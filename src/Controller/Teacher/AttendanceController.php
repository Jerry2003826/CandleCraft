<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Http\Response;

class AttendanceController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $classesTable = $this->fetchTable('Classes');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $selectedClassId = $this->request->getQuery('class_id');

        $classes = $classesTable->find()
            ->where(['Classes.teacher_id' => $teacher->teacher_id])
            ->contain(['Courses'])
            ->order(['Classes.start_datetime' => 'DESC'])
            ->all();

        $students = [];
        $selectedClass = null;
        if ($selectedClassId) {
            $selectedClass = $classesTable->get($selectedClassId, ['contain' => ['Courses']]);

            $bookingsTable = $this->fetchTable('Bookings');
            $students = $bookingsTable->find()
                ->where([
                    'Bookings.class_id' => $selectedClassId,
                    'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
                ])
                ->contain(['Students', 'AttendanceRecords'])
                ->all();
        }

        $this->set(compact('classes', 'students', 'selectedClass', 'selectedClassId'));
        $this->set('title', 'Attendance Management');
    }

    public function mark(): ?Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $attendanceRecordsTable = $this->fetchTable('AttendanceRecords');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $bookingId = $this->request->getData('booking_id');
        $status = $this->request->getData('attendance_status');
        $notes = $this->request->getData('attendance_notes', '');

        $existing = $attendanceRecordsTable->find()
            ->where(['AttendanceRecords.booking_id' => $bookingId])
            ->first();

        if ($existing) {
            $existing->attendance_status = $status;
            $existing->attendance_notes = $notes;
            $existing->marked_by_teacher_id = $teacher->teacher_id;
            $record = $existing;
        } else {
            $record = $attendanceRecordsTable->newEntity([
                'booking_id' => $bookingId,
                'marked_by_teacher_id' => $teacher->teacher_id,
                'attendance_status' => $status,
                'attendance_notes' => $notes,
                'attendance_date' => date('Y-m-d H:i:s'),
            ]);
        }

        if ($attendanceRecordsTable->save($record)) {
            $this->Flash->success(__('Attendance saved.'));
        } else {
            $this->Flash->error(__('Could not save attendance.'));
        }

        $classId = $this->request->getData('class_id');

        return $this->redirect(['action' => 'index', '?' => ['class_id' => $classId]]);
    }

    public function history(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $attendanceRecordsTable = $this->fetchTable('AttendanceRecords');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $classesTable = $this->fetchTable('Classes');
        $teacherClassIds = $classesTable->find()
            ->select(['class_id'])
            ->where(['Classes.teacher_id' => $teacher->teacher_id])
            ->all()
            ->map(fn($c) => $c->class_id)
            ->toArray();

        $bookingsTable = $this->fetchTable('Bookings');
        $query = $bookingsTable->find()
            ->where([
                'Bookings.class_id IN' => $teacherClassIds,
            ])
            ->contain([
                'Students',
                'Classes' => ['Courses'],
                'AttendanceRecords',
            ])
            ->order(['Bookings.booking_id' => 'DESC']);

        $statusFilter = $this->request->getQuery('status');
        if ($statusFilter) {
            $query->matching('AttendanceRecords', function ($q) use ($statusFilter) {
                return $q->where(['AttendanceRecords.attendance_status' => $statusFilter]);
            });
        }

        $records = $query->all();

        $this->set(compact('records', 'statusFilter'));
        $this->set('title', 'Attendance History');
    }
}
