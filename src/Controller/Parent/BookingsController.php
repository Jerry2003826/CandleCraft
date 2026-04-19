<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Service\BookingCancellationService;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Http\Response;
use RuntimeException;

class BookingsController extends AppController
{
    private function getParentEntity()
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();
    }

    private function getChildrenForParent(int $parentId): array
    {
        $links = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parentId])
            ->contain(['Students'])
            ->all();

        $children = [];
        foreach ($links as $link) {
            if ($link->student) {
                $children[$link->student_id] = $link->student;
            }
        }

        return $children;
    }

    public function index(): void
    {
        $parent = $this->getParentEntity();
        $children = $this->getChildrenForParent((int)$parent->parent_id);
        $studentIds = array_keys($children);

        $bookings = [];
        if (!empty($studentIds)) {
            $bookings = $this->fetchTable('Bookings')->find()
                ->where(['Bookings.student_id IN' => $studentIds])
                ->contain([
                    'Students',
                    'Classes' => ['Courses', 'Teachers'],
                    'Payments',
                    'AttendanceRecords',
                ])
                ->orderBy(['Bookings.booking_date' => 'DESC'])
                ->all();
        }

        $ref = $this->resolveWeekReference($this->request->getQuery('week_start'));
        $dow = (int)$ref->format('w');
        $weekStart = $ref->modify("-{$dow} days")->startOfDay();
        $weekEnd = $weekStart->modify('+6 days');

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
                'student_name' => $b->student?->student_name ?? '',
                'color' => $courseColorMap[$courseId],
                'booking_id' => $b->booking_id,
            ];
        }

        $this->set(compact('bookings', 'children', 'calendarEvents', 'weekStart', 'weekEnd'));
        $this->set('title', 'My Schedule');
    }

    public function add(?int $classId = null, ?int $studentId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $children = $this->getChildrenForParent((int)$parent->parent_id);
        $studentOptions = [];
        foreach ($children as $id => $student) {
            $studentOptions[$id] = $student->student_name;
        }

        if (empty($studentOptions)) {
            $this->Flash->error(__('No linked children found. Please contact admin.'));

            return $this->redirect(['action' => 'index']);
        }

        $class = $this->fetchTable('Classes')->find()
            ->contain(['Courses', 'Teachers'])
            ->where(['Classes.class_id' => $classId])
            ->firstOrFail();

        $bookingsTable = $this->fetchTable('Bookings');
        $bookingsCount = $bookingsTable->find()
            ->where([
                'Bookings.class_id' => $classId,
                'Bookings.booking_status IN' => ['pending', 'confirmed'],
            ])
            ->count();
        $availableSlots = $class->capacity - $bookingsCount;

        if ($availableSlots <= 0) {
            $this->Flash->error(__('This class is fully booked.'));

            return $this->redirect(['prefix' => false, 'controller' => 'Courses', 'action' => 'view', $classId]);
        }

        $selectedStudentId = $studentId;
        if ($this->request->is('post')) {
            $selectedStudentId = (int)$this->request->getData('student_id');

            if (!array_key_exists($selectedStudentId, $children)) {
                $this->Flash->error(__('Selected student is not linked to your account.'));
            } else {
                $existingBooking = $bookingsTable->find()
                    ->where([
                        'Bookings.student_id' => $selectedStudentId,
                        'Bookings.class_id' => $classId,
                        'Bookings.booking_status IN' => ['pending', 'confirmed'],
                    ])
                    ->first();

                if ($existingBooking) {
                    $this->Flash->error(__('This child is already booked for this class.'));
                } else {
                    $existingAnyStatusBooking = $bookingsTable->find()
                        ->where([
                            'Bookings.student_id' => $selectedStudentId,
                            'Bookings.class_id' => $classId,
                        ])
                        ->first();

                    if ($existingAnyStatusBooking) {
                        if ($existingAnyStatusBooking->booking_status === 'cancelled') {
                            $existingAnyStatusBooking->booking_status = 'pending';
                            $existingAnyStatusBooking->parent_id = $parent->parent_id;
                            $existingAnyStatusBooking->price_at_booking = $class->course?->course_price ?? 0;
                            $existingAnyStatusBooking->booking_date = new \Cake\I18n\DateTime();

                            if ($bookingsTable->save($existingAnyStatusBooking)) {
                                $this->Flash->success(__('Previous cancelled booking has been reactivated. Please proceed to payment.'));

                                return $this->redirect([
                                    'prefix' => 'Parent',
                                    'controller' => 'Payments',
                                    'action' => 'process',
                                    $existingAnyStatusBooking->booking_id,
                                ]);
                            }
                        }

                        $this->Flash->error(__('A booking record for this class already exists and cannot be duplicated.'));

                        return $this->redirect(['action' => 'index']);
                    }

                    $booking = $bookingsTable->newEntity([
                        'class_id' => $classId,
                        'student_id' => $selectedStudentId,
                        'parent_id' => $parent->parent_id,
                        'booking_status' => 'pending',
                        'price_at_booking' => $class->course?->course_price ?? 0,
                    ]);

                    if ($bookingsTable->save($booking)) {
                        try {
                            $this->loadComponent('Notification');
                            $schedule = $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : 'TBA';
                            $className = $class->course?->course_name ?? $class->class_code;
                            $this->Notification->sendBookingConfirmation(
                                $this->Authentication->getIdentity()->get('user_id'),
                                $className,
                                $schedule,
                            );
                        } catch (\Throwable $exception) {
                            Log::warning('Booking confirmation notification failed.', [
                                'booking_id' => $booking->booking_id ?? null,
                                'student_id' => $selectedStudentId,
                                'parent_id' => $parent->parent_id,
                                'portal' => 'parent',
                                'error' => $exception->getMessage(),
                            ]);
                        }

                        $this->Flash->success(__('Booking created successfully. Please proceed to payment.'));

                        return $this->redirect([
                            'prefix' => 'Parent',
                            'controller' => 'Payments',
                            'action' => 'process',
                            $booking->booking_id,
                        ]);
                    }
                    $this->Flash->error(__('Could not create booking. Please try again.'));
                }
            }
        }

        $this->set(compact('class', 'availableSlots', 'studentOptions', 'selectedStudentId'));
        $this->set('title', 'Book Class');

        return null;
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $parent = $this->getParentEntity();
        $children = $this->getChildrenForParent((int)$parent->parent_id);
        $studentIds = array_keys($children);

        $booking = $this->fetchTable('Bookings')->find()
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id IN' => $studentIds,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->cancelBooking((int)$booking->booking_id, [
                'portal_source' => 'parent_portal',
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
