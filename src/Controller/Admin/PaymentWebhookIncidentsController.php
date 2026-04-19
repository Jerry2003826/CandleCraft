<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\DateTime;
use RuntimeException;

class PaymentWebhookIncidentsController extends AppController
{
    public function index()
    {
        $status = (string)$this->request->getQuery('status', 'open');
        $allowedStatuses = ['open', 'resolved', 'ignored', 'all'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'open';
        }

        $query = $this->fetchTable('PaymentWebhookIncidents')
            ->find()
            ->contain(['Payments', 'Bookings'])
            ->orderBy(['PaymentWebhookIncidents.created_at' => 'DESC']);

        if ($status !== 'all') {
            $query->where(['PaymentWebhookIncidents.status' => $status]);
        }

        $incidents = $this->paginate($query, [
            'limit' => 25,
        ]);

        $this->set(compact('incidents', 'status'));
    }

    public function resolve(int $id)
    {
        return $this->updateStatus($id, 'resolved');
    }

    public function ignore(int $id)
    {
        return $this->updateStatus($id, 'ignored');
    }

    private function updateStatus(int $id, string $status)
    {
        $this->request->allowMethod(['post']);

        $incidentsTable = $this->fetchTable('PaymentWebhookIncidents');
        $incident = $incidentsTable->get($id);
        $incident->status = $status;
        $incident->resolved_at = DateTime::now();
        $incident->resolved_by_admin_id = $this->currentAdminId();

        $incidentsTable->saveOrFail($incident);

        $this->Flash->success(__('Webhook incident marked as {0}.', $status));

        return $this->redirect(['action' => 'index']);
    }

    private function currentAdminId(): int
    {
        $identity = $this->request->getAttribute('identity');
        $userId = (int)($identity?->get('user_id') ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException('Admin identity is missing.');
        }

        $admin = $this->fetchTable('Admins')
            ->find()
            ->select(['admin_id'])
            ->where(['Admins.user_id' => $userId])
            ->first();

        if ($admin === null) {
            throw new RecordNotFoundException('Admin profile not found.');
        }

        return (int)$admin->admin_id;
    }
}
