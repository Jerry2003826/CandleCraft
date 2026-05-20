<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class CoursesController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $coursesTable = $this->fetchTable('Courses');
        $query = $coursesTable->find()
            ->orderBy(['Courses.course_name' => 'ASC']);

        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where([
                'OR' => [
                    'Courses.course_name LIKE' => "%{$search}%",
                    'Courses.course_type LIKE' => "%{$search}%",
                ],
            ]);
        }

        $courses = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('courses', 'search'));
    }

    /**
     * Add.
     *
     * @return mixed
     */
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

    /**
     * Edit.
     *
     * @param mixed $id Id.
     * @return mixed
     */
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

    /**
     * Delete.
     *
     * @param mixed $id Id.
     * @return mixed
     */
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
