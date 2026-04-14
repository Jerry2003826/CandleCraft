<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\Http\Response;

class ResourcesController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $bookingsTable = $this->fetchTable('Bookings');
        $resourcesTable = $this->fetchTable('LearningResources');

        $studentIds = $this->getStudentIds($identity);

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

    public function view(?int $resourceId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $bookingsTable = $this->fetchTable('Bookings');
        $resourcesTable = $this->fetchTable('LearningResources');

        $resource = $resourcesTable->find()
            ->contain(['Classes' => ['Courses']])
            ->where(['LearningResources.resource_id' => $resourceId])
            ->firstOrFail();

        $studentIds = $this->getStudentIds($identity);

        $hasAccess = false;
        if (!empty($studentIds)) {
            $hasAccess = $bookingsTable->find()
                ->where([
                    'Bookings.student_id IN' => $studentIds,
                    'Bookings.class_id' => $resource->class_id,
                    'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
                ])
                ->count() > 0;
        }

        if (!$hasAccess) {
            $this->Flash->error(__('You do not have access to this resource.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('resource'));
        $this->set('title', h($resource->resource_name));

        return null;
    }

    private function getStudentIds($identity): array
    {
        if ($this->userRole === 'parent') {
            $parent = $this->fetchTable('Parents')->find()
                ->where(['Parents.user_id' => $identity->get('user_id')])
                ->firstOrFail();

            return $this->fetchTable('ParentStudents')->find()
                ->where(['ParentStudents.parent_id' => $parent->parent_id])
                ->all()
                ->extract('student_id')
                ->toArray();
        }

        $student = $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->first();

        return $student ? [$student->student_id] : [];
    }
}
