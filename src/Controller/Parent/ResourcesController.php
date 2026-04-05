<?php
declare(strict_types=1);

namespace App\Controller\Parent;

class ResourcesController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $parentsTable = $this->fetchTable('Parents');
        $parentStudentsTable = $this->fetchTable('ParentStudents');
        $bookingsTable = $this->fetchTable('Bookings');
        $resourcesTable = $this->fetchTable('LearningResources');

        $parent = $parentsTable->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $studentIds = $parentStudentsTable->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->all()
            ->extract('student_id')
            ->toArray();

        $bookings = new \Cake\Collection\Collection([]);
        $resources = [];

        if (!empty($studentIds)) {
            $bookings = $bookingsTable->find()
                ->where([
                    'Bookings.student_id IN' => $studentIds,
                    'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
                ])
                ->contain(['Classes' => ['Courses'], 'Students'])
                ->all();

            $classIds = $bookings->map(fn($b) => $b->class_id)->toArray();

            if (!empty($classIds)) {
                $resources = $resourcesTable->find()
                    ->where([
                        'LearningResources.class_id IN' => $classIds,
                        'LearningResources.resource_status' => 'active',
                    ])
                    ->contain(['Classes' => ['Courses']])
                    ->order(['LearningResources.uploaded_at' => 'DESC'])
                    ->all()
                    ->groupBy('class_id')
                    ->toArray();
            }
        }

        $this->set(compact('bookings', 'resources'));
        $this->set('title', 'Learning Center');
    }
}
