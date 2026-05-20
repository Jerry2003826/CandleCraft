<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Service\BookingCancellationService;
use App\Service\BookingConfirmationEmailService;
use App\Service\BookingEnrollmentStateService;
use App\Service\BookingService;
use Cake\Http\Response;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use RuntimeException;
use Throwable;

class BookingsController extends AppController
{
    /**
     * US4.3 -- View booked classes (schedule + calendar).
     * Accessible to all consumers regardless of age.
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $bookingsTable = $this->fetchTable('Bookings');
        $student = $this->getStudentEntity($identity);
        $bookings = $bookingsTable->find()
            ->where(['Bookings.student_id' => $student->student_id])
            ->contain([
                'Classes' => ['Courses', 'Teachers'],
                'Payments',
                'AttendanceRecords',
            ])
            ->orderBy(['Bookings.booking_date' => 'DESC'])
            ->all();

        $ref = $this->resolveWeekReference($this->request->getQuery('week_start'));
        $dow = (int)$ref->format('w');
        $weekStart = $ref->modify("-{$dow} days")->startOfDay();
        $weekEnd = $weekStart->modify('+6 days');

        $calendarEvents = $this->buildCalendarEvents($bookings, $weekStart, $weekEnd);

        $this->set(compact('bookings', 'calendarEvents', 'weekStart', 'weekEnd'));
        $this->set('title', 'My Schedule');
    }

    /**
     * US4.2 -- Book a class. Adults only (enforced by AppController).
     */
    public function add(?int $classId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $classesTable = $this->fetchTable('Classes');

        $class = $classesTable->find()
            ->contain(['Courses', 'Teachers'])
            ->where([
                'Classes.class_id' => $classId,
                'Classes.class_status IN' => BookingService::DEFAULT_ALLOWED_CLASS_STATUSES,
            ])
            ->firstOrFail();

        $bookingsCount = (new BookingEnrollmentStateService())->countBlockingBookingsForClass((int)$classId);
        $availableSlots = $class->capacity - $bookingsCount;

        // Best-effort display check only; BookingService applies the canonical capacity guard on POST.
        if (!$this->request->is('post') && $availableSlots <= 0) {
            $this->Flash->error(__(
                'This class is fully booked.',
            ));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']);
        }

        return $this->addAsStudent($identity, $class, $classId, $availableSlots);
    }

    /**
     * Add as student.
     *
     * @param mixed $identity Identity.
     * @param mixed $class Class.
     * @param mixed $classId Classid.
     * @param mixed $availableSlots Availableslots.
     */
    private function addAsStudent(mixed $identity, mixed $class, int $classId, int $availableSlots): ?Response
    {
        $student = $this->getStudentEntity($identity);

        if ($this->request->is('post')) {
            $result = $this->processBooking($classId, $student->student_id, null, $class, $identity);
            if ($result) {
                return $result;
            }
        }

        $this->set(compact('class', 'student', 'availableSlots'));
        $this->set('studentOptions', []);
        $this->set('selectedStudentId', null);
        $this->set('title', 'Book a Class');

        return null;
    }

    /**
     * Process booking.
     *
     * @param mixed $classId Classid.
     * @param mixed $studentId Studentid.
     * @param mixed $parentId Parentid.
     * @param mixed $class Class.
     * @param mixed $identity Identity.
     */
    private function processBooking(int $classId, int $studentId, ?int $parentId, mixed $class, mixed $identity): ?Response
    {
        try {
            $result = (new BookingService())->createBookingForStudent($classId, $studentId, $parentId);
            $booking = $result['booking'];
            $class = $result['class'];

            try {
                $this->loadComponent('Notification');
                $schedule = $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : 'TBA';
                $className = $class->course?->course_name ?? $class->class_code;
                $this->Notification->sendBookingConfirmation(
                    $identity->get('user_id'),
                    $className,
                    $schedule,
                );
            } catch (Throwable $exception) {
                Log::warning('Booking confirmation notification failed.', [
                    'booking_id' => $booking->booking_id ?? null,
                    'student_id' => $studentId,
                    'portal' => 'consumer',
                    'error' => $exception->getMessage(),
                ]);
            }
            $this->sendBookingConfirmationEmail((int)$booking->booking_id, 'Consumer');

            if (!empty($result['reactivated'])) {
                $this->Flash->success(__(
                    'Previous cancelled booking has been reactivated. Continue to payment to confirm it.',
                ));
            } else {
                $this->Flash->success(__(
                    'Your class has been reserved temporarily. Complete payment to confirm the booking.',
                ));
            }

            return $this->redirect([
                'prefix' => 'Consumer',
                'controller' => 'Payments',
                'action' => 'process',
                $booking->booking_id,
            ]);
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));
        }

        return null;
    }

    /**
     * Cancel.
     *
     * @param mixed $bookingId Bookingid.
     */
    public function cancel(?int $bookingId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentEntity($identity);
        $booking = $this->fetchTable('Bookings')->find()
            ->contain(['Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->cancelBooking((int)$booking->booking_id, [
                'portal_source' => 'consumer_portal',
            ]);
            $this->Flash->success(__(
                'Booking has been cancelled.',
            ));
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    // --- Helper methods ---

    /**
     * Get student entity.
     *
     * @param mixed $identity Identity.
     * @return mixed
     */
    private function getStudentEntity(mixed $identity)
    {
        return $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->firstOrFail();
    }

    /**
     * Build calendar events.
     *
     * @param mixed $bookings Bookings.
     * @param mixed $weekStart Weekstart.
     * @param mixed $weekEnd Weekend.
     */
    private function buildCalendarEvents(mixed $bookings, mixed $weekStart, mixed $weekEnd): array
    {
        $eventColors = ['#1a73e8', '#0b8043', '#8e24aa', '#d81b60', '#e37400', '#039be5', '#616161', '#c0ca33'];
        $courseColorMap = [];
        $colorIndex = 0;
        $calendarEvents = [];

        $bookingList = is_object($bookings) && method_exists($bookings, 'toList') ? $bookings->toList() : (is_array($bookings) ? $bookings : []);

        foreach ($bookingList as $b) {
            if (!$b->class_entity?->start_datetime || !$b->class_entity?->end_datetime) {
                continue;
            }
            if (!in_array($b->booking_status, ['pending', 'confirmed', 'completed'], true)) {
                continue;
            }
            $start = $b->class_entity->start_datetime;
            $end = $b->class_entity->end_datetime;
            $dateStr = $start->format('Y-m-d');
            if ($dateStr < $weekStart->format('Y-m-d') || $dateStr > $weekEnd->format('Y-m-d')) {
                continue;
            }

            $courseId = $b->class_entity->course_id ?? 0;
            if (!isset($courseColorMap[$courseId])) {
                $courseColorMap[$courseId] = $eventColors[$colorIndex % count($eventColors)];
                $colorIndex++;
            }

            $calendarEvents[] = [
                'full_date' => $dateStr,
                'start_hour' => (int)$start->format('G'),
                'start_minute' => (int)$start->format('i'),
                'end_hour' => (int)$end->format('G'),
                'end_minute' => (int)$end->format('i'),
                'title' => $b->class_entity?->course?->course_name ?? 'Class',
                'class_code' => $b->class_entity?->class_code ?? '',
                'location' => $b->class_entity?->location ?? '',
                'color' => $courseColorMap[$courseId],
                'booking_id' => $b->booking_id,
            ];
        }

        return $calendarEvents;
    }

    /**
     * Resolve week reference.
     *
     * @param mixed $weekStartParam Weekstartparam.
     */
    private function resolveWeekReference(mixed $weekStartParam): DateTime
    {
        if (!is_string($weekStartParam) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartParam) !== 1) {
            return new DateTime('now');
        }

        try {
            return new DateTime($weekStartParam);
        } catch (Throwable) {
            return new DateTime('now');
        }
    }

    /**
     * Send booking confirmation email.
     *
     * @param mixed $bookingId Bookingid.
     * @param mixed $portalPrefix Portalprefix.
     */
    private function sendBookingConfirmationEmail(int $bookingId, string $portalPrefix): void
    {
        $identity = $this->Authentication->getIdentity();
        (new BookingConfirmationEmailService())->sendForBooking($bookingId, [
            'portal_prefix' => $portalPrefix,
            'recipient_email' => (string)($identity?->get('email') ?? ''),
            'recipient_name' => (string)($identity?->get('username') ?? ''),
        ]);
    }
}
