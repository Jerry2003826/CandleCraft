<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Collection\Collection;
use Cake\Http\Response;
use Cake\I18n\DateTime;

class AttendanceController extends AppController
{
    /**
     * Display the teacher's class roster for attendance marking.
     */
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
            ->orderBy(['Classes.start_datetime' => 'DESC'])
            ->all();

        $students = [];
        $selectedClass = null;
        if ($selectedClassId) {
            $selectedClass = $classesTable->find()
                ->where([
                    'Classes.class_id' => $selectedClassId,
                    'Classes.teacher_id' => $teacher->teacher_id,
                ])
                ->contain(['Courses'])
                ->firstOrFail();

            $bookingsTable = $this->fetchTable('Bookings');
            $students = $bookingsTable->find()
                ->where([
                    'Bookings.class_id' => $selectedClass->class_id,
                    'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
                ])
                ->contain(['Students', 'AttendanceRecords'])
                ->orderBy(['Bookings.booking_id' => 'ASC'])
                ->all();
        }

        $this->set(compact('classes', 'students', 'selectedClass', 'selectedClassId'));
        $this->set('title', 'Manage Attendance');
    }

    /**
     * Save one attendance status for a booking.
     */
    public function mark(): ?Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $attendanceRecordsTable = $this->fetchTable('AttendanceRecords');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $bookingId = (int)$this->request->getData('booking_id');
        $status = strtolower(trim((string)$this->request->getData('attendance_status')));
        $notes = $this->request->getData('attendance_notes', '');

        $allowedStatuses = ['present', 'absent', 'late', 'excused'];
        if (!in_array($status, $allowedStatuses, true)) {
            $this->Flash->error(__(
                'Choose one attendance status for this student, then save attendance again.',
            ));

            return $this->redirectAfterAttendance((int)$this->request->getData('class_id'));
        }

        $booking = $this->fetchTable('Bookings')->find()
            ->innerJoinWith('Classes', function ($query) use ($teacher) {
                return $query->where(['Classes.teacher_id' => $teacher->teacher_id]);
            })
            ->where(['Bookings.booking_id' => $bookingId])
            ->firstOrFail();

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
                'attendance_date' => new DateTime(),
            ]);
        }

        if ($attendanceRecordsTable->save($record)) {
            $this->Flash->success(__('Attendance saved.'));
        } else {
            $this->Flash->error(__(
                'Could not save attendance: {0}',
                $this->firstValidationMessage($record->getErrors()) ?: 'check the highlighted fields and try again.',
            ));
        }

        return $this->redirectAfterAttendance((int)$booking->class_id);
    }

    /**
     * Display attendance history for the current teacher's classes.
     */
    public function history(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');

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

        $statusFilter = $this->request->getQuery('status');
        $records = new Collection([]);

        if ($teacherClassIds !== []) {
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
                ->orderBy(['Bookings.booking_id' => 'DESC']);

            if ($statusFilter) {
                $query->matching('AttendanceRecords', function ($q) use ($statusFilter) {
                    return $q->where(['AttendanceRecords.attendance_status' => $statusFilter]);
                });
            }

            $records = $query->all();
        }

        $this->set(compact('records', 'statusFilter'));
        $this->set('title', 'Attendance History');
    }

    /**
     * Redirect back to the originating teacher attendance context.
     */
    private function redirectAfterAttendance(?int $classId = null): Response
    {
        $returnTo = trim((string)$this->request->getData('return_to'));
        if ($returnTo !== '' && str_starts_with($returnTo, '/teacher/') && !str_contains($returnTo, '//')) {
            return $this->redirect($returnTo);
        }

        $query = $classId !== null && $classId > 0 ? ['class_id' => $classId] : [];

        return $this->redirect(['action' => 'index', '?' => $query]);
    }

    /**
     * @param array<string,mixed> $errors
     */
    private function firstValidationMessage(array $errors): string
    {
        foreach ($errors as $messages) {
            if (is_array($messages)) {
                foreach ($messages as $message) {
                    if (is_string($message) && $message !== '') {
                        return $message;
                    }
                }
            }
        }

        return '';
    }
}
