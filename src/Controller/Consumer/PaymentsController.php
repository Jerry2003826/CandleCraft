<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;

class PaymentsController extends AppController
{
    public function beforeFilter(\Cake\Event\EventInterface $event): void
    {
        parent::beforeFilter($event);
        $this->Authentication->addUnauthenticatedActions(['webhook']);
    }

    private function isStripeConfigured(): bool
    {
        $key = Configure::read('Stripe.secret_key');

        return !empty($key) && $key !== 'sk_test_placeholder';
    }

    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();

        if ($this->userRole === 'parent') {
            $parent = $this->fetchTable('Parents')->find()
                ->where(['Parents.user_id' => $identity->get('user_id')])
                ->firstOrFail();
            $allowedStudentIds = $this->fetchTable('ParentStudents')->find()
                ->where(['ParentStudents.parent_id' => $parent->parent_id])
                ->all()
                ->extract('student_id')
                ->toArray();

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
        } else {
            $student = $this->fetchTable('Students')->find()
                ->where(['Students.user_id' => $identity->get('user_id')])
                ->firstOrFail();
            $bookings = $this->fetchTable('Bookings')->find()
                ->where(['Bookings.student_id' => $student->student_id])
                ->contain([
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
        $identity = $this->Authentication->getIdentity();
        $bookingsTable = $this->fetchTable('Bookings');
        $paymentsTable = $this->fetchTable('Payments');

        if ($this->userRole === 'parent') {
            $parent = $this->fetchTable('Parents')->find()
                ->where(['Parents.user_id' => $identity->get('user_id')])
                ->firstOrFail();
            $allowedStudentIds = $this->fetchTable('ParentStudents')->find()
                ->where(['ParentStudents.parent_id' => $parent->parent_id])
                ->all()
                ->extract('student_id')
                ->toArray();

            if (empty($allowedStudentIds)) {
                $this->Flash->error(__('No linked children found.'));

                return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
            }

            $booking = $bookingsTable->find()
                ->contain(['Students', 'Classes' => ['Courses']])
                ->where([
                    'Bookings.booking_id' => $bookingId,
                    'Bookings.student_id IN' => $allowedStudentIds,
                ])
                ->firstOrFail();
        } else {
            $student = $this->fetchTable('Students')->find()
                ->where(['Students.user_id' => $identity->get('user_id')])
                ->firstOrFail();

            $booking = $bookingsTable->find()
                ->contain(['Classes' => ['Courses']])
                ->where([
                    'Bookings.booking_id' => $bookingId,
                    'Bookings.student_id' => $student->student_id,
                ])
                ->firstOrFail();
        }

        $existingPayment = $paymentsTable->find()
            ->where([
                'Payments.booking_id' => $bookingId,
                'Payments.payment_status IN' => ['paid', 'pending'],
            ])
            ->first();

        if (
            $existingPayment &&
            $existingPayment->payment_status === 'paid' &&
            in_array($booking->booking_status, ['confirmed', 'completed'], true)
        ) {
            $this->Flash->info(__('Payment already completed for this booking.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $stripeReady = $this->isStripeConfigured();
        if ($this->request->is('post')) {
            if ($stripeReady) {
                return $this->processStripe($booking, $bookingId, $paymentsTable);
            }

            return $this->processDemo($booking, $paymentsTable, $bookingsTable, $identity);
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
                    . Router::url(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'success', '?' => ['session_id' => '{CHECKOUT_SESSION_ID}']]),
                'cancel_url' => $this->request->getSchemeAndHttpHost()
                    . Router::url(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
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
                'notes' => json_encode(['stripe_checkout' => true, 'source' => 'consumer_portal']),
            ]);
            $paymentsTable->save($payment);

            return $this->redirect($session->url);
        } catch (\Exception $e) {
            $this->Flash->error(__('Payment could not be initiated: {0}', $e->getMessage()));

            return null;
        }
    }

    private function processDemo($booking, $paymentsTable, $bookingsTable, $identity): ?Response
    {
        $payment = $paymentsTable->newEntity([
            'booking_id' => $booking->booking_id,
            'amount' => $booking->price_at_booking,
            'payment_method' => 'online',
            'payment_status' => 'paid',
            'payment_date' => new \Cake\I18n\DateTime(),
            'transaction_reference' => 'DEMO-' . time() . '-' . $booking->booking_id,
            'notes' => json_encode(['demo_payment' => true, 'source' => 'consumer_portal']),
        ]);
        $paymentsTable->save($payment);

        $booking->booking_status = 'confirmed';
        $bookingsTable->save($booking);

        $this->loadComponent('Notification');
        $className = $booking->class_entity?->course?->course_name ?? 'Class';
        $this->Notification->sendPaymentReceipt(
            $identity->get('user_id'),
            $className,
            (float)$payment->amount,
        );

        $this->Flash->success(__('Demo payment completed successfully! Booking confirmed.'));

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
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
                        'source' => 'consumer_portal',
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

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $this->Flash->warning(__('Payment was cancelled. Booking remains pending.'));

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function webhook(): Response
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getBody()->getContents();
        $sigHeader = $this->request->getHeaderLine('Stripe-Signature');
        $endpointSecret = Configure::read('Stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $endpointSecret);
        } catch (\UnexpectedValueException $e) {
            return $this->response->withStatus(400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return $this->response->withStatus(400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $paymentsTable = $this->fetchTable('Payments');
            $bookingsTable = $this->fetchTable('Bookings');

            $payment = $paymentsTable->find()
                ->where(['Payments.transaction_reference' => $session->id])
                ->first();

            if ($payment && $payment->payment_status !== 'paid') {
                $payment->payment_status = 'paid';
                $payment->payment_date = new \Cake\I18n\DateTime();
                $payment->notes = json_encode([
                    'stripe_checkout' => true,
                    'payment_intent' => $session->payment_intent,
                ]);
                $paymentsTable->save($payment);

                $booking = $bookingsTable->get($payment->booking_id);
                $booking->booking_status = 'confirmed';
                $bookingsTable->save($booking);
            }
        }

        return $this->response->withType('application/json')->withStringBody(json_encode(['received' => true]));
    }

    public function receipt(?int $paymentId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $paymentsTable = $this->fetchTable('Payments');
        $bookingsTable = $this->fetchTable('Bookings');

        $payment = $paymentsTable->find()
            ->contain(['Bookings' => ['Classes' => ['Courses', 'Teachers']]])
            ->where(['Payments.payment_id' => $paymentId])
            ->firstOrFail();

        if ($this->userRole === 'parent') {
            $parent = $this->fetchTable('Parents')->find()
                ->where(['Parents.user_id' => $identity->get('user_id')])
                ->firstOrFail();
            $allowedStudentIds = $this->fetchTable('ParentStudents')->find()
                ->where(['ParentStudents.parent_id' => $parent->parent_id])
                ->all()
                ->extract('student_id')
                ->toArray();

            $booking = $bookingsTable->find()
                ->where([
                    'Bookings.booking_id' => $payment->booking_id,
                    'Bookings.student_id IN' => $allowedStudentIds,
                ])
                ->first();
        } else {
            $student = $this->fetchTable('Students')->find()
                ->where(['Students.user_id' => $identity->get('user_id')])
                ->firstOrFail();

            $booking = $bookingsTable->find()
                ->where([
                    'Bookings.booking_id' => $payment->booking_id,
                    'Bookings.student_id' => $student->student_id,
                ])
                ->first();
        }

        if (!$booking) {
            $this->Flash->error(__('Access denied.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->set(compact('payment'));
        $this->set('title', 'Payment Receipt');

        return null;
    }
}
