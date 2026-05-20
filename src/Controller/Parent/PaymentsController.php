<?php
declare(strict_types=1);

namespace App\Controller\Parent;

use App\Service\BookingCancellationService;
use App\Service\PaymentCheckoutService;
use App\Service\PaymentReceiptEmailService;
use App\Service\PaymentRefundRequestService;
use App\Service\StripeConfiguration;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Routing\Router;
use RuntimeException;
use Throwable;

class PaymentsController extends AppController
{
    /**
     * Get parent entity.
     *
     * @return mixed
     */
    private function getParentEntity()
    {
        $identity = $this->Authentication->getIdentity();

        return $this->fetchTable('Parents')->find()
            ->where(['Parents.user_id' => $identity?->get('user_id')])
            ->firstOrFail();
    }

    /**
     * Get allowed student ids.
     *
     * @param mixed $parentId Parentid.
     */
    private function getAllowedStudentIds(int $parentId): array
    {
        return $this->fetchTable('ParentStudents')->find()
            ->where(['ParentStudents.parent_id' => $parentId])
            ->all()
            ->extract('student_id')
            ->toArray();
    }

    /**
     * Is stripe configured.
     */
    private function isStripeConfigured(): bool
    {
        return StripeConfiguration::isHostedCheckoutReady();
    }

    /**
     * Is demo mode enabled.
     */
    private function isDemoModeEnabled(): bool
    {
        return (bool)Configure::read('Payments.demo_mode');
    }

    /**
     * Get request base url.
     */
    private function getRequestBaseUrl(): string
    {
        $uri = $this->request->getUri();

        return $uri->getScheme() . '://' . $uri->getAuthority();
    }

    /**
     * Index.
     */
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
                ->orderBy(['Bookings.booking_date' => 'DESC'])
                ->all();
        }

        $this->set(compact('bookings'));
        $this->set('title', 'Payments');
    }

    /**
     * Process.
     *
     * @param mixed $bookingId Bookingid.
     */
    public function process(?int $bookingId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);
        if (empty($allowedStudentIds)) {
            $this->Flash->error(__(
                'No linked children found.',
            ));

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

        if (
            $existingPayment &&
            $existingPayment->payment_status === 'paid' &&
            in_array($booking->booking_status, ['confirmed', 'completed'], true)
        ) {
            $this->Flash->info(__(
                'Payment already completed for this booking.',
            ));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            try {
                $result = (new PaymentCheckoutService())->startCheckout($booking, [
                    // See PaymentsController (Consumer prefix) for why this
                    // uses a path segment instead of `?session_id=...`.
                    'success_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'success'])
                        . '/{CHECKOUT_SESSION_ID}',
                    'cancel_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Parent', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
                    'portal_source' => 'parent_portal',
                    'payer_id' => $this->Authentication->getIdentity()?->get('user_id'),
                    'payer_email' => $this->Authentication->getIdentity()?->get('email'),
                ]);

                return $this->handleCheckoutResult($result);
            } catch (RuntimeException $exception) {
                $this->Flash->error(__(
                    $exception->getMessage(),
                ));
            }
        }

        $stripeReady = $this->isStripeConfigured();
        $demoModeEnabled = $this->isDemoModeEnabled();
        $this->set(compact('booking', 'stripeReady', 'demoModeEnabled'));
        $this->set('title', 'Payment');

        return null;
    }

    /**
     * Handle checkout result.
     *
     * @param mixed $result Result.
     */
    private function handleCheckoutResult(array $result): ?Response
    {
        if (($result['kind'] ?? null) === 'redirect') {
            return $this->redirect((string)$result['redirectUrl']);
        }

        if (($result['kind'] ?? null) === 'completed') {
            $payment = $result['payment'];
            $booking = $result['booking'];

            try {
                $this->loadComponent('Notification');
                $className = $booking->class_entity?->course?->course_name ?? 'Class';
                $this->Notification->sendPaymentReceipt(
                    $this->Authentication->getIdentity()->get('user_id'),
                    $className,
                    (float)$payment->amount,
                );
            } catch (Throwable $exception) {
                Log::warning('Payment receipt notification failed.', [
                    'payment_id' => $payment->payment_id ?? null,
                    'booking_id' => $booking->booking_id ?? null,
                    'portal' => 'parent',
                    'error' => $exception->getMessage(),
                ]);
            }
            $this->sendPaymentReceiptEmail((int)$payment->payment_id, 'Parent');

            $message = ($result['completed_reason'] ?? null) === 'zero_amount'
                ? __('Free booking confirmed successfully.')
                : __('Demo payment completed successfully! Booking confirmed.');
            $this->Flash->success($message);

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->info(__(
            'Payment already completed for this booking.',
        ));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Success.
     *
     * @param mixed $sessionToken Sessiontoken.
     */
    public function success(?string $sessionToken = null): ?Response
    {
        $sessionId = $sessionToken !== null && $sessionToken !== ''
            ? $sessionToken
            : $this->request->getQuery('session_id');
        $paymentsTable = $this->fetchTable('Payments');
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);

        if (!is_string($sessionId) || $sessionId === '') {
            $this->Flash->error(__(
                'Payment session could not be found.',
            ));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $payment = $paymentsTable->find()
            ->contain(['Bookings'])
            ->matching('Bookings', function ($query) use ($allowedStudentIds) {
                return $query->where(['Bookings.student_id IN' => $allowedStudentIds]);
            })
            ->where(['Payments.transaction_reference' => $sessionId])
            ->first();

        if (!$payment) {
            $this->Flash->error(__(
                'Payment session not found for your account.',
            ));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($payment->payment_status !== 'paid') {
            (new PaymentCheckoutService())->syncCheckoutSession($sessionId);
            $payment = $paymentsTable->get((int)$payment->payment_id);
        }

        if ($payment->payment_status === 'paid') {
            $this->sendPaymentReceiptEmail((int)$payment->payment_id, 'Parent');
            $this->Flash->success(__(
                'Payment completed successfully!',
            ));
        } else {
            $this->Flash->info(__(
                'Payment received. Confirmation will appear shortly once Stripe finishes processing the webhook.',
            ));
        }

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Cancel.
     *
     * @param mixed $bookingId Bookingid.
     */
    public function cancel(?int $bookingId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);
        $booking = $this->fetchTable('Bookings')->find()
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id IN' => $allowedStudentIds,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->voidPendingPaymentsForBooking((int)$booking->booking_id, [
                'portal_source' => 'parent_portal',
            ]);
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->warning(__(
            'Payment was cancelled. Booking remains pending.',
        ));

        return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Receipt.
     *
     * @param mixed $paymentId Paymentid.
     */
    public function receipt(?int $paymentId = null): ?Response
    {
        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);

        if (empty($allowedStudentIds)) {
            $this->Flash->error(__(
                'No linked children found.',
            ));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $payment = $this->fetchTable('Payments')->find()
            ->contain(['Bookings' => ['Students', 'Classes' => ['Courses', 'Teachers']]])
            ->where(['Payments.payment_id' => $paymentId])
            ->firstOrFail();

        $studentId = (int)($payment->booking?->student_id ?? 0);
        if (!in_array($studentId, array_map('intval', $allowedStudentIds), true)) {
            $this->Flash->error(__(
                'Access denied.',
            ));

            return $this->redirect(['prefix' => 'Parent', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->set(compact('payment'));
        $this->set('title', 'Payment Receipt');

        return null;
    }

    /**
     * Request refund.
     *
     * @param mixed $paymentId Paymentid.
     */
    public function requestRefund(?int $paymentId = null): ?Response
    {
        $this->request->allowMethod(['post']);

        $parent = $this->getParentEntity();
        $allowedStudentIds = $this->getAllowedStudentIds((int)$parent->parent_id);

        if (empty($allowedStudentIds)) {
            $this->Flash->error(__(
                'No linked children found.',
            ));

            return $this->redirect(['action' => 'index']);
        }

        $payment = $this->fetchTable('Payments')->find()
            ->matching('Bookings', function ($query) use ($allowedStudentIds) {
                return $query->where(['Bookings.student_id IN' => $allowedStudentIds]);
            })
            ->where(['Payments.payment_id' => $paymentId])
            ->firstOrFail();

        try {
            (new PaymentRefundRequestService())->requestRefund((int)$payment->payment_id, [
                'portal_source' => 'parent_portal',
                'requested_by_user_id' => $this->Authentication->getIdentity()?->get('user_id'),
                'recipient_email' => (string)($this->Authentication->getIdentity()?->get('email') ?? ''),
                'recipient_name' => (string)($this->Authentication->getIdentity()?->get('username') ?? ''),
            ]);
            $this->Flash->success(__(
                'Refund request submitted. Our team will review it shortly.',
            ));
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Send payment receipt email.
     *
     * @param mixed $paymentId Paymentid.
     * @param mixed $portalPrefix Portalprefix.
     */
    private function sendPaymentReceiptEmail(int $paymentId, string $portalPrefix): void
    {
        $identity = $this->Authentication->getIdentity();
        (new PaymentReceiptEmailService())->sendForPayment($paymentId, [
            'portal_prefix' => $portalPrefix,
            'recipient_email' => (string)($identity?->get('email') ?? ''),
            'recipient_name' => (string)($identity?->get('username') ?? ''),
        ]);
    }
}
