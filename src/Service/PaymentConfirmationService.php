<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorInterface;
use Cake\Datasource\Exception\RecordNotFoundException;
use Throwable;

class PaymentConfirmationService implements PaymentConfirmationServiceInterface
{
    private object $paymentsTable;
    private object $bookingsTable;

    public function __construct(?LocatorInterface $tableLocator = null)
    {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->paymentsTable = $locator->get('Payments');
        $this->bookingsTable = $locator->get('Bookings');
    }

    public function confirmCheckoutSession(
        object $session,
        string $eventType = 'checkout.session.completed',
        string $confirmationSource = 'stripe_webhook',
    ): string
    {
        $transactionReference = (string)($session->id ?? '');
        if ($transactionReference === '') {
            throw new NonRetriableWebhookException('Stripe session id is missing.', [
                'session_id' => '',
                'reason_code' => 'missing_session_id',
            ]);
        }

        $connection = $this->paymentsTable->getConnection();
        $result = $connection->transactional(function () use (
            $session,
            $transactionReference,
            $eventType,
            $confirmationSource
        ): string|PaymentWebhookException {
            $paymentPreview = $this->paymentsTable->find()
                ->select(['payment_id', 'booking_id'])
                ->where(['Payments.transaction_reference' => $transactionReference])
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
                    $this->markPaymentForReview(
                        $payment,
                        $session,
                        'completed_after_local_payment_voided_or_expired',
                        null,
                        $this->withEventContext([
                            'local_payment_status' => $localPaymentStatus,
                            'stripe_payment_status' => $sessionPaymentStatus,
                            'funds_captured' => true,
                            'refund_required' => true,
                        ], $eventType, $confirmationSource)
                    );

                    return new ManualReviewWebhookException(
                        'A completed Stripe session arrived for a locally voided or expired payment.',
                        $context + [
                            'reason_code' => 'completed_after_local_payment_voided_or_expired',
                            'local_payment_status' => $localPaymentStatus,
                            'stripe_payment_status' => $sessionPaymentStatus,
                        ]
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
                    ], $eventType, $confirmationSource)
                );

                return new ManualReviewWebhookException(
                    'A completed Stripe session arrived for a locally voided or expired payment without a paid terminal state.',
                    $context + [
                        'reason_code' => 'completed_after_local_payment_voided_or_expired_without_paid_status',
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
                );
            }

            if (in_array($localPaymentStatus, ['refund_required', 'refunded', 'partially_refunded'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'completed_after_refund_or_review_state',
                    null,
                    $this->withEventContext([
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ], $eventType, $confirmationSource)
                );

                return new ManualReviewWebhookException(
                    'A completed Stripe session arrived for a payment already in refund/manual-review state.',
                    $context + [
                        'reason_code' => 'completed_after_refund_or_review_state',
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
                );
            }

            if (!in_array($localPaymentStatus, ['pending', 'paid'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'unexpected_local_payment_status',
                    null,
                    $this->withEventContext([
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ], $eventType, $confirmationSource)
                );

                return new ManualReviewWebhookException(
                    'Payment status is incompatible with automatic webhook confirmation.',
                    $context + [
                        'reason_code' => 'unexpected_local_payment_status',
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
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
                    ], $eventType, $confirmationSource)
                );

                return new ManualReviewWebhookException(
                    'Checkout session completed but payment_status is not paid.',
                    $context + [
                        'reason_code' => 'checkout_completed_without_paid_status',
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
                );
            }

            $metadataBookingId = (string)($session->metadata->booking_id ?? '');
            if ($metadataBookingId === '') {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'missing_booking_metadata',
                    null,
                    $eventContext
                );

                return new NonRetriableWebhookException('Stripe booking metadata is missing.', $context + [
                    'reason_code' => 'missing_booking_metadata',
                ]);
            }

            if (!ctype_digit($metadataBookingId)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'invalid_booking_metadata',
                    null,
                    $eventContext
                );

                return new NonRetriableWebhookException('Stripe booking metadata is invalid.', $context + [
                    'reason_code' => 'invalid_booking_metadata',
                ]);
            }

            if ((int)$metadataBookingId !== (int)$payment->booking_id) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'booking_metadata_mismatch',
                    null,
                    $eventContext
                );

                return new NonRetriableWebhookException('Stripe booking metadata does not match the local payment.', $context + [
                    'reason_code' => 'booking_metadata_mismatch',
                ]);
            }

            $expectedAmount = (int)round((float)$payment->amount * 100);
            if (isset($session->amount_total) && (int)$session->amount_total !== $expectedAmount) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'amount_mismatch',
                    null,
                    $eventContext
                );

                return new NonRetriableWebhookException('Stripe amount does not match the local payment.', $context + [
                    'reason_code' => 'amount_mismatch',
                ]);
            }

            $currency = strtolower((string)($session->currency ?? 'aud'));
            if ($currency !== 'aud') {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'unsupported_currency',
                    null,
                    $eventContext
                );

                return new NonRetriableWebhookException('Unsupported Stripe currency.', $context + [
                    'reason_code' => 'unsupported_currency',
                ]);
            }

            $paymentMetadata = [
                'stripe_checkout' => true,
                'payment_intent' => (string)($session->payment_intent ?? ''),
                'confirmation_source' => $confirmationSource,
                'session_id' => $transactionReference,
                'manual_review_required' => false,
                'review_state' => 'resolved_by_payment_confirmation',
                'review_resolved_at' => DateTime::now()->i18nFormat(DateTime::ATOM),
                'reason_code' => 'payment_confirmed',
                'funds_captured' => true,
                'refund_required' => false,
            ] + $this->buildConfirmationEventAudit((string)$payment->notes, $eventType);

            switch ((string)$booking->booking_status) {
                case 'pending':
                    $payment->payment_status = 'paid';
                    $payment->payment_date = DateTime::now();
                    $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                    $this->savePayment($payment, $context + ['reason_code' => 'payment_confirmation']);

                    $booking->booking_status = 'confirmed';
                    $this->saveBooking($booking, $context + ['reason_code' => 'booking_confirmation']);

                    return 'confirmed';

                case 'confirmed':
                case 'completed':
                    if (
                        $payment->payment_status !== 'paid' ||
                        $this->paymentNotesNeedResolution((string)$payment->notes)
                    ) {
                        $payment->payment_status = 'paid';
                        $payment->payment_date = $payment->payment_date ?: DateTime::now();
                        $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                        $this->savePayment($payment, $context + ['reason_code' => 'idempotent_payment_sync']);
                    }

                    return 'idempotent';

                case 'cancelled':
                    $this->markPaymentForReview(
                        $payment,
                        $session,
                        'cancelled_booking_paid_late',
                        'cancelled',
                        $eventContext
                    );

                    return new ManualReviewWebhookException(
                        'Cancelled booking received a late successful payment.',
                        $context + ['reason_code' => 'cancelled_booking_paid_late']
                    );

                default:
                    $this->markPaymentForReview(
                        $payment,
                        $session,
                        'unexpected_booking_status',
                        (string)$booking->booking_status,
                        $eventContext
                    );

                    return new ManualReviewWebhookException(
                        'Booking status is incompatible with automatic confirmation.',
                        $context + ['reason_code' => 'unexpected_booking_status']
                    );
            }
        });

        if ($result instanceof PaymentWebhookException) {
            throw $result;
        }

        return $result;
    }

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
                ->where(['Payments.transaction_reference' => $transactionReference])
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
                return 'idempotent';
            }

            if (in_array($localPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'async_payment_failed_after_processed_payment',
                    (string)$booking->booking_status,
                    ['event_type' => 'checkout.session.async_payment_failed']
                );

                return new ManualReviewWebhookException(
                    'Stripe reported an async payment failure for a payment that was already processed locally.',
                    $context + [
                        'reason_code' => 'async_payment_failed_after_processed_payment',
                        'local_payment_status' => $localPaymentStatus,
                    ]
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
                    ]
                );

                return new ManualReviewWebhookException(
                    'Payment status is incompatible with automatic failure handling.',
                    $context + [
                        'reason_code' => 'unexpected_local_payment_status',
                        'local_payment_status' => $localPaymentStatus,
                    ]
                );
            }

            $payment->payment_status = 'failed';
            $payment->payment_date = $payment->payment_date ?: DateTime::now();
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
                ->where(['Payments.transaction_reference' => $transactionReference])
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
                return 'idempotent';
            }

            if (in_array($localPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'checkout_session_expired_after_processed_payment',
                    (string)$booking->booking_status,
                    $this->withEventContext([
                        'local_payment_status' => $localPaymentStatus,
                        'funds_captured' => in_array($localPaymentStatus, ['paid', 'refund_required', 'partially_refunded', 'refunded'], true),
                        'refund_required' => in_array($localPaymentStatus, ['paid', 'refund_required'], true),
                    ], 'checkout.session.expired', 'stripe_webhook')
                );

                return new ManualReviewWebhookException(
                    'Stripe reported session expiration for a payment already processed locally.',
                    $context + [
                        'reason_code' => 'checkout_session_expired_after_processed_payment',
                        'local_payment_status' => $localPaymentStatus,
                    ]
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
                    ], 'checkout.session.expired', 'stripe_webhook')
                );

                return new ManualReviewWebhookException(
                    'Payment status is incompatible with automatic expiration handling.',
                    $context + [
                        'reason_code' => 'unexpected_local_payment_status',
                        'local_payment_status' => $localPaymentStatus,
                    ]
                );
            }

            $payment->payment_status = 'expired';
            $payment->payment_date = $payment->payment_date ?: DateTime::now();
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

    protected function savePayment(object $payment, array $context): void
    {
        try {
            $this->persistPayment($payment);
        } catch (Throwable $exception) {
            throw new RetriableWebhookException(
                'Failed to persist payment state during webhook confirmation.',
                $context + ['reason_code' => $context['reason_code'] ?? 'payment_persist_failed'],
                previous: $exception
            );
        }
    }

    protected function saveBooking(object $booking, array $context): void
    {
        try {
            $this->persistBooking($booking);
        } catch (Throwable $exception) {
            throw new RetriableWebhookException(
                'Failed to persist booking state during webhook confirmation.',
                $context + ['reason_code' => $context['reason_code'] ?? 'booking_persist_failed'],
                previous: $exception
            );
        }
    }

    private function loadBookingForUpdate(int $bookingId): object
    {
        $query = $this->bookingsTable->find()
            ->where(['Bookings.booking_id' => $bookingId]);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query->firstOrFail();
    }

    private function loadPaymentForUpdate(string $transactionReference, int $paymentId): ?object
    {
        $query = $this->paymentsTable->find()
            ->where([
                'Payments.payment_id' => $paymentId,
                'Payments.transaction_reference' => $transactionReference,
            ]);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query->first();
    }

    private function markPaymentForReview(
        object $payment,
        object $session,
        string $reasonCode,
        ?string $bookingStatus = null,
        array $extraNotes = [],
        ?string $targetStatus = 'refund_required',
    ): void {
        if (
            $targetStatus !== null &&
            !in_array($payment->payment_status, ['refund_required', 'refunded', 'partially_refunded'], true)
        ) {
            $payment->payment_status = $targetStatus;
        }

        $payment->payment_date = $payment->payment_date ?: DateTime::now();
        $payment->notes = PaymentNotes::merge($payment->notes, array_filter(array_merge([
            'stripe_checkout' => true,
            'payment_intent' => (string)($session->payment_intent ?? ''),
            'confirmation_source' => 'stripe_webhook',
            'event_type' => $extraNotes['event_type'] ?? 'checkout.session.completed',
            'session_id' => (string)($session->id ?? ''),
            'manual_review_required' => true,
            'reason_code' => $reasonCode,
            'booking_status_at_confirmation' => $bookingStatus,
        ], $extraNotes), static fn ($value) => $value !== null && $value !== ''));

        $this->savePayment($payment, [
            'session_id' => (string)($session->id ?? ''),
            'payment_id' => (int)$payment->payment_id,
            'booking_id' => (int)$payment->booking_id,
            'reason_code' => $reasonCode,
        ]);
    }

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
            null
        );
    }

    private function withEventContext(array $notes, string $eventType, string $confirmationSource): array
    {
        return [
            'event_type' => $eventType,
            'confirmation_source' => $confirmationSource,
        ] + $notes;
    }

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

    protected function persistPayment(object $payment): void
    {
        $this->paymentsTable->saveOrFail($payment);
    }

    protected function persistBooking(object $booking): void
    {
        $this->bookingsTable->saveOrFail($booking);
    }

    private function supportsRowLocking(): bool
    {
        return $this->paymentsTable->getConnection()->getDriver() instanceof Mysql;
    }
}
