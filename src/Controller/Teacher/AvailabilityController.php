<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Http\Response;
use DateTimeImmutable;

class AvailabilityController extends AppController
{
    private const CALENDAR_YEARS_AHEAD = 3;

    /**
     * Display availability slots and blocked dates for the current teacher.
     */
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $availabilitiesTable = $this->fetchTable('TeacherAvailabilities');
        $blockedDatesTable = $this->fetchTable('TeacherBlockedDates');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $availabilities = $availabilitiesTable->find()
            ->where(['TeacherAvailabilities.teacher_id' => $teacher->teacher_id])
            ->orderBy(['TeacherAvailabilities.day_of_week' => 'ASC', 'TeacherAvailabilities.start_time' => 'ASC'])
            ->all()
            ->toArray();

        $blockedDates = $blockedDatesTable->find()
            ->where(['TeacherBlockedDates.teacher_id' => $teacher->teacher_id])
            ->orderBy(['TeacherBlockedDates.blocked_date' => 'ASC'])
            ->all();

        $daysMap = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
            4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
        ];

        $schedule = [];
        foreach ($availabilities as $availability) {
            $dayName = $daysMap[$availability->day_of_week] ?? 'Unknown';
            $schedule[$dayName][] = $availability;
        }

        $existingSlots = $availabilities;
        [$calendarMinDate, $calendarMaxDate] = $this->calendarDateBounds();

        $this->set(compact(
            'schedule',
            'daysMap',
            'teacher',
            'existingSlots',
            'blockedDates',
            'calendarMinDate',
            'calendarMaxDate',
        ));
        $this->set('title', 'Manage Availability');
    }

    /**
     * Replace weekly availability slots for the current teacher.
     */
    public function edit(): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $teachersTable = $this->fetchTable('Teachers');
        $availabilitiesTable = $this->fetchTable('TeacherAvailabilities');

        $teacher = $teachersTable->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $daysMap = [
            1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday',
            4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
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

                    if (
                        !$this->dateWithinCalendarLimit($slot['valid_from'] ?? null) ||
                        !$this->dateWithinCalendarLimit($slot['valid_until'] ?? null)
                    ) {
                        $hasValidationErrors = true;
                    }

                    $availability = $availabilitiesTable->newEntity([
                        'teacher_id' => $teacher->teacher_id,
                        'day_of_week' => (int)$slot['day_of_week'],
                        'start_time' => $slot['start_time'],
                        'end_time' => $slot['end_time'],
                        'valid_from' => $slot['valid_from'] ?? null,
                        'valid_until' => $slot['valid_until'] ?? null,
                        'is_available' => true,
                    ]);
                    if ($availability->hasErrors()) {
                        $hasValidationErrors = true;
                    }
                    $entities[] = $availability;
                }
            }

            if ($hasValidationErrors) {
                $this->Flash->error(__(
                    'Could not update availability. Check each time slot and keep date ranges ' .
                    'within the visible calendar years.',
                ));
            } else {
                $keptIds = [];
                foreach ($data['slots'] ?? [] as $slot) {
                    if (!empty($slot['id'])) {
                        $keptIds[] = (int)$slot['id'];
                    }
                }

                $connection = $availabilitiesTable->getConnection();
                $connection->transactional(function () use ($availabilitiesTable, $teacher, $entities, $keptIds): void {
                    $removeConditions = ['teacher_id' => $teacher->teacher_id];
                    if (!empty($keptIds)) {
                        $removeConditions['id NOT IN'] = $keptIds;
                    }
                    $availabilitiesTable->deleteAll($removeConditions);

                    foreach ($entities as $entity) {
                        $availabilitiesTable->deleteAll([
                            'teacher_id' => $teacher->teacher_id,
                            'day_of_week' => $entity->day_of_week,
                            'start_time <' => $entity->end_time,
                            'end_time >' => $entity->start_time,
                        ]);
                        $availabilitiesTable->saveOrFail($entity);
                    }
                });

                $this->Flash->success(__('Availability updated successfully.'));

                return $this->redirect(['action' => 'index']);
            }
        }

        $existingSlots = $availabilitiesTable->find()
            ->where(['TeacherAvailabilities.teacher_id' => $teacher->teacher_id])
            ->orderBy(['TeacherAvailabilities.day_of_week' => 'ASC', 'TeacherAvailabilities.start_time' => 'ASC'])
            ->all();

        [$calendarMinDate, $calendarMaxDate] = $this->calendarDateBounds();

        $this->set(compact('daysMap', 'existingSlots', 'calendarMinDate', 'calendarMaxDate'));
        $this->set('title', 'Manage Availability');

        return null;
    }

    /**
     * Add a blocked-out date inside the visible calendar range.
     */
    public function addBlockedDate(): ?Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $teacher = $this->fetchTable('Teachers')->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $blockedDatesTable = $this->fetchTable('TeacherBlockedDates');
        $blockedDate = (string)$this->request->getData('blocked_date');
        if (!$this->blockedDateWithinAllowedRange($blockedDate)) {
            $this->Flash->error(__(
                'Choose a block-out date between today and {0}.',
                $this->calendarDateBounds()[1],
            ));

            return $this->redirect(['action' => 'index', '?' => ['tab' => 'blocked']]);
        }

        $entity = $blockedDatesTable->newEntity([
            'teacher_id' => $teacher->teacher_id,
            'blocked_date' => $blockedDate,
            'reason' => $this->request->getData('reason') ?: null,
        ]);

        if ($blockedDatesTable->save($entity)) {
            $this->Flash->success(__('Date blocked successfully.'));
        } else {
            $this->Flash->error(__('Could not block date. Please try again.'));
        }

        return $this->redirect(['action' => 'index', '?' => ['tab' => 'blocked']]);
    }

    /**
     * Remove one blocked-out date for the current teacher.
     */
    public function deleteBlockedDate(int $id): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $identity = $this->Authentication->getIdentity();
        $teacher = $this->fetchTable('Teachers')->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $blockedDatesTable = $this->fetchTable('TeacherBlockedDates');
        $entry = $blockedDatesTable->find()
            ->where(['id' => $id, 'teacher_id' => $teacher->teacher_id])
            ->firstOrFail();

        $blockedDatesTable->delete($entry);
        $this->Flash->success(__('Blocked date removed.'));

        return $this->redirect(['action' => 'index', '?' => ['tab' => 'blocked']]);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function calendarDateBounds(): array
    {
        $min = new DateTimeImmutable('today');
        $max = $min->modify('+' . self::CALENDAR_YEARS_AHEAD . ' years');

        return [$min->format('Y-m-d'), $max->format('Y-m-d')];
    }

    /**
     * Check whether an optional availability date is inside the maximum year range.
     */
    private function dateWithinCalendarLimit(mixed $date): bool
    {
        $date = trim((string)$date);
        if ($date === '') {
            return true;
        }

        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }

        [, $maxDate] = $this->calendarDateBounds();

        return date('Y-m-d', $timestamp) <= $maxDate;
    }

    /**
     * Check whether a blocked date is inside the visible calendar range.
     */
    private function blockedDateWithinAllowedRange(string $date): bool
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return false;
        }

        [$minDate, $maxDate] = $this->calendarDateBounds();
        $normalized = date('Y-m-d', $timestamp);

        return $normalized >= $minDate && $normalized <= $maxDate;
    }
}
