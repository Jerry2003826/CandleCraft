<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\ResourceUploadService;
use Cake\Http\Response;
use RuntimeException;

class ResourcesController extends AppController
{
    public function index(): void
    {
        $resourcesTable = $this->fetchTable('LearningResources');
        $query = $resourcesTable->find()
            ->contain(['Classes' => ['Courses']])
            ->order(['LearningResources.uploaded_at' => 'DESC']);

        $filter = $this->request->getQuery('class_id');
        if ($filter) {
            $query->where(['LearningResources.class_id' => $filter]);
        }

        $resources = $query->all();

        $classesTable = $this->fetchTable('Classes');
        $classes = $classesTable->find()
            ->contain(['Courses'])
            ->order(['Classes.class_code' => 'ASC'])
            ->all();

        $this->set(compact('resources', 'classes', 'filter'));
        $this->set('title', 'Learning Resources');
    }

    public function add(): ?Response
    {
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->newEmptyEntity();
        $uploadService = new ResourceUploadService();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $resource = $resourcesTable->newEntity($data);

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

    public function edit(?int $resourceId = null): ?Response
    {
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->get($resourceId);
        $uploadService = new ResourceUploadService();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $oldFilePath = $resource->file_path;
            $resource = $resourcesTable->patchEntity($resource, $data);

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
}
