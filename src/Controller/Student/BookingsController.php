<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Service\BookingCancellationService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Http\Response;
use RuntimeException;

class BookingsController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

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

        $eventColors = ['#1a73e8', '#0b8043', '#8e24aa', '#d81b60', '#e37400', '#039be5', '#616161', '#c0ca33'];
        $courseColorMap = [];
        $colorIndex = 0;

        $calendarEvents = [];
        foreach ($bookings as $b) {
            if (!$b->class_entity?->start_datetime || !$b->class_entity?->end_datetime) {
                continue;
            }
            if (!in_array($b->booking_status, ['pending', 'confirmed', 'completed'], true)) {
                continue;
            }
            $start = $b->class_entity->start_datetime;
            $end = $b->class_entity->end_datetime;
            $dateStr = $start->format('Y-m-d');
            $wsStr = $weekStart->format('Y-m-d');
            $weStr = $weekEnd->format('Y-m-d');
            if ($dateStr < $wsStr || $dateStr > $weStr) {
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
                'color' => $courseColorMap[$courseId],
                'booking_id' => $b->booking_id,
            ];
        }

        $this->set(compact('bookings', 'student', 'calendarEvents', 'weekStart', 'weekEnd'));
        $this->set('title', 'My Schedule');
    }

    public function add(?int $classId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $classesTable = $this->fetchTable('Classes');
        $bookingsTable = $this->fetchTable('Bookings');
        $parentsTable = $this->fetchTable('Parents');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

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

            return $this->redirect(['prefix' => 'Student', 'controller' => 'Courses', 'action' => 'index']);
        }

        $existingBooking = $bookingsTable->find()
            ->where([
                'Bookings.student_id' => $student->student_id,
                'Bookings.class_id' => $classId,
                'Bookings.booking_status IN' => ['pending', 'confirmed'],
            ])
            ->first();

        if ($existingBooking) {
            $this->Flash->error(__('You have already booked this class.'));

            return $this->redirect(['action' => 'index']);
        }

        $existingAnyStatusBooking = $bookingsTable->find()
            ->where([
                'Bookings.student_id' => $student->student_id,
                'Bookings.class_id' => $classId,
            ])
            ->first();

        $parentStudents = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.student_id' => $student->student_id])
            ->all();

        $parentId = null;
        if ($parentStudents->count() > 0) {
            $parentId = $parentStudents->first()->parent_id;
        }

        if ($this->request->is('post')) {
            if ($existingAnyStatusBooking) {
                if ($existingAnyStatusBooking->booking_status === 'cancelled') {
                    $existingAnyStatusBooking->booking_status = 'pending';
                    $existingAnyStatusBooking->parent_id = $parentId ?? $this->request->getData('parent_id');
                    $existingAnyStatusBooking->price_at_booking = $class->course?->course_price ?? 0;
                    $existingAnyStatusBooking->booking_date = new \Cake\I18n\DateTime();

                    if ($bookingsTable->save($existingAnyStatusBooking)) {
                        $this->Flash->success(__('Previous cancelled booking has been reactivated. Please proceed to payment.'));

                        return $this->redirect([
                            'prefix' => 'Student',
                            'controller' => 'Payments',
                            'action' => 'process',
                            $existingAnyStatusBooking->booking_id,
                        ]);
                    }
                }

                $this->Flash->error(__('A booking record for this class already exists and cannot be duplicated.'));

                return $this->redirect(['action' => 'index']);
            }

            $bookingData = [
                'class_id' => $classId,
                'student_id' => $student->student_id,
                'parent_id' => $parentId ?? $this->request->getData('parent_id'),
                'booking_status' => 'pending',
                'price_at_booking' => $class->course?->course_price ?? 0,
            ];
            $booking = $bookingsTable->newEntity($bookingData);

            if ($bookingsTable->save($booking)) {
                try {
                    $this->loadComponent('Notification');
                    $schedule = $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : 'TBA';
                    $className = $class->course?->course_name ?? $class->class_code;
                    $this->Notification->sendBookingConfirmation(
                        $identity->get('user_id'),
                        $className,
                        $schedule,
                    );
                } catch (\Throwable $exception) {
                    Log::warning('Booking confirmation notification failed.', [
                        'booking_id' => $booking->booking_id ?? null,
                        'student_id' => $student->student_id,
                        'portal' => 'student',
                        'error' => $exception->getMessage(),
                    ]);
                }

                $this->Flash->success(__('Booking created successfully. Please proceed to payment.'));

                return $this->redirect([
                    'prefix' => 'Student',
                    'controller' => 'Payments',
                    'action' => 'process',
                    $booking->booking_id,
                ]);
            }
            $this->Flash->error(__('Could not create booking. Please try again.'));
        }

        $this->set(compact('class', 'student', 'availableSlots'));
        $this->set('title', 'Book Class');

        return null;
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $booking = $this->fetchTable('Bookings')->find()
            ->contain(['Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->cancelBooking((int)$booking->booking_id, [
                'portal_source' => 'student_portal',
            ]);
            $this->Flash->success(__('Booking has been cancelled.'));
        } catch (RuntimeException $exception) {
            $this->Flash->error(__($exception->getMessage()));
        }

        return $this->redirect(['action' => 'index']);
    }

    private function resolveWeekReference(mixed $weekStartParam): DateTime
    {
        if (!is_string($weekStartParam) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $weekStartParam) !== 1) {
            return new DateTime('now');
        }

        try {
            return new DateTime($weekStartParam);
        } catch (\Throwable) {
            return new DateTime('now');
        }
    }
}
