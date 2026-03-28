<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Http\Response;

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

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $resource = $resourcesTable->newEntity($data);

            $file = $this->request->getUploadedFile('file_upload');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $filename = time() . '_' . $file->getClientFilename();
                $file->moveTo(WWW_ROOT . 'uploads' . DS . 'resources' . DS . $filename);
                $resource->file_path = 'uploads/resources/' . $filename;
            }

            if ($resourcesTable->save($resource)) {
                $this->Flash->success(__('Resource has been added.'));
                return $this->redirect(['action' => 'index']);
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

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();
            $resource = $resourcesTable->patchEntity($resource, $data);

            $file = $this->request->getUploadedFile('file_upload');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $filename = time() . '_' . $file->getClientFilename();
                $file->moveTo(WWW_ROOT . 'uploads' . DS . 'resources' . DS . $filename);
                $resource->file_path = 'uploads/resources/' . $filename;
            }

            if ($resourcesTable->save($resource)) {
                $this->Flash->success(__('Resource has been updated.'));
                return $this->redirect(['action' => 'index']);
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

        if ($resourcesTable->delete($resource)) {
            $this->Flash->success(__('Resource has been deleted.'));
        } else {
            $this->Flash->error(__('Could not delete resource. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
