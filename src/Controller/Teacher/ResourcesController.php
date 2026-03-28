<?php
declare(strict_types=1);

namespace App\Controller\Teacher;

use Cake\Http\Response;

class ResourcesController extends AppController
{
    private function getTeacher()
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Teachers')->find()
            ->where(['Teachers.user_id' => $identity?->get('user_id')])
            ->firstOrFail();
    }

    private function getTeacherClassOptions(int $teacherId): array
    {
        $classes = $this->fetchTable('Classes')->find()
            ->contain(['Courses'])
            ->where(['Classes.teacher_id' => $teacherId])
            ->order(['Classes.class_code' => 'ASC'])
            ->all();

        $options = [];
        foreach ($classes as $c) {
            $options[$c->class_id] = $c->class_code . ' - ' . ($c->course ? $c->course->course_name : '');
        }

        return $options;
    }

    public function index(): void
    {
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');

        $teacherClassIds = $this->fetchTable('Classes')->find()
            ->where(['Classes.teacher_id' => $teacher->teacher_id])
            ->all()
            ->extract('class_id')
            ->toArray();

        $resources = [];
        if (!empty($teacherClassIds)) {
            $resources = $resourcesTable->find()
                ->contain(['Classes' => ['Courses']])
                ->where(['LearningResources.class_id IN' => $teacherClassIds])
                ->order(['LearningResources.uploaded_at' => 'DESC'])
                ->all();
        }

        $this->set(compact('resources', 'teacher'));
        $this->set('title', 'My Resources');
    }

    public function add(): ?Response
    {
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');
        $resource = $resourcesTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $data = $this->request->getData();
            $data['uploaded_by_teacher_id'] = $teacher->teacher_id;
            $resource = $resourcesTable->newEntity($data);

            $file = $this->request->getUploadedFile('file_upload');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $uploadDir = WWW_ROOT . 'uploads' . DS . 'resources';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = time() . '_' . $file->getClientFilename();
                $file->moveTo($uploadDir . DS . $filename);
                $resource->file_path = 'uploads/resources/' . $filename;
            }

            if ($resourcesTable->save($resource)) {
                $this->Flash->success(__('Resource has been added.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('Could not add resource. Please try again.'));
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
        $this->set('title', 'Add Resource');

        return null;
    }

    public function edit(?int $resourceId = null): ?Response
    {
        $teacher = $this->getTeacher();
        $resourcesTable = $this->fetchTable('LearningResources');

        $resource = $resourcesTable->find()
            ->where([
                'LearningResources.resource_id' => $resourceId,
                'LearningResources.uploaded_by_teacher_id' => $teacher->teacher_id,
            ])
            ->firstOrFail();

        if ($this->request->is(['patch', 'post', 'put'])) {
            $resource = $resourcesTable->patchEntity($resource, $this->request->getData());

            $file = $this->request->getUploadedFile('file_upload');
            if ($file && $file->getError() === UPLOAD_ERR_OK) {
                $uploadDir = WWW_ROOT . 'uploads' . DS . 'resources';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = time() . '_' . $file->getClientFilename();
                $file->moveTo($uploadDir . DS . $filename);
                $resource->file_path = 'uploads/resources/' . $filename;
            }

            if ($resourcesTable->save($resource)) {
                $this->Flash->success(__('Resource has been updated.'));

                return $this->redirect(['action' => 'index']);
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

        if ($resourcesTable->delete($resource)) {
            $this->Flash->success(__('Resource has been deleted.'));
        } else {
            $this->Flash->error(__('Could not delete resource. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
