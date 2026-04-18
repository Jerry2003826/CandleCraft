<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Service\BookingCancellationService;
use App\Service\BookingService;
use Cake\Http\Response;
use RuntimeException;

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
            ->order(['Bookings.booking_date' => 'DESC'])
            ->all();

        $weekStartParam = $this->request->getQuery('week_start');
        $ref = $weekStartParam ? new \Cake\I18n\DateTime($weekStartParam) : new \Cake\I18n\DateTime('now');
        $dow = (int)$ref->format('w');
        $weekStart = $ref->modify("-{$dow} days")->startOfDay();
        $weekEnd = $weekStart->modify('+6 days');

        $calendarEvents = $this->buildCalendarEvents($bookings, $weekStart, $weekEnd);

        $this->set(compact('bookings', 'calendarEvents', 'weekStart', 'weekEnd'));
        $this->set('title', 'View Schedule & Attendance');
    }

    /**
     * US4.2 -- Book a class. Adults only (enforced by AppController).
     */
    public function add(?int $classId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $classesTable = $this->fetchTable('Classes');
        $bookingsTable = $this->fetchTable('Bookings');

        $class = $classesTable->find()
            ->contain(['Courses', 'Teachers'])
            ->where(['Classes.class_id' => $classId])
            ->firstOrFail();

        $bookingsCount = $bookingsTable->find()
            ->where([
                'Bookings.class_id' => $classId,
                'Bookings.booking_status IN' => ['pending', 'confirmed'],
            ])
            ->count();
        $availableSlots = $class->capacity - $bookingsCount;

        if ($availableSlots <= 0) {
            $this->Flash->error(__('This class is fully booked.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Courses', 'action' => 'index']);
        }

        return $this->addAsStudent($identity, $class, $classId, $availableSlots, $bookingsTable);
    }

    private function addAsStudent($identity, $class, int $classId, int $availableSlots, $bookingsTable): ?Response
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
        $this->set('title', 'Book Class');

        return null;
    }

    private function processBooking(int $classId, int $studentId, ?int $parentId, $class, $identity): ?Response
    {
        try {
            $result = (new BookingService())->createBookingForStudent($classId, $studentId, $parentId);
            $booking = $result['booking'];

            try {
                $this->loadComponent('Notification');
                $schedule = $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : 'TBA';
                $className = $class->course?->course_name ?? $class->class_code;
                $this->Notification->sendBookingConfirmation(
                    $identity->get('user_id'),
                    $className,
                    $schedule,
                );
            } catch (\Exception $e) {
            }

            if (!empty($result['reactivated'])) {
                $this->Flash->success(__('Previous cancelled booking has been reactivated. Please proceed to payment.'));
            } else {
                $this->Flash->success(__('Booking created successfully. Please proceed to payment.'));
            }

            return $this->redirect([
                'prefix' => 'Consumer',
                'controller' => 'Payments',
                'action' => 'process',
                $booking->booking_id,
            ]);
        } catch (\RuntimeException $exception) {
            $this->Flash->error(__($exception->getMessage()));
        }

        return null;
    }

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
            $this->Flash->success(__('Booking has been cancelled.'));
        } catch (RuntimeException $exception) {
            $this->Flash->error(__($exception->getMessage()));
        }

        return $this->redirect(['action' => 'index']);
    }

    // --- Helper methods ---

    private function getStudentEntity($identity)
    {
        return $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->firstOrFail();
    }

    private function buildCalendarEvents($bookings, $weekStart, $weekEnd): array
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
                'day_index' => (int)$start->format('w'),
                'start_hour' => (int)$start->format('G'),
                'start_minute' => (int)$start->format('i'),
                'end_hour' => (int)$end->format('G'),
                'end_minute' => (int)$end->format('i'),
                'title' => $b->class_entity?->course?->course_name ?? 'Class',
                'class_code' => $b->class_entity?->class_code ?? '',
                'location' => $b->class_entity?->location ?? '',
                'student_name' => $b->student?->student_name ?? '',
                'attendance_status' => $b->attendance_record?->attendance_status ?? '',
                'reminder_sent_at' => $b->reminder_sent_at,
                'color' => $courseColorMap[$courseId],
                'booking_id' => $b->booking_id,
            ];
        }

        return $calendarEvents;
    }
}
