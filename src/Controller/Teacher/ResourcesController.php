<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use App\Service\ResourceUploadService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use RuntimeException;

class ResourcesController extends AppController
{
    /**
     * Get teacher.
     *
     * @return mixed
     */
    private function getTeacher()
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Teachers')->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();
    }

    /**
     * Get teacher class options.
     *
     * @param mixed $teacherId Teacherid.
     */
    private function getTeacherClassOptions(int $teacherId): array
    {
        $classes = $this->fetchTable('Classes')->find()
            ->contain(['Courses'])
            ->where(['Classes.teacher_id' => $teacherId])
            ->orderBy(['Classes.class_code' => 'ASC'])
            ->all();

        $options = [];
        foreach ($classes as $c) {
            $options[$c->class_id] = $c->class_code . ' - ' . ($c->course ? $c->course->course_name : '');
        }

        return $options;
    }

    /**
     * Index.
     */
    public function index(): void
    {
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');

        $status = $this->request->getQuery('status', 'active');
        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $teacherClassIds = $this->fetchTable('Classes')->find()
            ->where(['Classes.teacher_id' => $teacher->teacher_id])
            ->all()
            ->extract('class_id')
            ->toArray();

        $resources = [];
        if (!empty($teacherClassIds)) {
            $resources = $resourcesTable->find()
                ->contain(['Classes' => ['Courses']])
                ->where([
                    'LearningResources.class_id IN' => $teacherClassIds,
                    'LearningResources.resource_status' => $status,
                ])
                ->orderBy(['LearningResources.uploaded_at' => 'DESC'])
                ->all();
        }

        $this->set(compact('resources', 'teacher', 'status'));
        $this->set('title', 'Manage Learning Resources');
    }

    /**
     * Archive.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function archive(?int $resourceId = null): Response
    {
        $this->request->allowMethod(['post']);
        $teacher = $this->getTeacher();
        $resource = $this->fetchTable('LearningResources')->find()
            ->where([
                'LearningResources.resource_id' => $resourceId,
                'LearningResources.uploaded_by_teacher_id' => $teacher->teacher_id,
            ])
            ->firstOrFail();

        $resource->resource_status = 'archived';
        if ($this->fetchTable('LearningResources')->save($resource)) {
            $this->Flash->success(__('Resource has been archived.'));
        } else {
            $this->Flash->error(__('Could not archive resource. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Restore.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function restore(?int $resourceId = null): Response
    {
        $this->request->allowMethod(['post']);
        $teacher = $this->getTeacher();
        $resource = $this->fetchTable('LearningResources')->find()
            ->where([
                'LearningResources.resource_id' => $resourceId,
                'LearningResources.uploaded_by_teacher_id' => $teacher->teacher_id,
            ])
            ->firstOrFail();

        $resource->resource_status = 'active';
        if ($this->fetchTable('LearningResources')->save($resource)) {
            $this->Flash->success(__('Resource has been restored.'));
        } else {
            $this->Flash->error(__('Could not restore resource. Please try again.'));
        }

        return $this->redirect(['action' => 'index', '?' => ['status' => 'archived']]);
    }

    /**
     * Add.
     */
    public function add(): ?Response
    {
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->newEmptyEntity();
        $uploadService = new ResourceUploadService();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $this->assertTeacherOwnsClass($teacher->teacher_id, (int)($data['class_id'] ?? 0));
            $resource = $resourcesTable->newEntity($this->buildTeacherPayload($data));
            $resource->uploaded_by_teacher_id = $teacher->teacher_id;

            $file = $this->request->getUploadedFile('file_upload');
            $uploadedFilePath = null;
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                try {
                    $uploadedFilePath = $uploadService->storeUploadedFile($file);
                    $resource->file_path = $uploadedFilePath;
                } catch (RuntimeException $exception) {
                    $resource->setError('file_upload', [$exception->getMessage()]);
                }
            }

            if (!$resource->hasErrors() && $resourcesTable->save($resource)) {
                $this->Flash->success(__('Resource has been added.'));

                return $this->redirect(['action' => 'index']);
            }

            if ($uploadedFilePath) {
                $uploadService->deleteStoredFile($uploadedFilePath);
            }
            if (!$resource->hasErrors()) {
                $this->Flash->error(__('Could not add resource. Please try again.'));
            }
        }

        $classOptions = $this->getTeacherClassOptions($teacher->teacher_id);
        $resourceTypes = [
            'document' => 'Document',
            'pdf' => 'PDF',
            'video' => 'Video',
            'image' => 'Image',
            'link' => 'External Link',
            'other' => 'Other',
        ];

        $this->set(compact('resource', 'classOptions', 'resourceTypes'));
        $this->set('title', 'Add Learning Resource');

        return null;
    }

    /**
     * Edit.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function edit(?int $resourceId = null): ?Response
    {
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');
        $uploadService = new ResourceUploadService();

        $resource = $resourcesTable->find()
            ->where([
                'LearningResources.resource_id' => $resourceId,
                'LearningResources.uploaded_by_teacher_id' => $teacher->teacher_id,
            ])
            ->firstOrFail();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $this->assertTeacherOwnsClass($teacher->teacher_id, (int)($data['class_id'] ?? $resource->class_id));
            $oldFilePath = $resource->file_path;
            $resource = $resourcesTable->patchEntity($resource, $this->buildTeacherPayload($data));
            $resource->uploaded_by_teacher_id = $teacher->teacher_id;

            $file = $this->request->getUploadedFile('file_upload');
            $uploadedFilePath = null;
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                try {
                    $uploadedFilePath = $uploadService->storeUploadedFile($file);
                    $resource->file_path = $uploadedFilePath;
                } catch (RuntimeException $exception) {
                    $resource->setError('file_upload', [$exception->getMessage()]);
                }
            }

            if (!$resource->hasErrors() && $resourcesTable->save($resource)) {
                if ($uploadedFilePath && $oldFilePath && $oldFilePath !== $uploadedFilePath) {
                    $uploadService->deleteStoredFile($oldFilePath);
                }
                $this->Flash->success(__('Resource has been updated.'));

                return $this->redirect(['action' => 'index']);
            }

            if ($uploadedFilePath) {
                $uploadService->deleteStoredFile($uploadedFilePath);
            }
            $this->Flash->error(__('Could not update resource. Please try again.'));
        }

        $classOptions = $this->getTeacherClassOptions($teacher->teacher_id);
        $resourceTypes = [
            'document' => 'Document',
            'pdf' => 'PDF',
            'video' => 'Video',
            'image' => 'Image',
            'link' => 'External Link',
            'other' => 'Other',
        ];

        $this->set(compact('resource', 'classOptions', 'resourceTypes'));
        $this->set('title', 'Edit Resource');

        return null;
    }

    /**
     * Download.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function download(?int $resourceId = null): Response
    {
        $teacher = $this->getTeacher();
        $resource = $this->getTeacherOwnedClassResource((int)$resourceId, (int)$teacher->teacher_id);

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

    /**
     * Delete.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function delete(?int $resourceId = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');

        $resource = $resourcesTable->find()
            ->where([
                'LearningResources.resource_id' => $resourceId,
                'LearningResources.uploaded_by_teacher_id' => $teacher->teacher_id,
            ])
            ->firstOrFail();

        $uploadService = new ResourceUploadService();
        if ($resourcesTable->delete($resource)) {
            $uploadService->deleteStoredFile($resource->file_path);
            $this->Flash->success(__('Resource has been deleted.'));
        } else {
            $this->Flash->error(__('Could not delete resource. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Assert teacher owns class.
     *
     * @param mixed $teacherId Teacherid.
     * @param mixed $classId Classid.
     */
    private function assertTeacherOwnsClass(int $teacherId, int $classId): void
    {
        $ownsClass = $this->fetchTable('Classes')->exists([
            'Classes.class_id' => $classId,
            'Classes.teacher_id' => $teacherId,
        ]);

        if (!$ownsClass) {
            throw new ForbiddenException('Class does not belong to this teacher.');
        }
    }

    /**
     * Get teacher owned class resource.
     *
     * @param mixed $resourceId Resourceid.
     * @param mixed $teacherId Teacherid.
     * @return mixed
     */
    private function getTeacherOwnedClassResource(int $resourceId, int $teacherId)
    {
        return $this->fetchTable('LearningResources')->find()
            ->contain(['Classes'])
            ->matching('Classes', function ($query) use ($teacherId) {
                return $query->where(['Classes.teacher_id' => $teacherId]);
            })
            ->where(['LearningResources.resource_id' => $resourceId])
            ->firstOrFail();
    }

    /**
     * Build teacher payload.
     *
     * @param mixed $data Data.
     */
    private function buildTeacherPayload(array $data): array
    {
        return [
            'class_id' => (int)($data['class_id'] ?? 0),
            'resource_name' => trim((string)($data['resource_name'] ?? '')),
            'resource_type' => (string)($data['resource_type'] ?? ''),
            'resource_url' => trim((string)($data['resource_url'] ?? '')),
            'resource_description' => trim((string)($data['resource_description'] ?? '')),
            'resource_status' => 'active',
        ];
    }
}
