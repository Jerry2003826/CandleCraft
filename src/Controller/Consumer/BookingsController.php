<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\Http\Response;

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

        if ($this->userRole === 'parent') {
            $studentIds = $this->getParentStudentIds($identity);
            $bookings = [];
            if (!empty($studentIds)) {
                $bookings = $bookingsTable->find()
                    ->where(['Bookings.student_id IN' => $studentIds])
                    ->contain([
                        'Students',
                        'Classes' => ['Courses', 'Teachers'],
                        'Payments',
                        'AttendanceRecords',
                    ])
                    ->order(['Bookings.booking_date' => 'DESC'])
                    ->all();
            }
        } else {
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
        }

        $weekStartParam = $this->request->getQuery('week_start');
        $ref = $weekStartParam ? new \Cake\I18n\DateTime($weekStartParam) : new \Cake\I18n\DateTime('now');
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

        $studentOptions = [];
        $selectedStudentId = null;

        if ($this->userRole === 'parent') {
            return $this->addAsParent($identity, $class, $classId, $availableSlots, $bookingsTable);
        }

        return $this->addAsStudent($identity, $class, $classId, $availableSlots, $bookingsTable);
    }

    private function addAsParent($identity, $class, int $classId, int $availableSlots, $bookingsTable): ?Response
    {
        $parent = $this->getParentEntity($identity);
        $children = $this->getChildrenForParent((int)$parent->parent_id);
        $studentOptions = [];
        foreach ($children as $id => $student) {
            $studentOptions[$id] = $student->student_name;
        }

        if (empty($studentOptions)) {
            $this->Flash->error(__('No linked children found. Please contact admin.'));

            return $this->redirect(['action' => 'index']);
        }

        $selectedStudentId = null;
        if ($this->request->is('post')) {
            $selectedStudentId = (int)$this->request->getData('student_id');

            if (!array_key_exists($selectedStudentId, $children)) {
                $this->Flash->error(__('Selected student is not linked to your account.'));
            } else {
                $result = $this->processBooking(
                    $bookingsTable,
                    $classId,
                    $selectedStudentId,
                    $parent->parent_id,
                    $class,
                    $identity,
                );
                if ($result) {
                    return $result;
                }
            }
        }

        $this->set(compact('class', 'availableSlots', 'studentOptions', 'selectedStudentId'));
        $this->set('title', 'Book Class');

        return null;
    }

    private function addAsStudent($identity, $class, int $classId, int $availableSlots, $bookingsTable): ?Response
    {
        $student = $this->getStudentEntity($identity);

        $parentStudents = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.student_id' => $student->student_id])
            ->all();
        $parentId = $parentStudents->count() > 0 ? $parentStudents->first()->parent_id : null;

        if ($this->request->is('post')) {
            $result = $this->processBooking(
                $bookingsTable,
                $classId,
                $student->student_id,
                $parentId,
                $class,
                $identity,
            );
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

    private function processBooking($bookingsTable, int $classId, int $studentId, ?int $parentId, $class, $identity): ?Response
    {
        $existingBooking = $bookingsTable->find()
            ->where([
                'Bookings.student_id' => $studentId,
                'Bookings.class_id' => $classId,
                'Bookings.booking_status IN' => ['pending', 'confirmed'],
            ])
            ->first();

        if ($existingBooking) {
            $this->Flash->error(__('This student is already booked for this class.'));

            return $this->redirect(['action' => 'index']);
        }

        $existingAnyStatusBooking = $bookingsTable->find()
            ->where([
                'Bookings.student_id' => $studentId,
                'Bookings.class_id' => $classId,
            ])
            ->first();

        if ($existingAnyStatusBooking) {
            if ($existingAnyStatusBooking->booking_status === 'cancelled') {
                $existingAnyStatusBooking->booking_status = 'pending';
                $existingAnyStatusBooking->parent_id = $parentId ?? $existingAnyStatusBooking->parent_id;
                $existingAnyStatusBooking->price_at_booking = $class->course?->course_price ?? 0;
                $existingAnyStatusBooking->booking_date = new \Cake\I18n\DateTime();

                if ($bookingsTable->save($existingAnyStatusBooking)) {
                    $this->Flash->success(__('Previous cancelled booking has been reactivated. Please proceed to payment.'));

                    return $this->redirect([
                        'prefix' => 'Consumer',
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
            'student_id' => $studentId,
            'parent_id' => $parentId,
            'booking_status' => 'pending',
            'price_at_booking' => $class->course?->course_price ?? 0,
        ]);

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
            } catch (\Exception $e) {
            }

            $this->Flash->success(__('Booking created successfully. Please proceed to payment.'));

            return $this->redirect([
                'prefix' => 'Consumer',
                'controller' => 'Payments',
                'action' => 'process',
                $booking->booking_id,
            ]);
        }

        $this->Flash->error(__('Could not create booking. Please try again.'));

        return null;
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $identity = $this->Authentication->getIdentity();
        $bookingsTable = $this->fetchTable('Bookings');

        if ($this->userRole === 'parent') {
            $studentIds = $this->getParentStudentIds($identity);
            $booking = $bookingsTable->find()
                ->where([
                    'Bookings.booking_id' => $bookingId,
                    'Bookings.student_id IN' => $studentIds,
                ])
                ->firstOrFail();
        } else {
            $student = $this->getStudentEntity($identity);
            $booking = $bookingsTable->find()
                ->contain(['Classes' => ['Courses']])
                ->where([
                    'Bookings.booking_id' => $bookingId,
                    'Bookings.student_id' => $student->student_id,
                ])
                ->firstOrFail();
        }

        $booking->booking_status = 'cancelled';
        if ($bookingsTable->save($booking)) {
            $this->Flash->success(__('Booking has been cancelled.'));
        } else {
            $this->Flash->error(__('Could not cancel booking. Please try again.'));
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

    private function getParentEntity($identity)
    {
        return $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity->get('user_id')])
            ->firstOrFail();
    }

    private function getParentStudentIds($identity): array
    {
        $parent = $this->getParentEntity($identity);

        return $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->all()
            ->extract('student_id')
            ->toArray();
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
                'color' => $courseColorMap[$courseId],
                'booking_id' => $b->booking_id,
            ];
        }

        return $calendarEvents;
    }
}
