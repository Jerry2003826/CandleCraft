<?php
declare(strict_types=1);

namespace App\Controller\Consumer;

use App\Service\BookingCancellationService;
use App\Service\PaymentCheckoutService;
use App\Service\PaymentReceiptEmailService;
use App\Service\PaymentRefundRequestService;
use App\Service\StripeConfiguration;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Cake\Routing\Router;
use DateTimeInterface;
use RuntimeException;
use Throwable;

class PaymentsController extends AppController
{
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
        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentForIdentity($identity);
        $bookings = $this->fetchTable('Bookings')->find()
            ->where(['Bookings.student_id' => $student->student_id])
            ->contain([
                'Classes' => ['Courses'],
                'Payments' => function ($query) {
                    return $query->orderBy([
                        'Payments.payment_id' => 'ASC',
                    ]);
                },
            ])
            ->orderBy([
                'Bookings.booking_date' => 'DESC',
                'Bookings.booking_id' => 'DESC',
            ])
            ->all()
            ->toList();

        usort($bookings, [$this, 'comparePaymentBookings']);

        $this->set(compact('bookings'));
        $this->set('title', 'Payment Portal');
    }

    /**
     * Compare payment bookings.
     *
     * @param mixed $left Left.
     * @param mixed $right Right.
     */
    private function comparePaymentBookings(object $left, object $right): int
    {
        $leftRank = $this->getPaymentBookingRank($left);
        $rightRank = $this->getPaymentBookingRank($right);

        if ($leftRank !== $rightRank) {
            return $leftRank <=> $rightRank;
        }

        $dateCompare = $this->getPaymentBookingTimestamp($right) <=> $this->getPaymentBookingTimestamp($left);
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        return (int)($right->booking_id ?? 0) <=> (int)($left->booking_id ?? 0);
    }

    /**
     * Get payment booking rank.
     *
     * @param mixed $booking Booking.
     */
    private function getPaymentBookingRank(object $booking): int
    {
        $latestPaymentStatus = $this->getLatestPaymentStatus($booking);
        $hasPaidRecord = $this->hasPaidPayment($booking);
        $bookingStatus = (string)($booking->booking_status ?? '');

        if (
            !$hasPaidRecord
            && in_array($bookingStatus, ['pending', 'confirmed'], true)
            && !in_array($latestPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded', 'disputed'], true)
        ) {
            return 0;
        }

        if (in_array($latestPaymentStatus, ['failed', 'expired', 'voided'], true)) {
            return 1;
        }

        if (in_array($latestPaymentStatus, ['refund_required', 'disputed'], true)) {
            return 2;
        }

        if ($hasPaidRecord || in_array($latestPaymentStatus, ['paid', 'partially_refunded', 'refunded'], true)) {
            return 3;
        }

        return 4;
    }

    /**
     * Has paid payment.
     *
     * @param mixed $booking Booking.
     */
    private function hasPaidPayment(object $booking): bool
    {
        foreach ($booking->payments ?? [] as $payment) {
            if ((string)($payment->payment_status ?? '') === 'paid') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get latest payment status.
     *
     * @param mixed $booking Booking.
     */
    private function getLatestPaymentStatus(object $booking): string
    {
        $latestStatus = 'pending';

        foreach ($booking->payments ?? [] as $payment) {
            $latestStatus = (string)($payment->payment_status ?? 'pending');
        }

        return $latestStatus;
    }

    /**
     * Get payment booking timestamp.
     *
     * @param mixed $booking Booking.
     */
    private function getPaymentBookingTimestamp(object $booking): int
    {
        $date = $booking->class_entity?->start_datetime ?? $booking->booking_date ?? null;

        if ($date instanceof DateTimeInterface) {
            return $date->getTimestamp();
        }

        if ($date === null || $date === '') {
            return 0;
        }

        return strtotime((string)$date) ?: 0;
    }

    /**
     * Process.
     *
     * @param mixed $bookingId Bookingid.
     */
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
            $this->Flash->info(__(
                'Payment already completed for this booking.',
            ));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            try {
                $result = (new PaymentCheckoutService())->startCheckout($booking, [
                    // Pass the Stripe Checkout session id as a URL path
                    // segment instead of a query string so shared-host
                    // ModSecurity rules don't false-positive on the long
                    // `cs_test_*` / `cs_live_*` token (which looks like a
                    // SQL/RFI payload to the OWASP CRS family of rules).
                    'success_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'success'])
                        . '/{CHECKOUT_SESSION_ID}',
                    'cancel_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Consumer', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
                    'portal_source' => 'consumer_portal',
                    'payer_id' => $identity?->get('user_id'),
                    'payer_email' => $identity?->get('email'),
                ]);

                return $this->handleCheckoutResult($result, $identity?->get('user_id'));
            } catch (RuntimeException $exception) {
                $this->Flash->error(__(
                    $exception->getMessage(),
                ));
            }
        }

        $stripeReady = $this->isStripeConfigured();
        $demoModeEnabled = $this->isDemoModeEnabled();
        $this->set(compact('booking', 'stripeReady', 'demoModeEnabled'));
        $this->set('title', 'Payment Portal');

        return null;
    }

    /**
     * Handle checkout result.
     *
     * @param mixed $result Result.
     * @param mixed $notifyUserId Notifyuserid.
     */
    private function handleCheckoutResult(array $result, ?int $notifyUserId): ?Response
    {
        if (($result['kind'] ?? null) === 'redirect') {
            return $this->redirect((string)$result['redirectUrl']);
        }

        if (($result['kind'] ?? null) === 'completed') {
            $payment = $result['payment'];
            $booking = $result['booking'];

            if ($notifyUserId !== null) {
                try {
                    $this->loadComponent('Notification');
                    $className = $booking->class_entity?->course?->course_name ?? 'Class';
                    $this->Notification->sendPaymentReceipt(
                        $notifyUserId,
                        $className,
                        (float)$payment->amount,
                    );
                } catch (Throwable $exception) {
                    Log::warning('Payment receipt notification failed.', [
                        'payment_id' => $payment->payment_id ?? null,
                        'booking_id' => $booking->booking_id ?? null,
                        'portal' => 'consumer',
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
            $this->sendPaymentReceiptEmail((int)$payment->payment_id, 'Consumer');

            $message = ($result['completed_reason'] ?? null) === 'zero_amount'
                ? __('Free booking confirmed successfully.')
                : __('Demo payment completed successfully! Booking confirmed.');
            $this->Flash->success($message);

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->info(__(
            'Payment already completed for this booking.',
        ));

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Success.
     *
     * @param mixed $sessionToken Sessiontoken.
     */
    public function success(?string $sessionToken = null): ?Response
    {
        // Prefer the path segment (new Stripe success_url format), fall
        // back to the legacy `?session_id=...` query string so any in-flight
        // Checkout sessions created before the redeploy still complete.
        $sessionId = $sessionToken !== null && $sessionToken !== ''
            ? $sessionToken
            : $this->request->getQuery('session_id');
        $paymentsTable = $this->fetchTable('Payments');
        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentForIdentity($identity);

        if (!is_string($sessionId) || $sessionId === '') {
            $this->Flash->error(__(
                'Payment session could not be found.',
            ));

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
            $this->Flash->error(__(
                'Payment session not found for your account.',
            ));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        if ($payment->payment_status !== 'paid') {
            (new PaymentCheckoutService())->syncCheckoutSession($sessionId);
            $payment = $paymentsTable->get((int)$payment->payment_id);
        }

        if ($payment->payment_status === 'paid') {
            $this->sendPaymentReceiptEmail((int)$payment->payment_id, 'Consumer');
            $this->Flash->success(__(
                'Payment completed successfully!',
            ));
        } else {
            $this->Flash->info(__(
                'Payment received. Confirmation will appear shortly once Stripe finishes processing the webhook.',
            ));
        }

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Cancel.
     *
     * @param mixed $bookingId Bookingid.
     */
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
            (new BookingCancellationService())->cancelBooking((int)$booking->booking_id, [
                'portal_source' => 'consumer_portal',
            ]);
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->warning(__(
            'Payment was cancelled, so the pending booking was removed.',
        ));

        return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Receipt.
     *
     * @param mixed $paymentId Paymentid.
     */
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
            $this->Flash->error(__(
                'Access denied.',
            ));

            return $this->redirect(['prefix' => 'Consumer', 'controller' => 'Bookings', 'action' => 'index']);
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

        $identity = $this->Authentication->getIdentity();
        $student = $this->getStudentForIdentity($identity);
        $payment = $this->fetchTable('Payments')->find()
            ->matching('Bookings', function ($query) use ($student) {
                return $query->where(['Bookings.student_id' => $student->student_id]);
            })
            ->where(['Payments.payment_id' => $paymentId])
            ->firstOrFail();

        try {
            (new PaymentRefundRequestService())->requestRefund((int)$payment->payment_id, [
                'portal_source' => 'consumer_portal',
                'requested_by_user_id' => $identity?->get('user_id'),
                'recipient_email' => (string)($identity?->get('email') ?? ''),
                'recipient_name' => (string)($identity?->get('username') ?? ''),
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
     * Save profile.
     *
     * @param mixed $paymentProfileId Paymentprofileid.
     */
    public function saveProfile(?int $paymentProfileId = null): ?Response
    {
        $this->request->allowMethod(['post', 'put', 'patch']);
        $this->Flash->info(__(
            'Payment details are handled securely by our third-party checkout provider and are not saved here.',
        ));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Set default profile.
     *
     * @param mixed $paymentProfileId Paymentprofileid.
     */
    public function setDefaultProfile(?int $paymentProfileId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->Flash->info(__(
            'Saved payment details are no longer used because checkout is handled by a third-party provider.',
        ));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Archive profile.
     *
     * @param mixed $paymentProfileId Paymentprofileid.
     */
    public function archiveProfile(?int $paymentProfileId = null): ?Response
    {
        $this->request->allowMethod(['post']);
        $this->Flash->info(__(
            'Saved payment details are no longer used because checkout is handled by a third-party provider.',
        ));

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Get student for identity.
     *
     * @param mixed $identity Identity.
     * @return mixed
     */
    private function getStudentForIdentity(mixed $identity)
    {
        return $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity->get('user_id')])
            ->firstOrFail();
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
