<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Service\ResourceUploadService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class ResourcesController extends AppController
{
    private const ACCESSIBLE_BOOKING_STATUSES = ['confirmed', 'completed'];

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
        $this->set('title', 'Learning Resources');
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
                    'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
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

    public function download(?int $resourceId = null): Response
    {
        $identity = $this->Authentication->getIdentity();
        $resource = $this->fetchTable('LearningResources')->find()
            ->contain(['Classes' => ['Courses']])
            ->where(['LearningResources.resource_id' => $resourceId])
            ->firstOrFail();

        $studentIds = $this->getStudentIds($identity);
        if (!$this->hasAccessToClassResources($studentIds, (int)$resource->class_id)) {
            $this->Flash->error(__('You do not have access to this resource.'));

            return $this->redirect(['action' => 'index']);
        }

        return $this->buildDownloadResponse($resource->file_path, $resource->resource_type === 'video');
    }

    private function getStudentIds($identity): array
    {
        $student = $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->first();

        return $student ? [$student->student_id] : [];
    }

    private function hasAccessToClassResources(array $studentIds, int $classId): bool
    {
        if ($studentIds === []) {
            return false;
        }

        return $this->fetchTable('Bookings')->exists([
            'Bookings.student_id IN' => $studentIds,
            'Bookings.class_id' => $classId,
            'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
        ]);
    }

    private function buildDownloadResponse(?string $relativePath, bool $allowInlineVideo = false): Response
    {
        if (!$relativePath) {
            throw new NotFoundException('No uploaded file is available for this resource.');
        }

        $uploadService = new ResourceUploadService();
        $absolutePath = $uploadService->resolveStoredFilePath($relativePath);
        if ($absolutePath === null) {
            throw new NotFoundException('The requested resource file could not be found.');
        }

        $inline = $allowInlineVideo && filter_var($this->request->getQuery('inline', false), FILTER_VALIDATE_BOOLEAN);

        return $this->response
            ->withType($uploadService->detectStoredFileMediaType($absolutePath))
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withFile($absolutePath, [
                'download' => !$inline,
                'name' => basename($absolutePath),
            ]);
    }
}
