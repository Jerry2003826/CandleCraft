<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Service\PaymentNotes;
use App\Service\PaymentRefundService;
use Cake\Database\Expression\IdentifierExpression;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\I18n\DateTime;
use RuntimeException;

class BookingsController extends AppController
{
    /**
     * Index.
     */
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

        $search = $this->request->getQuery('search');
        if ($search) {
            $query->where([
                'OR' => [
                    'Students.student_name LIKE' => "%{$search}%",
                    'Courses.course_name LIKE' => "%{$search}%",
                    'Classes.class_code LIKE' => "%{$search}%",
                ],
            ]);
        }

        $status = $this->request->getQuery('status');
        if ($status && in_array($status, ['pending', 'confirmed', 'completed', 'cancelled'])) {
            $query->where(['Bookings.booking_status' => $status]);
        } elseif ($status === 'refund_required') {
            $query
                ->matching('Payments', function ($q) {
                    return $q->where([
                        'OR' => [
                            ['Payments.payment_status' => 'refund_required'],
                            [
                                'Bookings.booking_status' => 'cancelled',
                                'Payments.payment_status IN' => ['paid', 'partially_refunded', 'disputed'],
                                $q->expr()->lt(
                                    'Payments.refunded_amount',
                                    new IdentifierExpression('Payments.amount'),
                                ),
                            ],
                        ],
                    ]);
                })
                ->distinct(['Bookings.booking_id']);
        }

        $bookings = $this->paginate($query, ['limit' => 20]);

        $this->set(compact('bookings', 'status', 'stats', 'search'));
    }

    /**
     * View.
     *
     * @param mixed $id Id.
     */
    public function view(?string $id = null): void
    {
        $bookingsTable = $this->fetchTable('Bookings');
        $booking = $bookingsTable->get($id, contain: [
            'Students',
            'Classes' => ['Courses', 'Teachers'],
            'Payments' => ['PaymentRefunds', 'PaymentDisputes'],
            'AttendanceRecords',
        ]);

        $this->set('booking', $booking);
    }

    /**
     * Edit.
     *
     * @param mixed $id Id.
     * @return mixed
     */
    public function edit(?string $id = null)
    {
        $bookingsTable = $this->fetchTable('Bookings');
        $booking = $bookingsTable->get($id, contain: ['Students', 'Classes' => ['Courses']]);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $booking = $bookingsTable->patchEntity($booking, $this->request->getData());
            if ($bookingsTable->save($booking)) {
                if ($booking->booking_status === 'cancelled') {
                    $this->markCancelledBookingPaymentsForRefundReview((int)$booking->booking_id);
                }

                $this->Flash->success(__(
                    'The booking has been updated.',
                ));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__(
                'The booking could not be updated. Please try again.',
            ));
        }

        $this->set(compact('booking'));
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

        $bookingsTable = $this->fetchTable('Bookings');
        $booking = $bookingsTable->get($id);
        if ($bookingsTable->delete($booking)) {
            $this->Flash->success(__(
                'The booking has been deleted.',
            ));
        } else {
            $this->Flash->error(__(
                'The booking could not be deleted. Please try again.',
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Refund payment.
     *
     * @param mixed $bookingId Bookingid.
     * @param mixed $paymentId Paymentid.
     * @return mixed
     */
    public function refundPayment(?int $bookingId = null, ?int $paymentId = null)
    {
        $this->request->allowMethod(['post']);

        $payment = $this->fetchTable('Payments')->find()
            ->where([
                'Payments.payment_id' => $paymentId,
                'Payments.booking_id' => $bookingId,
            ])
            ->firstOrFail();

        $amount = (float)$this->request->getData('amount');
        $reason = (string)$this->request->getData('reason', 'requested_by_customer');

        try {
            (new PaymentRefundService())->issueRefund((int)$payment->payment_id, $amount, $reason, $this->currentAdminId());
            $this->Flash->success(__(
                'Refund submitted to Stripe.',
            ));
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));
        }

        return $this->redirect(['action' => 'view', $bookingId]);
    }

    /**
     * Mark cancelled booking payments for refund review.
     *
     * @param mixed $bookingId Bookingid.
     */
    private function markCancelledBookingPaymentsForRefundReview(int $bookingId): void
    {
        $paymentsTable = $this->fetchTable('Payments');
        $payments = $paymentsTable->find()
            ->where([
                'Payments.booking_id' => $bookingId,
                'Payments.payment_status IN' => ['paid', 'partially_refunded', 'disputed'],
            ])
            ->all();

        foreach ($payments as $payment) {
            $remaining = round((float)$payment->amount - (float)$payment->refunded_amount, 2);
            if ($remaining <= 0) {
                continue;
            }

            $previousStatus = (string)$payment->payment_status;
            $payment->payment_status = 'refund_required';
            $payment->notes = PaymentNotes::merge($payment->notes, [
                'manual_review_required' => true,
                'refund_required' => true,
                'reason_code' => 'admin_cancelled_paid_booking',
                'previous_payment_status' => $previousStatus,
                'review_state' => 'awaiting_refund_after_admin_cancellation',
                'review_started_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
            ]);
            $paymentsTable->saveOrFail($payment);
        }
    }

    /**
     * Current admin id.
     */
    private function currentAdminId(): int
    {
        $identity = $this->request->getAttribute('identity');
        $userId = (int)($identity?->get('user_id') ?? 0);
        if ($userId <= 0) {
            throw new RuntimeException(
                'Admin identity is missing.',
            );
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
