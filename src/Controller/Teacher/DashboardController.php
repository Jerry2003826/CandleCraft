<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

class DashboardController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $classesTable = $this->fetchTable('Classes');

        $teacher = $teachersTable->find()
            ->contain(['Users'])
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $classes = $classesTable->find()
            ->where(['Classes.teacher_id' => $teacher->teacher_id])
            ->contain([
                'Courses',
                'Bookings' => [
                    'Students',
                    'AttendanceRecords',
                ],
            ])
            ->orderBy(['Classes.start_datetime' => 'ASC'])
            ->all();

        $classCount = $classes->count();
        $upcomingClasses = $classes->filter(
            fn ($class) => $class->start_datetime && $class->start_datetime->isFuture(),
        )->count();

        $studentCount = 0;
        $attendanceMarked = 0;
        foreach ($classes as $class) {
            $studentCount += count($class->bookings);
            foreach ($class->bookings as $booking) {
                if ($booking->attendance_record !== null) {
                    $attendanceMarked++;
                }
            }
        }

        $this->set(compact(
            'teacher',
            'classes',
            'classCount',
            'upcomingClasses',
            'studentCount',
            'attendanceMarked',
        ));
        $this->set('title', 'Teacher Dashboard');
    }

    public function markAttendance(?string $bookingId = null)
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $bookingsTable = $this->fetchTable('Bookings');
        $classesTable = $this->fetchTable('Classes');
        $attendanceRecordsTable = $this->fetchTable('AttendanceRecords');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $booking = $bookingsTable->find()
            ->contain(['AttendanceRecords'])
            ->where(['Bookings.booking_id' => $bookingId])
            ->firstOrFail();

        $class = $classesTable->find()
            ->select(['class_id', 'teacher_id'])
            ->where(['Classes.class_id' => $booking->class_id])
            ->firstOrFail();

        if ((int)$class->teacher_id !== (int)$teacher->teacher_id) {
            $this->Flash->error(__('You can only update attendance for your own classes.'));

            return $this->redirect(['action' => 'index']);
        }

        $attendance = $booking->attendance_record ?? $attendanceRecordsTable->newEmptyEntity();
        $attendanceData = [
            'booking_id' => $booking->booking_id,
            'marked_by_teacher_id' => $teacher->teacher_id,
            'attendance_status' => (string)$this->request->getData('attendance_status'),
            'attendance_notes' => $this->request->getData('attendance_notes'),
            'attendance_date' => date('Y-m-d H:i:s'),
        ];
        $attendance = $attendanceRecordsTable->patchEntity($attendance, $attendanceData);

        if ($attendanceRecordsTable->save($attendance)) {
            $this->Flash->success(__('Attendance was saved successfully.'));
        } else {
            $this->Flash->error(__('Attendance could not be saved. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
