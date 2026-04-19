<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\Payments\PaymentWebhookException;
use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;
use Throwable;

class PaymentCheckoutService
{
    private const SESSION_RECOVERY_ERROR = 'Unable to recover the current payment session. Please try again shortly.';
    private const SESSION_PENDING_ERROR = 'Your payment is still processing with Stripe. Please wait a moment and try again shortly.';

    private object $bookingsTable;
    private object $paymentsTable;
    private StripeCheckoutGatewayInterface $gateway;
    private PaymentConfirmationServiceInterface $paymentConfirmationService;
    private PendingPaymentDispositionService $pendingPaymentDispositionService;
    private StripeCheckoutSessionClassifier $sessionClassifier;

    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripeCheckoutGatewayInterface $gateway = null,
        ?PaymentConfirmationServiceInterface $paymentConfirmationService = null,
        ?PendingPaymentDispositionService $pendingPaymentDispositionService = null,
        ?StripeCheckoutSessionClassifier $sessionClassifier = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->bookingsTable = $locator->get('Bookings');
        $this->paymentsTable = $locator->get('Payments');
        $this->gateway = $gateway ?? $this->buildGateway();
        $this->paymentConfirmationService = $paymentConfirmationService ?? new PaymentConfirmationService($locator);
        $this->pendingPaymentDispositionService = $pendingPaymentDispositionService ?? new PendingPaymentDispositionService(
            $locator,
            $this->gateway
        );
        $this->sessionClassifier = $sessionClassifier ?? new StripeCheckoutSessionClassifier();
    }

    public function isStripeConfigured(): bool
    {
        return StripeConfiguration::isCheckoutReady();
    }

    public function isDemoModeEnabled(): bool
    {
        return (bool)Configure::read('debug') && (bool)Configure::read('Payments.demo_mode');
    }

    public function startCheckout(object $booking, array $context): array
    {
        if ($this->isZeroAmountBooking($booking)) {
            return $this->completeZeroAmountPayment((int)$booking->booking_id, $context);
        }

        if ($this->isStripeConfigured()) {
            return $this->startStripeCheckout((int)$booking->booking_id, $context);
        }

        if ($this->isDemoModeEnabled()) {
            return $this->completeDemoPayment((int)$booking->booking_id, $context);
        }

        throw new RuntimeException('Online payments are temporarily unavailable.');
    }

    private function completeZeroAmountPayment(int $bookingId, array $context): array
    {
        $connection = $this->paymentsTable->getConnection();

        return $connection->transactional(function () use ($bookingId, $context): array {
            $booking = $this->loadBookingForUpdate($bookingId);

            if (!$this->isZeroAmountBooking($booking)) {
                throw new RuntimeException('Booking amount changed during checkout. Please retry.');
            }

            $blockingPayment = $this->findPaymentsForUpdate($bookingId)
                ->where([
                    'Payments.payment_status IN' => ['paid', 'refund_required', 'partially_refunded', 'refunded'],
                ])
                ->orderBy(['Payments.payment_id' => 'DESC'])
                ->first();

            if ($blockingPayment) {
                if (
                    $blockingPayment->payment_status === 'paid' &&
                    in_array($booking->booking_status, ['confirmed', 'completed'], true)
                ) {
                    return ['kind' => 'already_paid'];
                }

                throw new RuntimeException('This booking already has a processed payment and requires manual review.');
            }

            if ($booking->booking_status === 'cancelled') {
                throw new RuntimeException('Cancelled bookings cannot be paid.');
            }

            $pendingPayments = $this->findPaymentsForUpdate($bookingId)
                ->where([
                    'Payments.payment_status' => 'pending',
                ])
                ->all();

            foreach ($pendingPayments as $pendingPayment) {
                $this->pendingPaymentDispositionService->voidPendingPayment($pendingPayment, 'zero_amount_override', [
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                    'zero_amount_checkout' => true,
                ]);
            }

            $referenceTimestamp = DateTime::now()->format('YmdHisv');
            $payment = $this->paymentsTable->newEntity([
                'booking_id' => $bookingId,
                'amount' => 0,
                'currency_code' => 'AUD',
                'payment_date' => DateTime::now(),
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'transaction_reference' => sprintf('ZERO-%d-%s', $bookingId, $referenceTimestamp),
                'notes' => PaymentNotes::merge(null, [
                    'zero_amount_checkout' => true,
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                ]),
            ]);
            $this->paymentsTable->saveOrFail($payment);

            $booking->booking_status = 'confirmed';
            $this->bookingsTable->saveOrFail($booking);

            return [
                'kind' => 'completed',
                'completed_reason' => 'zero_amount',
                'payment' => $payment,
                'booking' => $booking,
            ];
        });
    }

    private function startStripeCheckout(int $bookingId, array $context): array
    {
        $connection = $this->paymentsTable->getConnection();

        return $connection->transactional(function () use ($bookingId, $context): array {
            $booking = $this->loadBookingForUpdate($bookingId);

            if ($this->isZeroAmountBooking($booking)) {
                throw new RuntimeException('Booking amount changed during checkout. Please retry.');
            }

            $blockingPayment = $this->findPaymentsForUpdate($bookingId)
                ->where([
                    'Payments.payment_status IN' => ['paid', 'refund_required', 'partially_refunded', 'refunded'],
                ])
                ->orderBy(['Payments.payment_id' => 'DESC'])
                ->first();

            if ($blockingPayment) {
                if (
                    $blockingPayment->payment_status === 'paid' &&
                    in_array($booking->booking_status, ['confirmed', 'completed'], true)
                ) {
                    return ['kind' => 'already_paid'];
                }

                throw new RuntimeException('This booking already has a processed payment and requires manual review.');
            }

            if ($booking->booking_status === 'cancelled') {
                throw new RuntimeException('Cancelled bookings cannot be paid.');
            }

            $pendingPayments = $this->findPaymentsForUpdate($bookingId)
                ->where([
                    'Payments.payment_status' => 'pending',
                ])
                ->orderBy(['Payments.updated_at' => 'DESC', 'Payments.payment_id' => 'DESC'])
                ->all();

            foreach ($pendingPayments as $pendingPayment) {
                $inspection = $this->inspectPendingSession($pendingPayment);

                if (($inspection['kind'] ?? null) === 'reuse') {
                    return [
                        'kind' => 'redirect',
                        'redirectUrl' => (string)$inspection['url'],
                        'reused' => true,
                    ];
                }

                if (($inspection['kind'] ?? null) === 'already_completed') {
                    try {
                        $result = $this->paymentConfirmationService->confirmCheckoutSession(
                            $inspection['session'],
                            'checkout.session.recovered_from_checkout_scan',
                            'checkout_recovery'
                        );
                    } catch (PaymentWebhookException $exception) {
                        $this->logCheckoutFailure(
                            'Completed Stripe session could not be synchronized locally',
                            $booking,
                            $context,
                            (string)($inspection['session']->id ?? $pendingPayment->transaction_reference ?? ''),
                            $exception
                        );

                        throw new RuntimeException('This booking already has a processed payment and requires manual review.');
                    }

                    if (in_array($result, ['confirmed', 'idempotent'], true)) {
                        return ['kind' => 'already_paid'];
                    }

                    throw new RuntimeException('This booking already has a processed payment and requires manual review.');
                }

                if (($inspection['kind'] ?? null) === 'stale') {
                    $this->transitionPayment($pendingPayment, 'expired', [
                        'payment_resolution' => 'replaced_checkout',
                        'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                    ]);
                }

                if (($inspection['kind'] ?? null) === 'awaiting_payment') {
                    throw new RuntimeException(self::SESSION_PENDING_ERROR);
                }

                if (($inspection['kind'] ?? null) === 'inspection_failed') {
                    Log::warning('Unable to inspect Stripe checkout session during reuse scan: ' . json_encode([
                        'booking_id' => (int)$booking->booking_id,
                        'payment_id' => (int)($pendingPayment->payment_id ?? 0),
                        'session_id' => (string)($pendingPayment->transaction_reference ?? ''),
                        'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                        'error' => (string)($inspection['error'] ?? ''),
                    ]));

                    throw new RuntimeException(self::SESSION_RECOVERY_ERROR);
                }
            }

            $portalSource = $this->normalizePortalSource($context['portal_source'] ?? null);
            $checkoutAttemptId = $this->buildCheckoutAttemptId();
            $clientReferenceId = $this->buildClientReferenceId((int)$booking->booking_id, $checkoutAttemptId);
            $sessionMetadata = $this->buildStripeSessionMetadata($booking, $portalSource, $checkoutAttemptId);
            $session = $this->createStripeSession($booking, $context, $clientReferenceId, $sessionMetadata);

            $payment = $this->paymentsTable->newEntity([
                'booking_id' => $bookingId,
                'amount' => $booking->price_at_booking,
                'currency_code' => 'AUD',
                'payment_method' => 'online',
                'payment_status' => 'pending',
                'transaction_reference' => (string)$session->id,
                'notes' => PaymentNotes::merge(null, [
                    'stripe_checkout' => true,
                    'portal_source' => $portalSource,
                    'checkout_attempt_id' => $checkoutAttemptId,
                    'stripe_client_reference_id' => $clientReferenceId,
                ]),
            ]);

            try {
                $this->paymentsTable->saveOrFail($payment);
            } catch (Throwable $exception) {
                $this->expireSessionAfterPersistenceFailure((string)$session->id);
                $this->logCheckoutFailure('Failed to persist Stripe checkout session', $booking, $context, $session->id, $exception);

                throw new RuntimeException('Payment could not be initiated. Please try again later.');
            }

            return [
                'kind' => 'redirect',
                'redirectUrl' => (string)$session->url,
                'reused' => false,
            ];
        });
    }

    private function completeDemoPayment(int $bookingId, array $context): array
    {
        $connection = $this->paymentsTable->getConnection();

        return $connection->transactional(function () use ($bookingId, $context): array {
            $booking = $this->loadBookingForUpdate($bookingId);

            if ($booking->booking_status === 'cancelled') {
                throw new RuntimeException('Cancelled bookings cannot be paid.');
            }

            $blockingPayment = $this->findPaymentsForUpdate($bookingId)
                ->where([
                    'Payments.payment_status IN' => ['paid', 'refund_required', 'partially_refunded', 'refunded'],
                ])
                ->orderBy(['Payments.payment_id' => 'DESC'])
                ->first();

            if ($blockingPayment) {
                if (
                    $blockingPayment->payment_status === 'paid' &&
                    in_array($booking->booking_status, ['confirmed', 'completed'], true)
                ) {
                    return ['kind' => 'already_paid'];
                }

                throw new RuntimeException('This booking already has a processed payment and requires manual review.');
            }

            $pendingPayments = $this->findPaymentsForUpdate($bookingId)
                ->where([
                    'Payments.payment_status' => 'pending',
                ])
                ->all();

            foreach ($pendingPayments as $pendingPayment) {
                $this->pendingPaymentDispositionService->voidPendingPayment($pendingPayment, 'demo_payment_override', [
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                ]);
            }

            $payment = $this->paymentsTable->newEntity([
                'booking_id' => $bookingId,
                'amount' => $booking->price_at_booking,
                'currency_code' => 'AUD',
                'payment_method' => 'online',
                'payment_status' => 'paid',
                'payment_date' => DateTime::now(),
                'transaction_reference' => sprintf('DEMO-%d-%d', time(), $bookingId),
                'notes' => PaymentNotes::merge(null, [
                    'demo_payment' => true,
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                ]),
            ]);
            $this->paymentsTable->saveOrFail($payment);

            $booking->booking_status = 'confirmed';
            $this->bookingsTable->saveOrFail($booking);

            return [
                'kind' => 'completed',
                'completed_reason' => 'demo',
                'payment' => $payment,
                'booking' => $booking,
            ];
        });
    }

    private function inspectPendingSession(object $payment): array
    {
        $transactionReference = (string)($payment->transaction_reference ?? '');
        if ($transactionReference === '' || !$this->isStripeConfigured()) {
            return ['kind' => 'stale'];
        }

        try {
            $session = $this->gateway->retrieveCheckoutSession($transactionReference);
        } catch (Throwable $exception) {
            return [
                'kind' => 'inspection_failed',
                'error' => $exception->getMessage(),
            ];
        }

        $classification = $this->sessionClassifier->classify($session);
        if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_PAID) {
            return [
                'kind' => 'already_completed',
                'session' => $session,
            ];
        }

        if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_AWAITING_PAYMENT) {
            return [
                'kind' => 'awaiting_payment',
                'session' => $session,
            ];
        }

        if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_OPEN_NON_PAID && !empty($session->url)) {
            return [
                'kind' => 'reuse',
                'url' => (string)$session->url,
                'session' => $session,
            ];
        }

        if (($classification['state'] ?? null) === StripeCheckoutSessionClassifier::STATE_OPEN_UNKNOWN_PAYMENT_STATUS) {
            return [
                'kind' => 'inspection_failed',
                'error' => 'Stripe checkout session is still open with an unknown payment status.',
            ];
        }

        return ['kind' => 'stale'];
    }

    private function createStripeSession(
        object $booking,
        array $context,
        string $clientReferenceId,
        array $sessionMetadata,
    ): object
    {
        $courseName = $booking->class_entity?->course?->course_name ?? 'Class Booking';
        $amountInCents = (int)round((float)$booking->price_at_booking * 100);

        try {
            $session = $this->gateway->createCheckoutSession([
                'payment_method_types' => ['card'],
                'client_reference_id' => $clientReferenceId,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'aud',
                        'product_data' => [
                            'name' => $courseName . ' - ' . ($booking->class_entity?->class_code ?? ''),
                            'description' => 'Class booking',
                        ],
                        'unit_amount' => $amountInCents,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => (string)$context['success_url'],
                'cancel_url' => (string)$context['cancel_url'],
                'metadata' => $sessionMetadata,
                'payment_intent_data' => [
                    'description' => sprintf(
                        'Booking #%d',
                        (int)$booking->booking_id
                    ),
                    'metadata' => $sessionMetadata,
                ],
            ]);
        } catch (Throwable $exception) {
            $this->logCheckoutFailure('Stripe checkout session creation failed', $booking, $context, null, $exception);

            throw new RuntimeException('Payment could not be initiated. Please try again later.');
        }

        if (empty($session->id) || empty($session->url)) {
            $this->logCheckoutFailure('Stripe checkout session response was incomplete', $booking, $context, $session->id ?? null, null);

            throw new RuntimeException('Payment could not be initiated. Please try again later.');
        }

        return $session;
    }

    private function expireSessionAfterPersistenceFailure(string $sessionId): void
    {
        if ($sessionId === '' || !StripeConfiguration::canManageCheckoutSessions()) {
            return;
        }

        try {
            $this->gateway->expireCheckoutSession($sessionId);
        } catch (Throwable $exception) {
            Log::error('Unable to expire Stripe session after local persistence failure: ' . json_encode([
                'session_id' => $sessionId,
                'error' => $exception->getMessage(),
            ]));
        }
    }

    private function transitionPayment(object $payment, string $status, array $notes): void
    {
        $payment->payment_status = $status;
        $payment->notes = PaymentNotes::merge($payment->notes, $notes);
        $this->paymentsTable->saveOrFail($payment);
    }

    private function loadBookingForUpdate(int $bookingId): object
    {
        $query = $this->bookingsTable->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => $bookingId]);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query->firstOrFail();
    }

    private function findPaymentsForUpdate(int $bookingId)
    {
        $query = $this->paymentsTable->find()
            ->where(['Payments.booking_id' => $bookingId]);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query;
    }

    private function supportsRowLocking(): bool
    {
        return $this->paymentsTable->getConnection()->getDriver() instanceof Mysql;
    }

    private function buildCheckoutAttemptId(): string
    {
        return bin2hex(random_bytes(8));
    }

    private function buildClientReferenceId(int $bookingId, string $checkoutAttemptId): string
    {
        return sprintf('booking:%d:attempt:%s', $bookingId, $checkoutAttemptId);
    }

    private function buildStripeSessionMetadata(object $booking, string $portalSource, string $checkoutAttemptId): array
    {
        return [
            'booking_id' => (string)$booking->booking_id,
            'student_id' => (string)$booking->student_id,
            'portal_source' => $portalSource,
            'checkout_attempt_id' => $checkoutAttemptId,
        ];
    }

    private function normalizePortalSource(mixed $value): string
    {
        $source = strtolower(trim((string)$value));

        return in_array($source, [
            'consumer_portal',
            'parent_portal',
            'student_portal',
            'admin_portal',
            'service_test',
            'unknown',
        ], true) ? $source : 'unknown';
    }

    private function buildGateway(): StripeCheckoutGatewayInterface
    {
        $gatewayClass = (string)Configure::read('Payments.gateway_class', StripeCheckoutGateway::class);
        $gateway = new $gatewayClass();

        if (!$gateway instanceof StripeCheckoutGatewayInterface) {
            throw new RuntimeException(sprintf('Configured payment gateway "%s" must implement %s.', $gatewayClass, StripeCheckoutGatewayInterface::class));
        }

        return $gateway;
    }

    private function logCheckoutFailure(
        string $message,
        object $booking,
        array $context,
        ?string $sessionId,
        ?Throwable $exception,
    ): void {
        Log::error($message . ': ' . json_encode([
            'booking_id' => (int)$booking->booking_id,
            'student_id' => (int)($booking->student_id ?? 0),
            'payer_id' => $context['payer_id'] ?? null,
            'portal_source' => $context['portal_source'] ?? 'unknown',
            'stripe_session_id' => $sessionId,
            'error' => $exception?->getMessage(),
        ]));
    }

    private function isZeroAmountBooking(object $booking): bool
    {
        return round((float)($booking->price_at_booking ?? 0), 2) === 0.0;
    }
}
