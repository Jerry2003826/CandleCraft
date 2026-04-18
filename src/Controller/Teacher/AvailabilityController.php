<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Http\Response;

class AvailabilityController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $availabilitiesTable = $this->fetchTable('TeacherAvailabilities');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $availabilities = $availabilitiesTable->find()
            ->where(['TeacherAvailabilities.teacher_id' => $teacher->teacher_id])
            ->orderBy(['TeacherAvailabilities.day_of_week' => 'ASC', 'TeacherAvailabilities.start_time' => 'ASC'])
            ->all();

        $daysMap = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        $schedule = [];
        foreach ($availabilities as $availability) {
            $dayName = $daysMap[$availability->day_of_week] ?? 'Unknown';
            if (!isset($schedule[$dayName])) {
                $schedule[$dayName] = [];
            }
            $schedule[$dayName][] = $availability;
        }

        $this->set(compact('schedule', 'daysMap', 'teacher'));
        $this->set('title', 'View Schedule');
    }

    public function edit(): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $availabilitiesTable = $this->fetchTable('TeacherAvailabilities');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $daysMap = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $entities = [];
            $hasValidationErrors = false;
            if (!empty($data['slots'])) {
                foreach ($data['slots'] as $slot) {
                    if (empty($slot['day_of_week']) || empty($slot['start_time']) || empty($slot['end_time'])) {
                        continue;
                    }

                    $availability = $availabilitiesTable->newEntity([
                        'teacher_id' => $teacher->teacher_id,
                        'day_of_week' => (int)$slot['day_of_week'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'is_available' => true,
                    ]);

                    if ($availability->hasErrors()) {
                        $hasValidationErrors = true;
                    }
                    $entities[] = $availability;
                }
            }

            if ($hasValidationErrors) {
                $this->Flash->error(__('Could not update schedule. Please review the submitted time slots.'));
            } else {
                $connection = $availabilitiesTable->getConnection();
                $connection->transactional(function () use ($availabilitiesTable, $teacher, $entities): void {
                    $availabilitiesTable->deleteAll(['TeacherAvailabilities.teacher_id' => $teacher->teacher_id]);
                    foreach ($entities as $entity) {
                        $availabilitiesTable->saveOrFail($entity);
                    }
                });

                $this->Flash->success(__('Schedule updated successfully.'));

                return $this->redirect(['action' => 'index']);
            }
        }

        $existingSlots = $availabilitiesTable->find()
            ->where(['TeacherAvailabilities.teacher_id' => $teacher->teacher_id])
            ->orderBy(['TeacherAvailabilities.day_of_week' => 'ASC', 'TeacherAvailabilities.start_time' => 'ASC'])
            ->all();

        $this->set(compact('daysMap', 'existingSlots'));
        $this->set('title', 'Manage Availability');

        return null;
    }
}
