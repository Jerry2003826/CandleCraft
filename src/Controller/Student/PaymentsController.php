<?php
declare(strict_types=1);

namespace App\Controller\Student;

use App\Service\BookingCancellationService;
use App\Service\PaymentCheckoutService;
use App\Service\PaymentReceiptEmailService;
use App\Service\StripeConfiguration;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Routing\Router;
use RuntimeException;

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
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $bookings = $bookingsTable->find()
            ->where(['Bookings.student_id' => $student->student_id])
            ->contain([
                'Classes' => ['Courses'],
                'Payments',
            ])
            ->orderBy(['Bookings.booking_date' => 'DESC'])
            ->all();

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
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $bookingsTable = $this->fetchTable('Bookings');
        $paymentsTable = $this->fetchTable('Payments');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $booking = $bookingsTable->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

        // If this booking is linked to a parent account, payment must be authorized by parent.
        if (!empty($booking->parent_id)) {
            $this->Flash->warning(__(
                'Payment for this booking requires parent authorization. Please ask your parent to' .
                'complete payment in the Parent Portal.',
            ));

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
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
            $this->Flash->info(__(
                'Payment already completed for this booking.',
            ));

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
        }

        if ($this->request->is('post')) {
            try {
                $result = (new PaymentCheckoutService())->startCheckout($booking, [
                    // See PaymentsController (Consumer prefix) for why this
                    // uses a path segment instead of `?session_id=...`.
                    'success_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Student', 'controller' => 'Payments', 'action' => 'success'])
                        . '/{CHECKOUT_SESSION_ID}',
                    'cancel_url' => $this->getRequestBaseUrl()
                        . Router::url(['prefix' => 'Student', 'controller' => 'Payments', 'action' => 'cancel', $bookingId]),
                    'portal_source' => 'student_portal',
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
        $this->set(compact('booking', 'student', 'stripeReady', 'demoModeEnabled'));
        $this->set('title', 'Payment');

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
                $this->loadComponent('Notification');
                $className = $booking->class_entity?->course?->course_name ?? 'Class';
                $this->Notification->sendPaymentReceipt(
                    $notifyUserId,
                    $className,
                    (float)$payment->amount,
                );
            }
            $this->sendPaymentReceiptEmail((int)$payment->payment_id, 'Student');

            $message = ($result['completed_reason'] ?? null) === 'zero_amount'
                ? __('Free booking confirmed successfully.')
                : __('Demo payment completed successfully! Booking confirmed.');
            $this->Flash->success($message);

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->info(__(
            'Payment already completed for this booking.',
        ));

        return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
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
        $identity = $this->Authentication->getIdentity();
        $student = $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        if (!is_string($sessionId) || $sessionId === '') {
            $this->Flash->error(__(
                'Payment session could not be found.',
            ));

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
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

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
        }

        if ($payment->payment_status !== 'paid') {
            (new PaymentCheckoutService())->syncCheckoutSession($sessionId);
            $payment = $paymentsTable->get((int)$payment->payment_id);
        }

        if ($payment->payment_status === 'paid') {
            $this->sendPaymentReceiptEmail((int)$payment->payment_id, 'Student');
            $this->Flash->success(__(
                'Payment completed successfully!',
            ));
        } else {
            $this->Flash->info(__(
                'Payment received. Confirmation will appear shortly once Stripe finishes processing the webhook.',
            ));
        }
        $this->set('title', 'Payment Successful');

        return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Cancel.
     *
     * @param mixed $bookingId Bookingid.
     */
    public function cancel(?int $bookingId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $student = $this->fetchTable('Students')->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $booking = $this->fetchTable('Bookings')->find()
            ->where([
                'Bookings.booking_id' => $bookingId,
                'Bookings.student_id' => $student->student_id,
            ])
            ->firstOrFail();

        try {
            (new BookingCancellationService())->voidPendingPaymentsForBooking((int)$booking->booking_id, [
                'portal_source' => 'student_portal',
            ]);
        } catch (RuntimeException $exception) {
            $this->Flash->error(__(
                $exception->getMessage(),
            ));

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
        }

        $this->Flash->warning(__(
            'Payment was cancelled. Your booking is still pending.',
        ));

        return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
    }

    /**
     * Receipt.
     *
     * @param mixed $paymentId Paymentid.
     */
    public function receipt(?int $paymentId = null): ?Response
    {
        $identity = $this->Authentication->getIdentity();
        $studentsTable = $this->fetchTable('Students');
        $paymentsTable = $this->fetchTable('Payments');

        $student = $studentsTable->find()
            ->where(['Students.user_id' => $identity?->get('user_id')])
            ->firstOrFail();

        $payment = $paymentsTable->find()
            ->contain(['Bookings' => ['Classes' => ['Courses', 'Teachers']]])
            ->where([
                'Payments.payment_id' => $paymentId,
            ])
            ->firstOrFail();

        $bookingsTable = $this->fetchTable('Bookings');
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

            return $this->redirect(['controller' => 'Bookings', 'action' => 'index']);
        }

        $this->set(compact('payment'));
        $this->set('title', 'Payment Receipt');

        return null;
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
