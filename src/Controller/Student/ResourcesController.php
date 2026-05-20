<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Service\ResourceUploadService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class ResourcesController extends AppController
{
    private const ACCESSIBLE_BOOKING_STATUSES = ['confirmed', 'completed'];

    /**
     * Index.
     */
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
                'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
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
                ->orderBy(['LearningResources.uploaded_at' => 'DESC'])
                ->all()
                ->groupBy('class_id')
                ->toArray();
        }

        $this->set(compact('bookings', 'resources', 'student'));
        $this->set('title', 'Learning Center');
    }

    /**
     * View.
     *
     * @param mixed $resourceId Resourceid.
     */
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
                'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
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

    /**
     * Download.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function download(?int $resourceId = null): Response
    {
        $identity = $this->Authentication->getIdentity();
        $student = $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $resource = $this->fetchTable('LearningResources')->find()
            ->where(['LearningResources.resource_id' => $resourceId])
            ->firstOrFail();

        $hasAccess = $this->fetchTable('Bookings')->exists([
            'Bookings.student_id' => $student->student_id,
            'Bookings.class_id' => $resource->class_id,
            'Bookings.booking_status IN' => self::ACCESSIBLE_BOOKING_STATUSES,
        ]);

        if (!$hasAccess) {
            $this->Flash->error(__('You do not have access to this resource.'));

            return $this->redirect(['action' => 'index']);
        }

        return $this->buildDownloadResponse($resource->file_path, $resource->resource_type === 'video');
    }

    /**
     * Build download response.
     *
     * @param mixed $relativePath Relativepath.
     * @param mixed $allowInlineVideo Allowinlinevideo.
     */
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
