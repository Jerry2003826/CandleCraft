<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorInterface;
use LogicException;
use Throwable;

class PaymentConfirmationService implements PaymentConfirmationServiceInterface
{
    private object $paymentsTable;
    private object $bookingsTable;
    private StripePaymentDetailsCollector $stripeDetailsCollector;
    private PaymentReceiptEmailService $paymentReceiptEmailService;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @param mixed $stripeDetailsCollector Stripedetailscollector.
     * @param mixed $paymentReceiptEmailService Paymentreceiptemailservice.
     * @return mixed
     */
    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripePaymentDetailsCollector $stripeDetailsCollector = null,
        ?PaymentReceiptEmailService $paymentReceiptEmailService = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->bookingsTable = $locator->get('Bookings');
        $this->stripeDetailsCollector = $stripeDetailsCollector ?? new StripePaymentDetailsCollector();
        $this->paymentReceiptEmailService = $paymentReceiptEmailService ?? new PaymentReceiptEmailService($locator);
    }

    /**
     * Confirm checkout session.
     *
     * @param mixed $session Session.
     * @param mixed $eventType Eventtype.
     * @param mixed $confirmationSource Confirmationsource.
     */
    public function confirmCheckoutSession(
        object $session,
        string $eventType = 'checkout.session.completed',
        string $confirmationSource = 'stripe_webhook',
    ): string {
        $transactionReference = (string)($session->id ?? '');
        if ($transactionReference === '') {
            throw new NonRetriableWebhookException('Stripe session id is missing.', [
                'session_id' => '',
                'reason_code' => 'missing_session_id',
            ]);
        }

        $connection = $this->paymentsTable->getConnection();
        $paidPaymentId = null;
        $result = $connection->transactional(function () use (
            $session,
            $transactionReference,
            $eventType,
            $confirmationSource,
            &$paidPaymentId,
        ): string|PaymentWebhookException {
            $paymentPreview = $this->paymentsTable->find()
                ->select(['payment_id', 'booking_id'])
                ->where([
                    'OR' => [
                        ['Payments.transaction_reference' => $transactionReference],
                        ['Payments.stripe_session_id' => $transactionReference],
                    ],
                ])
                ->first();

            if (!$paymentPreview) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => null,
                    'booking_id' => null,
                    'reason_code' => 'payment_not_found',
                ]);
            }

            try {
                $booking = $this->loadBookingForUpdate((int)$paymentPreview->booking_id);
            } catch (RecordNotFoundException $exception) {
                return new ManualReviewWebhookException('Booking record not found for payment.', [
                    'session_id' => $transactionReference,
                    'payment_id' => (int)$paymentPreview->payment_id,
                    'booking_id' => (int)$paymentPreview->booking_id,
                    'reason_code' => 'booking_not_found',
                ], previous: $exception);
            }

            $payment = $this->loadPaymentForUpdate($transactionReference, (int)$paymentPreview->payment_id);
            if ($payment === null) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => (int)$paymentPreview->payment_id,
                    'booking_id' => (int)$paymentPreview->booking_id,
                    'reason_code' => 'payment_not_found_after_lock',
                ]);
            }

            $context = [
                'session_id' => $transactionReference,
                'payment_id' => (int)$payment->payment_id,
                'booking_id' => (int)$payment->booking_id,
            ];
            $localPaymentStatus = (string)$payment->payment_status;
            $sessionPaymentStatus = strtolower((string)($session->payment_status ?? ''));
            $eventContext = $this->withEventContext([], $eventType, $confirmationSource);

            if (in_array($localPaymentStatus, ['voided', 'expired'], true)) {
                if ($sessionPaymentStatus === 'paid') {
                    $this->markPaymentForRefundReview(
                        $payment,
                        $session,
                        'completed_after_local_payment_voided_or_expired',
                        null,
                        $this->withEventContext([
                            'local_payment_status' => $localPaymentStatus,
                            'stripe_payment_status' => $sessionPaymentStatus,
                            'funds_captured' => true,
                            'refund_required' => true,
                        ], $eventType, $confirmationSource),
                    );

                    return new ManualReviewWebhookException(
                        'A completed Stripe session arrived for a locally voided or expired payment.',
                        $context + [
                            'reason_code' => 'completed_after_local_payment_voided_or_expired',
                            'local_payment_status' => $localPaymentStatus,
                            'stripe_payment_status' => $sessionPaymentStatus,
                        ],
                    );
                }

                $this->markPaymentForReviewWithoutRefundRequirement(
                    $payment,
                    $session,
                    'completed_after_local_payment_voided_or_expired_without_paid_status',
                    null,
                    $this->withEventContext([
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                        'funds_captured' => false,
                        'refund_required' => false,
                        'review_state' => 'awaiting_payment_terminal_event',
                    ], $eventType, $confirmationSource),
                );

                return new ManualReviewWebhookException(
                    'A completed Stripe session arrived for a locally voided or expired payment without a paid terminal state.',
                    $context + [
                        'reason_code' => 'completed_after_local_payment_voided_or_expired_without_paid_status',
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ],
                );
            }

            if ($localPaymentStatus === 'refund_required') {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'completed_after_refund_or_review_state',
                    null,
                    $this->withEventContext([
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ], $eventType, $confirmationSource),
                );

                return new ManualReviewWebhookException(
                    'A completed Stripe session arrived for a payment already in refund/manual-review state.',
                    $context + [
                        'reason_code' => 'completed_after_refund_or_review_state',
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ],
                );
            }

            if (in_array($localPaymentStatus, ['refunded', 'partially_refunded'], true)) {
                $this->appendRefundLifecycleAudit(
                    $payment,
                    $session,
                    $transactionReference,
                    $eventType,
                    $confirmationSource,
                    $localPaymentStatus,
                );

                return 'idempotent';
            }

            if (!in_array($localPaymentStatus, ['pending', 'paid'], true)) {
                if ($sessionPaymentStatus === 'paid') {
                    $this->markPaymentForRefundReview(
                        $payment,
                        $session,
                        'unexpected_local_payment_status',
                        null,
                        $this->withEventContext([
                            'local_payment_status' => $localPaymentStatus,
                            'stripe_payment_status' => $sessionPaymentStatus,
                            'funds_captured' => true,
                        ], $eventType, $confirmationSource),
                    );
                } else {
                    $this->markPaymentForReview(
                        $payment,
                        $session,
                        'unexpected_local_payment_status',
                        null,
                        $this->withEventContext([
                            'local_payment_status' => $localPaymentStatus,
                            'stripe_payment_status' => $sessionPaymentStatus,
                            'funds_captured' => false,
                            'refund_required' => false,
                            'review_state' => 'awaiting_payment_terminal_event',
                        ], $eventType, $confirmationSource),
                    );
                }

                return new ManualReviewWebhookException(
                    'Payment status is incompatible with automatic webhook confirmation.',
                    $context + [
                        'reason_code' => 'unexpected_local_payment_status',
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ],
                );
            }

            if ($sessionPaymentStatus !== 'paid') {
                $this->markPaymentForReviewWithoutRefundRequirement(
                    $payment,
                    $session,
                    'checkout_completed_without_paid_status',
                    null,
                    $this->withEventContext([
                        'stripe_payment_status' => $sessionPaymentStatus,
                        'funds_captured' => false,
                        'refund_required' => false,
                        'review_state' => 'awaiting_payment_confirmation',
                    ], $eventType, $confirmationSource),
                );

                return new ManualReviewWebhookException(
                    'Checkout session completed but payment_status is not paid.',
                    $context + [
                        'reason_code' => 'checkout_completed_without_paid_status',
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ],
                );
            }

            $metadataBookingId = (string)($session->metadata->booking_id ?? '');
            if ($metadataBookingId === '') {
                $this->markPaymentForRefundReview(
                    $payment,
                    $session,
                    'missing_booking_metadata',
                    null,
                    $eventContext + ['funds_captured' => true],
                );

                return new NonRetriableWebhookException('Stripe booking metadata is missing.', $context + [
                    'reason_code' => 'missing_booking_metadata',
                ]);
            }

            if (!ctype_digit($metadataBookingId)) {
                $this->markPaymentForRefundReview(
                    $payment,
                    $session,
                    'invalid_booking_metadata',
                    null,
                    $eventContext + ['funds_captured' => true],
                );

                return new NonRetriableWebhookException('Stripe booking metadata is invalid.', $context + [
                    'reason_code' => 'invalid_booking_metadata',
                ]);
            }

            if ((int)$metadataBookingId !== (int)$payment->booking_id) {
                $this->markPaymentForRefundReview(
                    $payment,
                    $session,
                    'booking_metadata_mismatch',
                    null,
                    $eventContext + ['funds_captured' => true],
                );

                return new NonRetriableWebhookException('Stripe booking metadata does not match the local payment.', $context + [
                    'reason_code' => 'booking_metadata_mismatch',
                ]);
            }

            $clientReferenceId = trim((string)($session->client_reference_id ?? ''));
            if ($clientReferenceId !== '') {
                $clientReferenceBookingId = $this->extractBookingIdFromClientReferenceId($clientReferenceId);
                if ($clientReferenceBookingId === null) {
                    $this->markPaymentForRefundReview(
                        $payment,
                        $session,
                        'invalid_client_reference_id',
                        null,
                        $eventContext + [
                            'funds_captured' => true,
                            'client_reference_id' => $clientReferenceId,
                        ],
                    );

                    return new NonRetriableWebhookException('Stripe client reference is invalid.', $context + [
                        'reason_code' => 'invalid_client_reference_id',
                        'client_reference_id' => $clientReferenceId,
                    ]);
                }

                if ($clientReferenceBookingId !== (int)$payment->booking_id) {
                    $this->markPaymentForRefundReview(
                        $payment,
                        $session,
                        'client_reference_id_mismatch',
                        null,
                        $eventContext + [
                            'funds_captured' => true,
                            'client_reference_id' => $clientReferenceId,
                        ],
                    );

                    return new NonRetriableWebhookException('Stripe client reference does not match the local payment.', $context + [
                        'reason_code' => 'client_reference_id_mismatch',
                        'client_reference_id' => $clientReferenceId,
                    ]);
                }
            }

            $expectedAmount = (int)round((float)$payment->amount * 100);
            if (isset($session->amount_total) && (int)$session->amount_total !== $expectedAmount) {
                $this->markPaymentForRefundReview(
                    $payment,
                    $session,
                    'amount_mismatch',
                    null,
                    $eventContext + ['funds_captured' => true],
                );

                return new NonRetriableWebhookException('Stripe amount does not match the local payment.', $context + [
                    'reason_code' => 'amount_mismatch',
                ]);
            }

            $currency = strtolower((string)($session->currency ?? 'aud'));
            if ($currency !== 'aud') {
                $this->markPaymentForRefundReview(
                    $payment,
                    $session,
                    'unsupported_currency',
                    null,
                    $eventContext + ['funds_captured' => true],
                );

                return new NonRetriableWebhookException('Unsupported Stripe currency.', $context + [
                    'reason_code' => 'unsupported_currency',
                ]);
            }

            $paymentMetadata = [
                'stripe_checkout' => true,
                'payment_intent' => $this->stripeObjectId($session->payment_intent ?? null),
                'confirmation_source' => $confirmationSource,
                'session_id' => $transactionReference,
                'manual_review_required' => false,
                'review_state' => 'resolved_by_payment_confirmation',
                'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
                'reason_code' => 'payment_confirmed',
                'funds_captured' => true,
                'refund_required' => false,
            ] + $this->buildConfirmationEventAudit((string)$payment->notes, $eventType);
            $stripeDetails = $this->stripeDetailsCollector->collectFromCheckoutSession($session);
            if (isset($stripeDetails['stripe_payment_intent_id'])) {
                $paymentMetadata['payment_intent'] = $stripeDetails['stripe_payment_intent_id'];
            }

            switch ((string)$booking->booking_status) {
                case 'pending':
                    $this->applyStripeDetailsToPayment($payment, $stripeDetails);
                    $payment->payment_status = 'paid';
                    $payment->payment_date = DateTime::now();
                    $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                    $this->savePayment($payment, $context + ['reason_code' => 'payment_confirmation']);

                    $booking->booking_status = 'confirmed';
                    $this->saveBooking($booking, $context + ['reason_code' => 'booking_confirmation']);
                    $paidPaymentId = (int)$payment->payment_id;

                    return 'confirmed';

                case 'confirmed':
                case 'completed':
                    if (
                        $payment->payment_status !== 'paid' ||
                        $this->paymentNotesNeedResolution((string)$payment->notes)
                    ) {
                        $this->applyStripeDetailsToPayment($payment, $stripeDetails);
                        $payment->payment_status = 'paid';
                        $payment->payment_date = $payment->payment_date ?: DateTime::now();
                        $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                        $this->savePayment($payment, $context + ['reason_code' => 'idempotent_payment_sync']);
                    }
                    $paidPaymentId = (int)$payment->payment_id;

                    return 'idempotent';

                case 'cancelled':
                    $this->markPaymentForRefundReview(
                        $payment,
                        $session,
                        'cancelled_booking_paid_late',
                        'cancelled',
                        $eventContext + ['funds_captured' => true],
                    );

                    return new ManualReviewWebhookException(
                        'Cancelled booking received a late successful payment.',
                        $context + ['reason_code' => 'cancelled_booking_paid_late'],
                    );

                default:
                    $this->markPaymentForRefundReview(
                        $payment,
                        $session,
                        'unexpected_booking_status_after_paid_checkout',
                        (string)$booking->booking_status,
                        $this->withEventContext([
                            'local_payment_status' => (string)$payment->payment_status,
                            'booking_status' => (string)$booking->booking_status,
                            'stripe_payment_status' => $sessionPaymentStatus,
                            'funds_captured' => true,
                            'review_state' => 'captured_payment_with_unexpected_booking_status',
                        ], $eventType, $confirmationSource),
                    );

                    return new ManualReviewWebhookException(
                        'Booking status is incompatible with automatic confirmation.',
                        $context + ['reason_code' => 'unexpected_booking_status_after_paid_checkout'],
                    );
            }
        });

        if ($result instanceof PaymentWebhookException) {
            throw $result;
        }

        if ($paidPaymentId !== null && in_array($result, ['confirmed', 'idempotent'], true)) {
            $this->paymentReceiptEmailService->sendForPayment($paidPaymentId);
        }

        return $result;
    }

    /**
     * Mark checkout session failed.
     *
     * @param mixed $session Session.
     */
    public function markCheckoutSessionFailed(object $session): string
    {
        $transactionReference = (string)($session->id ?? '');
        if ($transactionReference === '') {
            throw new NonRetriableWebhookException('Stripe session id is missing.', [
                'session_id' => '',
                'reason_code' => 'missing_session_id',
            ]);
        }

        $connection = $this->paymentsTable->getConnection();
        $result = $connection->transactional(function () use ($session, $transactionReference): string|PaymentWebhookException {
            $paymentPreview = $this->paymentsTable->find()
                ->select(['payment_id', 'booking_id'])
                ->where([
                    'OR' => [
                        ['Payments.transaction_reference' => $transactionReference],
                        ['Payments.stripe_session_id' => $transactionReference],
                    ],
                ])
                ->first();

            if ($paymentPreview === null) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => null,
                    'booking_id' => null,
                    'reason_code' => 'payment_not_found',
                ]);
            }

            try {
                $booking = $this->loadBookingForUpdate((int)$paymentPreview->booking_id);
            } catch (RecordNotFoundException $exception) {
                return new ManualReviewWebhookException('Booking record not found for payment.', [
                    'session_id' => $transactionReference,
                    'payment_id' => (int)$paymentPreview->payment_id,
                    'booking_id' => (int)$paymentPreview->booking_id,
                    'reason_code' => 'booking_not_found',
                ], previous: $exception);
            }

            $payment = $this->loadPaymentForUpdate($transactionReference, (int)$paymentPreview->payment_id);
            if ($payment === null) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => (int)$paymentPreview->payment_id,
                    'booking_id' => (int)$paymentPreview->booking_id,
                    'reason_code' => 'payment_not_found_after_lock',
                ]);
            }

            $context = [
                'session_id' => $transactionReference,
                'payment_id' => (int)$payment->payment_id,
                'booking_id' => (int)$payment->booking_id,
            ];
            $localPaymentStatus = (string)$payment->payment_status;

            if (in_array($localPaymentStatus, ['failed', 'voided', 'expired'], true)) {
                $this->resolveNonCapturedReviewIfPresent(
                    $payment,
                    'checkout.session.async_payment_failed',
                    'resolved_by_async_payment_failure',
                    'stripe_async_payment_failed',
                );

                return 'idempotent';
            }

            if (in_array($localPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'async_payment_failed_after_processed_payment',
                    (string)$booking->booking_status,
                    $this->buildContradictoryTerminalEventNotes(
                        $localPaymentStatus,
                        'checkout.session.async_payment_failed',
                        'stripe_webhook',
                    ),
                );

                return new ManualReviewWebhookException(
                    'Stripe reported an async payment failure for a payment that was already processed locally.',
                    $context + [
                        'reason_code' => 'async_payment_failed_after_processed_payment',
                        'local_payment_status' => $localPaymentStatus,
                    ],
                );
            }

            if ($localPaymentStatus !== 'pending') {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'unexpected_local_payment_status',
                    (string)$booking->booking_status,
                    [
                        'event_type' => 'checkout.session.async_payment_failed',
                        'local_payment_status' => $localPaymentStatus,
                    ],
                );

                return new ManualReviewWebhookException(
                    'Payment status is incompatible with automatic failure handling.',
                    $context + [
                        'reason_code' => 'unexpected_local_payment_status',
                        'local_payment_status' => $localPaymentStatus,
                    ],
                );
            }

            $payment->payment_status = 'failed';
            $payment->notes = PaymentNotes::merge($payment->notes, [
                'stripe_checkout' => true,
                'confirmation_source' => 'stripe_webhook',
                'event_type' => 'checkout.session.async_payment_failed',
                'session_id' => $transactionReference,
                'payment_resolution' => 'stripe_async_payment_failed',
                'manual_review_required' => false,
                'review_state' => 'resolved_by_async_payment_failure',
                'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
                'reason_code' => 'stripe_async_payment_failed',
                'funds_captured' => false,
                'refund_required' => false,
            ]);
            $this->savePayment($payment, $context + ['reason_code' => 'payment_marked_failed']);

            return 'failed';
        });

        if ($result instanceof PaymentWebhookException) {
            throw $result;
        }

        return $result;
    }

    /**
     * Mark checkout session expired.
     *
     * @param mixed $session Session.
     */
    public function markCheckoutSessionExpired(object $session): string
    {
        $transactionReference = (string)($session->id ?? '');
        if ($transactionReference === '') {
            throw new NonRetriableWebhookException('Stripe session id is missing.', [
                'session_id' => '',
                'reason_code' => 'missing_session_id',
            ]);
        }

        $connection = $this->paymentsTable->getConnection();
        $result = $connection->transactional(function () use ($session, $transactionReference): string|PaymentWebhookException {
            $paymentPreview = $this->paymentsTable->find()
                ->select(['payment_id', 'booking_id'])
                ->where([
                    'OR' => [
                        ['Payments.transaction_reference' => $transactionReference],
                        ['Payments.stripe_session_id' => $transactionReference],
                    ],
                ])
                ->first();

            if ($paymentPreview === null) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => null,
                    'booking_id' => null,
                    'reason_code' => 'payment_not_found',
                ]);
            }

            try {
                $booking = $this->loadBookingForUpdate((int)$paymentPreview->booking_id);
            } catch (RecordNotFoundException $exception) {
                return new ManualReviewWebhookException('Booking record not found for payment.', [
                    'session_id' => $transactionReference,
                    'payment_id' => (int)$paymentPreview->payment_id,
                    'booking_id' => (int)$paymentPreview->booking_id,
                    'reason_code' => 'booking_not_found',
                ], previous: $exception);
            }

            $payment = $this->loadPaymentForUpdate($transactionReference, (int)$paymentPreview->payment_id);
            if ($payment === null) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => (int)$paymentPreview->payment_id,
                    'booking_id' => (int)$paymentPreview->booking_id,
                    'reason_code' => 'payment_not_found_after_lock',
                ]);
            }

            $context = [
                'session_id' => $transactionReference,
                'payment_id' => (int)$payment->payment_id,
                'booking_id' => (int)$payment->booking_id,
            ];
            $localPaymentStatus = (string)$payment->payment_status;

            if (in_array($localPaymentStatus, ['expired', 'voided', 'failed'], true)) {
                $this->resolveNonCapturedReviewIfPresent(
                    $payment,
                    'checkout.session.expired',
                    'resolved_by_session_expiration',
                    'stripe_checkout_session_expired',
                );

                return 'idempotent';
            }

            if (in_array($localPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'checkout_session_expired_after_processed_payment',
                    (string)$booking->booking_status,
                    $this->buildContradictoryTerminalEventNotes(
                        $localPaymentStatus,
                        'checkout.session.expired',
                        'stripe_webhook',
                    ),
                );

                return new ManualReviewWebhookException(
                    'Stripe reported session expiration for a payment already processed locally.',
                    $context + [
                        'reason_code' => 'checkout_session_expired_after_processed_payment',
                        'local_payment_status' => $localPaymentStatus,
                    ],
                );
            }

            if ($localPaymentStatus !== 'pending') {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'unexpected_local_payment_status',
                    (string)$booking->booking_status,
                    $this->withEventContext([
                        'local_payment_status' => $localPaymentStatus,
                    ], 'checkout.session.expired', 'stripe_webhook'),
                );

                return new ManualReviewWebhookException(
                    'Payment status is incompatible with automatic expiration handling.',
                    $context + [
                        'reason_code' => 'unexpected_local_payment_status',
                        'local_payment_status' => $localPaymentStatus,
                    ],
                );
            }

            $payment->payment_status = 'expired';
            $payment->notes = PaymentNotes::merge($payment->notes, [
                'stripe_checkout' => true,
                'confirmation_source' => 'stripe_webhook',
                'event_type' => 'checkout.session.expired',
                'session_id' => $transactionReference,
                'payment_resolution' => 'stripe_checkout_session_expired',
                'manual_review_required' => false,
                'review_state' => 'resolved_by_session_expiration',
                'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
                'reason_code' => 'stripe_checkout_session_expired',
                'funds_captured' => false,
                'refund_required' => false,
            ]);
            $this->savePayment($payment, $context + ['reason_code' => 'payment_marked_expired']);

            return 'expired';
        });

        if ($result instanceof PaymentWebhookException) {
            throw $result;
        }

        return $result;
    }

    /**
     * Save payment.
     *
     * @param mixed $payment Payment.
     * @param mixed $context Context.
     */
    protected function savePayment(object $payment, array $context): void
    {
        try {
            $this->persistPayment($payment);
        } catch (Throwable $exception) {
            throw new RetriableWebhookException(
                'Failed to persist payment state during webhook confirmation.',
                $context + ['reason_code' => $context['reason_code'] ?? 'payment_persist_failed'],
                previous: $exception,
            );
        }
    }

    /**
     * Save booking.
     *
     * @param mixed $booking Booking.
     * @param mixed $context Context.
     */
    protected function saveBooking(object $booking, array $context): void
    {
        try {
            $this->persistBooking($booking);
        } catch (Throwable $exception) {
            throw new RetriableWebhookException(
                'Failed to persist booking state during webhook confirmation.',
                $context + ['reason_code' => $context['reason_code'] ?? 'booking_persist_failed'],
                previous: $exception,
            );
        }
    }

    /**
     * Load booking for update.
     *
     * @param mixed $bookingId Bookingid.
     */
    private function loadBookingForUpdate(int $bookingId): object
    {
        $query = $this->bookingsTable->find()
            ->where(['Bookings.booking_id' => $bookingId]);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query->firstOrFail();
    }

    /**
     * Load payment for update.
     *
     * @param mixed $transactionReference Transactionreference.
     * @param mixed $paymentId Paymentid.
     */
    private function loadPaymentForUpdate(string $transactionReference, int $paymentId): ?object
    {
        $query = $this->paymentsTable->find()
            ->where([
                'Payments.payment_id' => $paymentId,
                'OR' => [
                    'Payments.transaction_reference' => $transactionReference,
                    'Payments.stripe_session_id' => $transactionReference,
                ],
            ]);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query->first();
    }

    /**
     * Mark payment for review.
     *
     * @param mixed $payment Payment.
     * @param mixed $session Session.
     * @param mixed $reasonCode Reasoncode.
     * @param mixed $bookingStatus Bookingstatus.
     * @param mixed $extraNotes Extranotes.
     * @param mixed $targetStatus Targetstatus.
     */
    private function markPaymentForReview(
        object $payment,
        object $session,
        string $reasonCode,
        ?string $bookingStatus = null,
        array $extraNotes = [],
        ?string $targetStatus = null,
    ): void {
        $this->applyStripeDetailsToPayment($payment, $this->stripeDetailsCollector->collectFromCheckoutSession($session));

        if (
            $targetStatus !== null &&
            !in_array($payment->payment_status, ['refund_required', 'refunded', 'partially_refunded'], true)
        ) {
            $payment->payment_status = $targetStatus;
        }

        $payment->notes = PaymentNotes::merge($payment->notes, array_filter(array_merge([
            'stripe_checkout' => true,
            'payment_intent' => $this->stripeObjectId($session->payment_intent ?? null),
            'confirmation_source' => 'stripe_webhook',
            'event_type' => $extraNotes['event_type'] ?? 'checkout.session.completed',
            'session_id' => (string)($session->id ?? ''),
            'manual_review_required' => true,
            'reason_code' => $reasonCode,
            'booking_status_at_confirmation' => $bookingStatus,
        ], $extraNotes), static fn($value) => $value !== null && $value !== ''));

        $this->savePayment($payment, [
            'session_id' => (string)($session->id ?? ''),
            'payment_id' => (int)$payment->payment_id,
            'booking_id' => (int)$payment->booking_id,
            'reason_code' => $reasonCode,
        ]);
    }

    /**
     * Apply stripe details to payment.
     *
     * @param mixed $payment Payment.
     * @param mixed $stripeDetails Stripedetails.
     */
    private function applyStripeDetailsToPayment(object $payment, array $stripeDetails): void
    {
        foreach (
            [
            'stripe_session_id',
            'stripe_payment_intent_id',
            'stripe_charge_id',
            'stripe_customer_id',
            'stripe_invoice_id',
            'stripe_invoice_pdf_url',
            'stripe_receipt_url',
            'stripe_payment_method_type',
            ] as $field
        ) {
            if (isset($stripeDetails[$field]) && $stripeDetails[$field] !== '') {
                $payment->{$field} = $stripeDetails[$field];
            }
        }
    }

    /**
     * Mark payment for refund review.
     *
     * @param mixed $payment Payment.
     * @param mixed $session Session.
     * @param mixed $reasonCode Reasoncode.
     * @param mixed $bookingStatus Bookingstatus.
     * @param mixed $extraNotes Extranotes.
     */
    private function markPaymentForRefundReview(
        object $payment,
        object $session,
        string $reasonCode,
        ?string $bookingStatus = null,
        array $extraNotes = [],
    ): void {
        if (($extraNotes['funds_captured'] ?? false) !== true) {
            throw new LogicException('Refund review requires captured funds.');
        }

        $currentStatus = (string)($payment->payment_status ?? '');

        if (
            $payment->payment_date === null
            || in_array($currentStatus, ['pending', 'failed', 'expired', 'voided'], true)
        ) {
            $payment->payment_date = DateTime::now();
        }

        $this->markPaymentForReview(
            $payment,
            $session,
            $reasonCode,
            $bookingStatus,
            array_merge($extraNotes, ['refund_required' => true]),
            'refund_required',
        );
    }

    /**
     * Mark payment for review without refund requirement.
     *
     * @param mixed $payment Payment.
     * @param mixed $session Session.
     * @param mixed $reasonCode Reasoncode.
     * @param mixed $bookingStatus Bookingstatus.
     * @param mixed $extraNotes Extranotes.
     */
    private function markPaymentForReviewWithoutRefundRequirement(
        object $payment,
        object $session,
        string $reasonCode,
        ?string $bookingStatus = null,
        array $extraNotes = [],
    ): void {
        $this->markPaymentForReview(
            $payment,
            $session,
            $reasonCode,
            $bookingStatus,
            $extraNotes,
            null,
        );
    }

    /**
     * Resolve non captured review if present.
     *
     * @param mixed $payment Payment.
     * @param mixed $eventType Eventtype.
     * @param mixed $reviewState Reviewstate.
     * @param mixed $reasonCode Reasoncode.
     */
    private function resolveNonCapturedReviewIfPresent(
        object $payment,
        string $eventType,
        string $reviewState,
        string $reasonCode,
    ): void {
        $decoded = json_decode((string)$payment->notes, true);
        if (!is_array($decoded)) {
            return;
        }

        if (($decoded['manual_review_required'] ?? false) !== true) {
            return;
        }

        if (($decoded['refund_required'] ?? true) !== false) {
            return;
        }

        if (($decoded['funds_captured'] ?? true) !== false) {
            return;
        }

        if (
            !in_array($decoded['review_state'] ?? null, [
            'awaiting_payment_confirmation',
            'awaiting_payment_terminal_event',
            ], true)
        ) {
            return;
        }

        $payment->notes = PaymentNotes::merge($payment->notes, [
            'manual_review_required' => false,
            'review_state' => $reviewState,
            'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
            'refund_required' => false,
            'funds_captured' => false,
            'event_type' => $eventType,
            'terminal_event_type' => $eventType,
            'reason_code' => $reasonCode,
        ]);

        $this->savePayment($payment, [
            'session_id' => (string)($decoded['session_id'] ?? ''),
            'payment_id' => (int)$payment->payment_id,
            'booking_id' => (int)$payment->booking_id,
            'reason_code' => $reasonCode,
        ]);
    }

    /**
     * With event context.
     *
     * @param mixed $notes Notes.
     * @param mixed $eventType Eventtype.
     * @param mixed $confirmationSource Confirmationsource.
     */
    private function withEventContext(array $notes, string $eventType, string $confirmationSource): array
    {
        return [
            'event_type' => $eventType,
            'confirmation_source' => $confirmationSource,
        ] + $notes;
    }

    /**
     * Build contradictory terminal event notes.
     *
     * @param mixed $localPaymentStatus Localpaymentstatus.
     * @param mixed $eventType Eventtype.
     * @param mixed $confirmationSource Confirmationsource.
     */
    private function buildContradictoryTerminalEventNotes(
        string $localPaymentStatus,
        string $eventType,
        string $confirmationSource,
    ): array {
        $reviewState = match ($localPaymentStatus) {
            'paid' => 'contradictory_terminal_event_after_paid',
            'refund_required' => 'contradictory_terminal_event_while_refund_pending',
            'partially_refunded' => 'contradictory_terminal_event_after_partial_refund',
            'refunded' => 'contradictory_terminal_event_after_full_refund',
            default => 'contradictory_terminal_event',
        };

        $notes = $this->withEventContext([
            'local_payment_status' => $localPaymentStatus,
            'review_state' => $reviewState,
            'terminal_event_type' => $eventType,
            'funds_captured' => true,
            'stripe_terminal_event_indicates_funds_captured' => false,
        ], $eventType, $confirmationSource);

        $notes['refund_required'] = match ($localPaymentStatus) {
            'refund_required' => true,
            'paid', 'refunded', 'partially_refunded' => false,
            default => false,
        };

        return $notes;
    }

    /**
     * Append refund lifecycle audit.
     *
     * @param mixed $payment Payment.
     * @param mixed $session Session.
     * @param mixed $transactionReference Transactionreference.
     * @param mixed $eventType Eventtype.
     * @param mixed $confirmationSource Confirmationsource.
     * @param mixed $localPaymentStatus Localpaymentstatus.
     */
    private function appendRefundLifecycleAudit(
        object $payment,
        object $session,
        string $transactionReference,
        string $eventType,
        string $confirmationSource,
        string $localPaymentStatus,
    ): void {
        $reasonCode = $localPaymentStatus === 'refunded'
            ? 'duplicate_completed_event_after_refund'
            : 'duplicate_completed_event_after_partial_refund';

        $reviewState = $localPaymentStatus === 'refunded'
            ? 'resolved_after_refund_lifecycle'
            : 'resolved_after_partial_refund_lifecycle';

        $payment->notes = PaymentNotes::merge($payment->notes, [
            'stripe_checkout' => true,
            'payment_intent' => $this->stripeObjectId($session->payment_intent ?? null),
            'confirmation_source' => $confirmationSource,
            'session_id' => $transactionReference,
            'manual_review_required' => false,
            'review_state' => $reviewState,
            'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
            'reason_code' => $reasonCode,
            'funds_captured' => true,
            'refund_required' => false,
        ] + $this->buildConfirmationEventAudit((string)$payment->notes, $eventType));

        $this->savePayment($payment, [
            'session_id' => $transactionReference,
            'payment_id' => (int)$payment->payment_id,
            'booking_id' => (int)$payment->booking_id,
            'reason_code' => $reasonCode,
        ]);
    }

    /**
     * Payment notes need resolution.
     *
     * @param mixed $existingNotes Existingnotes.
     */
    private function paymentNotesNeedResolution(string $existingNotes): bool
    {
        if ($existingNotes === '') {
            return true;
        }

        $decoded = json_decode($existingNotes, true);
        if (!is_array($decoded)) {
            return true;
        }

        if (($decoded['manual_review_required'] ?? false) === true) {
            return true;
        }

        if (($decoded['reason_code'] ?? null) !== 'payment_confirmed') {
            return true;
        }

        return ($decoded['review_state'] ?? null) !== 'resolved_by_payment_confirmation';
    }

    /**
     * Build confirmation event audit.
     *
     * @param mixed $existingNotes Existingnotes.
     * @param mixed $eventType Eventtype.
     */
    private function buildConfirmationEventAudit(string $existingNotes, string $eventType): array
    {
        $events = [];
        $decoded = json_decode($existingNotes, true);
        if (is_array($decoded)) {
            foreach (['event_type', 'last_stripe_event_type'] as $key) {
                $value = $decoded[$key] ?? null;
                if (is_string($value) && $value !== '') {
                    $events[] = $value;
                }
            }

            $confirmationEvents = $decoded['confirmation_events'] ?? [];
            if (is_array($confirmationEvents)) {
                foreach ($confirmationEvents as $value) {
                    if (is_string($value) && $value !== '') {
                        $events[] = $value;
                    }
                }
            }
        }

        $events[] = $eventType;

        return [
            'event_type' => $eventType,
            'last_stripe_event_type' => $eventType,
            'confirmation_events' => array_values(array_unique($events)),
        ];
    }

    /**
     * Extract booking id from client reference id.
     *
     * @param mixed $clientReferenceId Clientreferenceid.
     */
    private function extractBookingIdFromClientReferenceId(string $clientReferenceId): ?int
    {
        $normalized = trim($clientReferenceId);
        if ($normalized === '') {
            return null;
        }

        if (ctype_digit($normalized)) {
            return (int)$normalized;
        }

        if (preg_match('/^booking:(\d+):attempt:[a-f0-9]{16}$/', strtolower($normalized), $matches) === 1) {
            return (int)$matches[1];
        }

        return null;
    }

    /**
     * Persist payment.
     *
     * @param mixed $payment Payment.
     */
    protected function persistPayment(object $payment): void
    {
        $this->paymentsTable->saveOrFail($payment);
    }

    /**
     * Persist booking.
     *
     * @param mixed $booking Booking.
     */
    protected function persistBooking(object $booking): void
    {
        $this->bookingsTable->saveOrFail($booking);
    }

    /**
     * Stripe object id.
     *
     * @param mixed $value Value.
     */
    private function stripeObjectId(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && isset($value->id)) {
            return (string)$value->id;
        }

        return '';
    }

    /**
     * Supports row locking.
     */
    private function supportsRowLocking(): bool
    {
        return $this->paymentsTable->getConnection()->getDriver() instanceof Mysql;
    }
}
