<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Http\Response;
use DateTime;

class DashboardController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $classesTable = $this->fetchTable('Classes');

        $teacher = $teachersTable->find()
            ->contain(['Users'])
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $upcoming = $classesTable->find()
            ->contain(['Courses', 'Bookings'])
            ->where([
                'Classes.teacher_id' => $teacher->teacher_id,
                'Classes.start_datetime >' => new DateTime(),
            ])
            ->orderBy(['Classes.start_datetime' => 'ASC'])
            ->all()
            ->toArray();

        $this->set(compact('teacher', 'upcoming'));
        $this->set('title', 'Teacher Dashboard');
    }

    /**
     * Mark attendance.
     *
     * @param mixed $bookingId Bookingid.
     */
    public function markAttendance(?string $bookingId = null): Response
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
