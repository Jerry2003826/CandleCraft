<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class BookingsController extends AppController
{
    public function index(): void
    {
        $bookingsTable = $this->fetchTable('Bookings');

        $stats = [
            'total' => $bookingsTable->find()->count(),
            'confirmed' => $bookingsTable->find()->where(['booking_status' => 'confirmed'])->count(),
            'pending' => $bookingsTable->find()->where(['booking_status' => 'pending'])->count(),
            'cancelled' => $bookingsTable->find()->where(['booking_status' => 'cancelled'])->count(),
        ];

        $query = $bookingsTable->find()
            ->contain(['Students', 'Classes' => ['Courses', 'Teachers'], 'Payments'])
            ->orderBy(['Bookings.booking_date' => 'DESC']);

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $query->where(['Bookings.booking_status' => $status]);
        }

        $bookings = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('bookings', 'status', 'stats'));
    }

    public function view(?string $id = null): void
    {
        $bookingsTable = $this->fetchTable('Bookings');
        $booking = $bookingsTable->get($id, contain: [
            'Students',
            'Classes' => ['Courses', 'Teachers'],
            'Payments',
            'AttendanceRecords',
        ]);

        $this->set('booking', $booking);
    }

    public function edit(?string $id = null)
    {
        $bookingsTable = $this->fetchTable('Bookings');
        $booking = $bookingsTable->get($id, contain: ['Students', 'Classes' => ['Courses']]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $booking = $bookingsTable->patchEntity($booking, $this->request->getData());
            if ($bookingsTable->save($booking)) {
                $this->Flash->success(__('The booking has been updated.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The booking could not be updated. Please try again.'));
        }

        $this->set(compact('booking'));
    }

    public function delete(?string $id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        $bookingsTable = $this->fetchTable('Bookings');
        $booking = $bookingsTable->get($id);
        if ($bookingsTable->delete($booking)) {
            $this->Flash->success(__('The booking has been deleted.'));
        } else {
            $this->Flash->error(__('The booking could not be deleted. Please try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }
}
