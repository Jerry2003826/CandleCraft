<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ResourceUploadService;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use RuntimeException;

class ResourcesController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $status = $this->request->getQuery('status', 'active');
        if (!in_array($status, ['active', 'archived'], true)) {
            $status = 'active';
        }

        $resourcesTable = $this->fetchTable('LearningResources');
        $query = $resourcesTable->find()
            ->contain(['Classes' => ['Courses']])
            ->where(['LearningResources.resource_status' => $status])
            ->orderBy(['LearningResources.uploaded_at' => 'DESC']);

        $filter = $this->request->getQuery('class_id');
        if ($filter) {
            $query->where(['LearningResources.class_id' => $filter]);
        }

        $resources = $query->all();

        $classesTable = $this->fetchTable('Classes');
        $classes = $classesTable->find()
            ->contain(['Courses'])
            ->orderBy(['Classes.class_code' => 'ASC'])
            ->all();

        $this->set(compact('resources', 'classes', 'filter', 'status'));
        $this->set('title', 'Learning Resources');
    }

    /**
     * Archive.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function archive(?int $resourceId = null): Response
    {
        $this->request->allowMethod(['post']);
        $resource = $this->fetchTable('LearningResources')->get($resourceId);
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
        $resource = $this->fetchTable('LearningResources')->get($resourceId);
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
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->newEmptyEntity();
        $uploadService = new ResourceUploadService();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $resource = $resourcesTable->newEntity($this->buildAdminPayload($data), [
                'accessibleFields' => [
                    'resource_status' => true,
                ],
            ]);

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
            $this->Flash->error(__('Could not add resource. Please try again.'));
        }

        $classesTable = $this->fetchTable('Classes');
        $classOptions = [];
        foreach ($classesTable->find()->contain(['Courses'])->all() as $c) {
            $classOptions[$c->class_id] = $c->class_code . ' - ' . ($c->course ? $c->course->course_name : '');
        }

        $resourceTypes = [
            'document' => 'Document',
            'pdf' => 'PDF',
            'video' => 'Video',
            'image' => 'Image',
            'link' => 'External Link',
            'other' => 'Other',
        ];

        $this->set(compact('resource', 'classOptions', 'resourceTypes'));
        $this->set('title', 'Add Resource');

        return null;
    }

    /**
     * Edit.
     *
     * @param mixed $resourceId Resourceid.
     */
    public function edit(?int $resourceId = null): ?Response
    {
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->get($resourceId);
        $uploadService = new ResourceUploadService();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $oldFilePath = $resource->file_path;
            $resource = $resourcesTable->patchEntity($resource, $this->buildAdminPayload($data), [
                'accessibleFields' => [
                    'resource_status' => true,
                ],
            ]);

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

        $classesTable = $this->fetchTable('Classes');
        $classOptions = [];
        foreach ($classesTable->find()->contain(['Courses'])->all() as $c) {
            $classOptions[$c->class_id] = $c->class_code . ' - ' . ($c->course ? $c->course->course_name : '');
        }

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
        $resource = $this->fetchTable('LearningResources')->get($resourceId);
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
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->get($resourceId);
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
     * Build admin payload.
     *
     * @param mixed $data Data.
     */
    private function buildAdminPayload(array $data): array
    {
        return [
            'class_id' => (int)($data['class_id'] ?? 0),
            'resource_name' => trim((string)($data['resource_name'] ?? '')),
            'resource_type' => (string)($data['resource_type'] ?? ''),
            'resource_url' => trim((string)($data['resource_url'] ?? '')),
            'resource_description' => trim((string)($data['resource_description'] ?? '')),
            'resource_status' => (string)($data['resource_status'] ?? 'active'),
        ];
    }
}
