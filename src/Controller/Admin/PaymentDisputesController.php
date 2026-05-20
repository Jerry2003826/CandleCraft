<?php
declare(strict_types=1);

namespace App\Controller\Admin;

class PaymentDisputesController extends AppController
{
    /**
     * Index.
     */
    public function index(): void
    {
        $status = (string)$this->request->getQuery('status', 'open');
        $allowedStatuses = ['open', 'closed', 'all'];
        if (!in_array($status, $allowedStatuses, true)) {
            $status = 'open';
        }

        $query = $this->fetchTable('PaymentDisputes')
            ->find()
            ->contain(['Payments' => ['Bookings' => ['Students']]])
            ->orderBy([
                'PaymentDisputes.evidence_due_by' => 'ASC',
                'PaymentDisputes.created_at' => 'DESC',
            ]);

        if ($status === 'open') {
            $query->where(['PaymentDisputes.status NOT IN' => ['won', 'lost', 'warning_closed']]);
        } elseif ($status === 'closed') {
            $query->where(['PaymentDisputes.status IN' => ['won', 'lost', 'warning_closed']]);
        }

        $disputes = $this->paginate($query, ['limit' => 25]);

        $this->set(compact('disputes', 'status'));
        $this->set('title', 'Payment Disputes');
    }
}
