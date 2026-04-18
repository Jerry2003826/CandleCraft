<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;

class PaymentsController extends AppController
{
    private function isStripeConfigured(): bool
    {
        $key = Configure::read('Stripe.secret_key');

        return !empty($key) && $key !== 'sk_test_placeholder';
    }

    public function index(): void
    {
        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentForIdentity($identity);
        $bookings = $this->fetchTable('Bookings')->find()
            ->where(['Bookings.student_id' => $student->student_id])
            ->contain([
                'Classes' => ['Courses'],
                'Payments',
            ])
            ->order(['Bookings.booking_date' => 'DESC'])
            ->all();

        $paymentProfilesTable = $this->fetchTable('PaymentProfiles');
        $paymentProfiles = $paymentProfilesTable->find()
            ->where(['PaymentProfiles.user_id' => $identity->get('user_id')])
            ->order([
                'PaymentProfiles.is_default' => 'DESC',
                'PaymentProfiles.updated_at' => 'DESC',
            ])
            ->all();

        $requestedProfileId = $this->request->getQuery('profile');
        $paymentProfile = null;
        if ($requestedProfileId !== null && ctype_digit((string)$requestedProfileId)) {
            $paymentProfile = $paymentProfilesTable->find()
                ->where([
                    'PaymentProfiles.payment_profile_id' => (int)$requestedProfileId,
                    'PaymentProfiles.user_id' => $identity->get('user_id'),
                ])
                ->first();
        }

        if (!$paymentProfile) {
            $paymentProfile = $paymentProfilesTable->newEntity([
                'billing_name' => (string)$student->student_name,
                'billing_email' => (string)$identity->get('email'),
                'billing_phone' => '',
                'billing_address_line1' => '',
                'billing_address_line2' => '',
                'billing_city' => '',
                'billing_state' => '',
                'billing_postcode' => '',
                'billing_country' => 'Australia',
                'preferred_payment_method' => 'card',
                'profile_status' => 'active',
                'is_default' => $paymentProfiles->count() === 0,
            ]);
        }

        $preferredPaymentMethods = [
            'card' => 'Card',
            'bank_transfer' => 'Bank Transfer',
            'cash' => 'Cash',
            'other' => 'Other',
        ];

        $this->set(compact('bookings', 'paymentProfiles', 'paymentProfile', 'preferredPaymentMethods'));
        $this->set('title', 'Payment Portal');
    }

    public function process(?int $bookingId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $bookingsTable = $this->fetchTable('Bookings');
        $paymentsTable = $this->fetchTable('Payments');
        $student = $this->getStudentForIdentity($identity);

        $booking = $bookingsTable->find()
            ->contain(['Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

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
        $this->set('title', 'Payment Portal');

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
        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentForIdentity($identity);

        if (!is_string($sessionId) || $sessionId === '') {
            $this->Flash->error(__('Payment session could not be found.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $payment = $paymentsTable->find()
            ->contain(['Bookings'])
            ->matching('Bookings', function ($query) use ($student) {
                return $query->where(['Bookings.student_id' => $student->student_id]);
            })
            ->where(['Payments.transaction_reference' => $sessionId])
            ->first();

        if (!$payment) {
            $this->Flash->error(__('Payment session not found for your account.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($payment->payment_status === 'paid') {
            $this->Flash->success(__('Payment completed successfully!'));
        } else {
            $this->Flash->info(__('Payment received. Confirmation will appear shortly once Stripe finishes processing the webhook.'));
        }

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
    }

    public function cancel(?int $bookingId = null): ?Response
    {
        $this->Flash->warning(__('Payment was cancelled. Booking remains pending.'));

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
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

        $student = $this->getStudentForIdentity($identity);

        $booking = $bookingsTable->find()
            ->where([
                'Bookings.booking_id' => $payment->booking_id,
                'Bookings.student_id' => $student->student_id,
            ])
            ->first();

        if (!$booking) {
            $this->Flash->error(__('Access denied.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->set(compact('payment'));
        $this->set('title', 'Payment Receipt');

        return null;
    }

    public function saveProfile(?int $paymentProfileId = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);

        $identity = $this->Authentication->getIdentity();
        $paymentProfilesTable = $this->fetchTable('PaymentProfiles');
        $profile = $paymentProfileId
            ? $paymentProfilesTable->find()
                ->where([
                    'PaymentProfiles.payment_profile_id' => $paymentProfileId,
                    'PaymentProfiles.user_id' => $identity->get('user_id'),
                ])
                ->firstOrFail()
            : $paymentProfilesTable->newEmptyEntity();

        $data = $this->request->getData();
        $profileData = [
            'user_id' => $identity->get('user_id'),
            'billing_name' => trim((string)($data['billing_name'] ?? '')),
            'billing_email' => trim((string)($data['billing_email'] ?? '')),
            'billing_phone' => trim((string)($data['billing_phone'] ?? '')),
            'billing_address_line1' => trim((string)($data['billing_address_line1'] ?? '')),
            'billing_address_line2' => trim((string)($data['billing_address_line2'] ?? '')),
            'billing_city' => trim((string)($data['billing_city'] ?? '')),
            'billing_state' => trim((string)($data['billing_state'] ?? '')),
            'billing_postcode' => trim((string)($data['billing_postcode'] ?? '')),
            'billing_country' => trim((string)($data['billing_country'] ?? '')),
            'preferred_payment_method' => (string)($data['preferred_payment_method'] ?? 'card'),
            'profile_status' => 'active',
            'is_default' => !empty($data['is_default']),
        ];

        $profile = $paymentProfilesTable->patchEntity($profile, $profileData);
        if ($paymentProfilesTable->save($profile)) {
            $hasAnyDefault = $paymentProfilesTable->find()
                ->where([
                    'PaymentProfiles.user_id' => $identity->get('user_id'),
                    'PaymentProfiles.profile_status' => 'active',
                    'PaymentProfiles.is_default' => true,
                ])
                ->count() > 0;

            if ($profile->is_default || !$hasAnyDefault) {
                $this->updateDefaultProfile((int)$identity->get('user_id'), (int)$profile->payment_profile_id);
            }

            $this->Flash->success(__('Payment details saved successfully.'));

            return $this->redirect(['action' => 'index']);
        }

        $this->Flash->error($this->extractFirstValidationError($profile->getErrors(), 'Could not save the payment details.'));

        return $this->redirect(['action' => 'index', '?' => ['profile' => $paymentProfileId ?: 'new']]);
    }

    public function setDefaultProfile(?int $paymentProfileId = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $paymentProfilesTable = $this->fetchTable('PaymentProfiles');
        $profile = $paymentProfilesTable->find()
            ->where([
                'PaymentProfiles.payment_profile_id' => $paymentProfileId,
                'PaymentProfiles.user_id' => $identity->get('user_id'),
                'PaymentProfiles.profile_status' => 'active',
            ])
            ->firstOrFail();

        $this->updateDefaultProfile((int)$identity->get('user_id'), (int)$profile->payment_profile_id);
        $this->Flash->success(__('Default payment details updated.'));

        return $this->redirect(['action' => 'index']);
    }

    public function archiveProfile(?int $paymentProfileId = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $identity = $this->Authentication->getIdentity();
        $paymentProfilesTable = $this->fetchTable('PaymentProfiles');
        $profile = $paymentProfilesTable->find()
            ->where([
                'PaymentProfiles.payment_profile_id' => $paymentProfileId,
                'PaymentProfiles.user_id' => $identity->get('user_id'),
            ])
            ->firstOrFail();

        $wasDefault = (bool)$profile->is_default;
        $profile->profile_status = 'archived';
        $profile->is_default = false;

        if ($paymentProfilesTable->save($profile)) {
            if ($wasDefault) {
                $replacement = $paymentProfilesTable->find()
                    ->where([
                        'PaymentProfiles.user_id' => $identity->get('user_id'),
                        'PaymentProfiles.profile_status' => 'active',
                    ])
                    ->order(['PaymentProfiles.updated_at' => 'DESC'])
                    ->first();

                if ($replacement) {
                    $this->updateDefaultProfile((int)$identity->get('user_id'), (int)$replacement->payment_profile_id);
                }
            }

            $this->Flash->success(__('Payment details archived.'));
        } else {
            $this->Flash->error(__('Could not archive the payment details.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    private function getStudentForIdentity($identity)
    {
        return $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->firstOrFail();
    }

    private function updateDefaultProfile(int $userId, int $paymentProfileId): void
    {
        $paymentProfilesTable = $this->fetchTable('PaymentProfiles');
        $paymentProfilesTable->updateAll(
            ['is_default' => false],
            ['PaymentProfiles.user_id' => $userId]
        );
        $paymentProfilesTable->updateAll(
            ['is_default' => true],
            [
                'PaymentProfiles.user_id' => $userId,
                'PaymentProfiles.payment_profile_id' => $paymentProfileId,
            ]
        );
    }

    private function extractFirstValidationError(array $errors, string $fallback): string
    {
        foreach ($errors as $fieldErrors) {
            if (!is_array($fieldErrors)) {
                continue;
            }

            foreach ($fieldErrors as $message) {
                if (is_string($message) && $message !== '') {
                    return $message;
                }
            }
        }

        return $fallback;
    }
}
