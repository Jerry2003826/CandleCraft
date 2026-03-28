<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;

class PaymentsController extends AppController
{
    private function getParentEntity()
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();
    }

    private function getAllowedStudentIds(int $parentId): array
    {
        return $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parentId])
            ->all()
            ->extract('student_id')
            ->toArray();
    }

    private function isStripeConfigured(): bool
    {
        $key = Configure::read('Stripe.secret_key');

        return !empty($key) && $key !== 'sk_test_placeholder';
    }

    public function index(): void
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);

        $bookings = [];
        if (!empty($allowedStudentIds)) {
            $bookings = $this->fetchTable('Bookings')->find()
                ->where(['Bookings.student_id IN' => $allowedStudentIds])
                ->contain([
                    'Students',
                    'Classes' => ['Courses'],
                    'Payments',
                ])
                ->order(['Bookings.booking_date' => 'DESC'])
                ->all();
        }

        $this->set(compact('bookings'));
        $this->set('title', 'Payments');
    }

    public function process(?int $bookingId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);
        if (empty($allowedStudentIds)) {
            $this->Flash->error(__('No linked children found.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $bookingsTable = $this->fetchTable('Bookings');
        $paymentsTable = $this->fetchTable('Payments');
        $booking = $bookingsTable->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id IN' => $allowedStudentIds,
            ])
            ->firstOrFail();

        $existingPayment = $paymentsTable->find()
            ->where([
                'Payments.booking_id' => $bookingId,
                'Payments.payment_status IN' => ['paid', 'pending'],
            ])
            ->first();

        if ($existingPayment && $existingPayment->payment_status === 'paid') {
            $this->Flash->info(__('Payment already completed for this booking.'));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $stripeReady = $this->isStripeConfigured();
        if ($this->request->is('post')) {
            if ($stripeReady) {
                return $this->processStripe($booking, $bookingId, $paymentsTable);
            }

            return $this->processDemo($booking, $paymentsTable, $bookingsTable);
        }

        $this->set(compact('booking', 'stripeReady'));
        $this->set('title', 'Payment');

        return null;
    }

    private function processStripe($booking, int $bookingId, $paymentsTable): ?Response
    {
        \Stripe\Stripe::setApiKey(Configure::read('Stripe.secret_key'));

        $courseName = $booking->class_entity?->course?->course_name ?? 'Class Booking';
        $studentName = $booking->student?->student_name ?? 'Student';
        $amountInCents = (int)round((float)$booking->price_at_booking * 100);

        try {
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'aud',
                        'product_data' => [
                            'name' => $courseName . ' - ' . ($booking->class_entity?->class_code ?? ''),
                            'description' => 'Booking for ' . $studentName,
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->request->getSchemeAndHttpHost()
                    . Router::url(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'success', '?' => ['session_id' => '{CHECKOUT_SESSION_ID}']]),
                'cancel_url' => $this->request->getSchemeAndHttpHost()
                    . Router::url(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
                'metadata' => [
                    'booking_id' => $bookingId,
                    'student_id' => $booking->student_id,
                ],
            ]);

            $payment = $paymentsTable->newEntity([
                'booking_id' => $bookingId,
                'amount' => $booking->price_at_booking,
                'payment_method' => 'online',
                'payment_status' => 'pending',
                'transaction_reference' => $session->id,
                'notes' => json_encode(['stripe_checkout' => true, 'source' => 'parent_portal']),
            ]);
            $paymentsTable->save($payment);

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->Flash->error(__('Payment could not be initiated: {0}', $e->getMessage()));

            return null;
        }
    }

    private function processDemo($booking, $paymentsTable, $bookingsTable): ?Response
    {
        $payment = $paymentsTable->newEntity([
            'booking_id' => $booking->booking_id,
            'amount' => $booking->price_at_booking,
            'payment_method' => 'online',
            'payment_status' => 'paid',
            'payment_date' => new \Cake\I18n\DateTime(),
            'transaction_reference' => 'DEMO-PARENT-' . time() . '-' . $booking->booking_id,
            'notes' => json_encode(['demo_payment' => true, 'source' => 'parent_portal']),
        ]);
        $paymentsTable->save($payment);

        $booking->booking_status = 'confirmed';
        $bookingsTable->save($booking);

        $this->loadComponent('Notification');
        $className = $booking->class_entity?->course?->course_name ?? 'Class';
        $this->Notification->sendPaymentReceipt(
            $this->Authentication->getIdentity()->get('user_id'),
            $className,
            (float)$payment->amount,
        );

        $this->Flash->success(__('Demo payment completed successfully! Booking confirmed.'));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function success(): ?Response
    {
        $sessionId = $this->request->getQuery('session_id');
        $paymentsTable = $this->fetchTable('Payments');
        $bookingsTable = $this->fetchTable('Bookings');

        $payment = $paymentsTable->find()
            ->where(['Payments.transaction_reference' => $sessionId])
            ->first();

        if ($payment && $payment->payment_status !== 'paid') {
            \Stripe\Stripe::setApiKey(Configure::read('Stripe.secret_key'));
            try {
                $session = \Stripe\Checkout\Session::retrieve($sessionId);
                if ($session->payment_status === 'paid') {
                    $payment->payment_status = 'paid';
                    $payment->payment_date = new \Cake\I18n\DateTime();
                    $payment->notes = json_encode([
                        'stripe_checkout' => true,
                        'payment_intent' => $session->payment_intent,
                        'source' => 'parent_portal',
                    ]);
                    $paymentsTable->save($payment);

                    $booking = $bookingsTable->get($payment->booking_id);
                    $booking->booking_status = 'confirmed';
                    $bookingsTable->save($booking);
                }
            } catch (\Exception $e) {
            }
        }

        $this->Flash->success(__('Payment completed successfully!'));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $this->Flash->warning(__('Payment was cancelled. Booking remains pending.'));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }
}
