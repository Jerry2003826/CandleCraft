<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Service\ResourceUploadService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class ResourcesController extends AppController
{
    private const ACCESSIBLE_BOOKING_STATUSES = ['confirmed', 'completed'];

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
                    'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
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
                    ->orderBy(['LearningResources.uploaded_at' => 'DESC'])
                    ->all()
                    ->groupBy('class_id')
                    ->toArray();
            }
        }

        $this->set(compact('bookings', 'resources'));
        $this->set('title', 'Learning Center');
    }

    public function download(?int $resourceId = null): Response
    {
        $identity = $this->Authentication->getIdentity();
        $parent = $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $studentIds = $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parent->parent_id])
            ->all()
            ->extract('student_id')
            ->toArray();

        $resource = $this->fetchTable('LearningResources')->find()
            ->where([
                'LearningResources.resource_id' => $resourceId,
                'LearningResources.resource_status' => 'active',
            ])
            ->firstOrFail();

        $hasAccess = $studentIds !== [] && $this->fetchTable('Bookings')->exists([
            'Bookings.student_id IN' => $studentIds,
            'Bookings.class_id' => $resource->class_id,
            'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
        ]);

        if (!$hasAccess) {
            $this->Flash->error(__('You do not have access to this resource.'));

            return $this->redirect(['action' => 'index']);
        }

        if (!$resource->file_path) {
            throw new NotFoundException('No uploaded file is available for this resource.');
        }

        $uploadService = new ResourceUploadService();
        $absolutePath = $uploadService->resolveStoredFilePath($resource->file_path);
        if ($absolutePath === null) {
            throw new NotFoundException('The requested resource file could not be found.');
        }

        return $this->response
            ->withType($uploadService->detectStoredFileMediaType($absolutePath))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withFile($absolutePath, [
                'download' => true,
                'name' => basename($absolutePath),
            ]);
    }
}
