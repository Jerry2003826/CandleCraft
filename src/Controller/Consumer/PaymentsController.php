<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Service\BookingCancellationService;
use App\Service\PaymentCheckoutService;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;
use RuntimeException;

class PaymentsController extends AppController
{
    private function isStripeConfigured(): bool
    {
        $key = Configure::read('Stripe.secret_key');

        return !empty($key) && $key !== 'sk_test_placeholder';
    }

    private function isDemoModeEnabled(): bool
    {
        return (bool)Configure::read('debug') && (bool)Configure::read('Payments.demo_mode');
    }

    private function getRequestBaseUrl(): string
    {
        $uri = $this->request->getUri();

        return $uri->getScheme() . '://' . $uri->getAuthority();
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
            ->contain(['Students', 'Classes' => ['Courses']])
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

        if ($this->request->is('post')) {
            try {
                $result = (new PaymentCheckoutService())->startCheckout($booking, [
                    'success_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'success', '?' => ['session_id' => '{CHECKOUT_SESSION_ID}']]),
                    'cancel_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
                    'portal_source' => 'consumer_portal',
                    'payer_id' => $identity?->get('user_id'),
                ]);

                return $this->handleCheckoutResult($result, $identity?->get('user_id'));
            } catch (RuntimeException $exception) {
                $this->Flash->error(__($exception->getMessage()));
            }
        }

        $stripeReady = $this->isStripeConfigured();
        $demoModeEnabled = $this->isDemoModeEnabled();
        $this->set(compact('booking', 'stripeReady', 'demoModeEnabled'));
        $this->set('title', 'Payment Portal');

        return null;
    }

    private function handleCheckoutResult(array $result, ?int $notifyUserId): ?Response
    {
        if (($result['kind'] ?? null) === 'redirect') {
            return $this->redirect((string)$result['redirectUrl']);
        }

        if (($result['kind'] ?? null) === 'completed') {
            $payment = $result['payment'];
            $booking = $result['booking'];

            if ($notifyUserId !== null) {
                $this->loadComponent('Notification');
                $className = $booking->class_entity?->course?->course_name ?? 'Class';
                $this->Notification->sendPaymentReceipt(
                    $notifyUserId,
                    $className,
                    (float)$payment->amount,
                );
            }

            $this->Flash->success(__('Demo payment completed successfully! Booking confirmed.'));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->info(__('Payment already completed for this booking.'));

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
        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentForIdentity($identity);
        $booking = $this->fetchTable('Bookings')->find()
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->voidPendingPaymentsForBooking((int)$booking->booking_id, [
                'portal_source' => 'consumer_portal',
            ]);
        } catch (RuntimeException $exception) {
            $this->Flash->error(__($exception->getMessage()));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

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
