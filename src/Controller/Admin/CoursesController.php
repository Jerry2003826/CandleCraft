<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class CoursesController extends AppController
{
    public function index(): void
    {
        $coursesTable = $this->fetchTable('Courses');
        $query = $coursesTable->find()
            ->orderBy(['Courses.course_name' => 'ASC']);

        $courses = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('courses'));
    }

    public function add()
    {
        $coursesTable = $this->fetchTable('Courses');
        $course = $coursesTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $course = $coursesTable->patchEntity($course, $this->request->getData());
            if ($coursesTable->save($course)) {
                $this->Flash->success(__('The course has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The course could not be saved. Please try again.'));
        }

        $this->set(compact('course'));
    }

    public function edit(?string $id = null)
    {
        $coursesTable = $this->fetchTable('Courses');
        $course = $coursesTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $course = $coursesTable->patchEntity($course, $this->request->getData());
            if ($coursesTable->save($course)) {
                $this->Flash->success(__('The course has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The course could not be saved. Please try again.'));
        }

        $this->set(compact('course'));
    }

    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $coursesTable = $this->fetchTable('Courses');
        $course = $coursesTable->get($id);
        if ($coursesTable->delete($course)) {
            $this->Flash->success(__('The course has been deleted.'));
        } else {
            $this->Flash->error(__('The course could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
