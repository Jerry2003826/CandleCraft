<?php
declare(strict_types=1);

namespace App\Controller\Student;

use Cake\Http\Response;

class ResourcesController extends AppController
{
    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');
        $resourcesTable = $this->fetchTable('LearningResources');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $bookings = $bookingsTable->find()
            ->where([
                'Bookings.student_id' => $student->student_id,
                'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
            ])
            ->contain(['Classes' => ['Courses']])
            ->all();

        $classIds = $bookings->map(fn($b) => $b->class_id)->toArray();

        $resources = [];
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

        $this->set(compact('bookings', 'resources', 'student'));
        $this->set('title', 'Learning Center');
    }

    public function view(?int $resourceId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');
        $resourcesTable = $this->fetchTable('LearningResources');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $resource = $resourcesTable->find()
            ->contain(['Classes' => ['Courses']])
            ->where(['LearningResources.resource_id' => $resourceId])
            ->firstOrFail();

        $hasAccess = $bookingsTable->find()
            ->where([
                'Bookings.student_id' => $student->student_id,
                'Bookings.class_id' => $resource->class_id,
                'Bookings.booking_status IN' => ['pending', 'confirmed', 'completed'],
            ])
            ->count() > 0;

        if (!$hasAccess) {
            $this->Flash->error(__('You do not have access to this resource.'));
            return $this->redirect(['action' => 'index']);
        }

        $this->set(compact('resource'));
        $this->set('title', h($resource->resource_name));

        return null;
    }
}
