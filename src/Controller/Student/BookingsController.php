<?php
declare(strict_types=1);

namespace App\Controller\Student;

use Cake\Http\Response;

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
            ->order(['Bookings.booking_date' => 'DESC'])
            ->all();

        $this->set(compact('bookings', 'student'));
        $this->set('title', 'My Bookings');
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

            return $this->redirect(['prefix' => false, 'controller' => 'Courses', 'action' => 'view', $classId]);
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

        $parentStudents = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.student_id' => $student->student_id])
            ->all();

        $parentId = null;
        if ($parentStudents->count() > 0) {
            $parentId = $parentStudents->first()->parent_id;
        }

        if ($this->request->is('post')) {
            $bookingData = [
                'class_id' => $classId,
                'student_id' => $student->student_id,
                'parent_id' => $parentId ?? $this->request->getData('parent_id'),
                'booking_status' => 'pending',
                'price_at_booking' => $class->course?->course_price ?? 0,
            ];
            $booking = $bookingsTable->newEntity($bookingData);

            if ($bookingsTable->save($booking)) {
                $this->loadComponent('Notification');
                $schedule = $class->start_datetime ? $class->start_datetime->format('D j M Y, g:ia') : 'TBA';
                $className = $class->course?->course_name ?? $class->class_code;
                $this->Notification->sendBookingConfirmation(
                    $identity->get('user_id'),
                    $className,
                    $schedule,
                );

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
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $booking = $bookingsTable->find()
            ->contain(['Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

        if ($this->request->is('post')) {
            $booking->booking_status = 'cancelled';
            if ($bookingsTable->save($booking)) {
                $this->Flash->success(__('Booking has been cancelled.'));
            } else {
                $this->Flash->error(__('Could not cancel booking. Please try again.'));
            }

            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('booking'));
        $this->set('title', 'Cancel Booking');

        return null;
    }
}
