<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use Cake\Http\Response;

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
                ->order(['Bookings.booking_date' => 'DESC'])
                ->all();
        }

        $this->set(compact('bookings', 'children'));
        $this->set('title', 'Family Bookings');
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
                        } catch (\Exception $e) {
                            // Notification failure should not block booking
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
        $parent = $this->getParentEntity();
        $children = $this->getChildrenForParent((int)$parent->parent_id);
        $studentIds = array_keys($children);

        $booking = $this->fetchTable('Bookings')->find()
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id IN' => $studentIds,
            ])
            ->firstOrFail();

        $this->request->allowMethod(['post']);
        $booking->booking_status = 'cancelled';

        if ($this->fetchTable('Bookings')->save($booking)) {
            $this->Flash->success(__('Booking has been cancelled.'));
        } else {
            $this->Flash->error(__('Could not cancel booking. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
