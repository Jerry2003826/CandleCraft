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

    public function confirmCheckoutSession(object $session): string
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

            if (in_array($localPaymentStatus, ['voided', 'expired'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'completed_after_local_payment_voided_or_expired',
                    null,
                    [
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
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

            if (in_array($localPaymentStatus, ['refund_required', 'refunded', 'partially_refunded'], true)) {
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'completed_after_refund_or_review_state',
                    null,
                    [
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
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
                    [
                        'local_payment_status' => $localPaymentStatus,
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
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
                $this->markPaymentForReview(
                    $payment,
                    $session,
                    'checkout_completed_without_paid_status',
                    null,
                    ['stripe_payment_status' => $sessionPaymentStatus]
                );

                return new ManualReviewWebhookException(
                    'Checkout session completed but payment_status is not paid.',
                    $context + [
                        'reason_code' => 'checkout_completed_without_paid_status',
                        'stripe_payment_status' => $sessionPaymentStatus,
                    ]
                );
            }

            $metadataBookingId = $session->metadata->booking_id ?? null;
            if ($metadataBookingId === null || $metadataBookingId === '') {
                $this->markPaymentForReview($payment, $session, 'missing_booking_metadata');

                return new NonRetriableWebhookException('Stripe booking metadata is missing.', $context + [
                    'reason_code' => 'missing_booking_metadata',
                ]);
            }

            if ((int)$metadataBookingId !== (int)$payment->booking_id) {
                $this->markPaymentForReview($payment, $session, 'booking_metadata_mismatch');

                return new NonRetriableWebhookException('Stripe booking metadata does not match the local payment.', $context + [
                    'reason_code' => 'booking_metadata_mismatch',
                ]);
            }

            $expectedAmount = (int)round((float)$payment->amount * 100);
            if (isset($session->amount_total) && (int)$session->amount_total !== $expectedAmount) {
                $this->markPaymentForReview($payment, $session, 'amount_mismatch');

                return new NonRetriableWebhookException('Stripe amount does not match the local payment.', $context + [
                    'reason_code' => 'amount_mismatch',
                ]);
            }

            $currency = strtolower((string)($session->currency ?? 'aud'));
            if ($currency !== 'aud') {
                $this->markPaymentForReview($payment, $session, 'unsupported_currency');

                return new NonRetriableWebhookException('Unsupported Stripe currency.', $context + [
                    'reason_code' => 'unsupported_currency',
                ]);
            }

            $paymentMetadata = [
                'stripe_checkout' => true,
                'payment_intent' => (string)($session->payment_intent ?? ''),
                'confirmation_source' => 'stripe_webhook',
                'session_id' => $transactionReference,
                'event_type' => 'checkout.session.completed',
            ];

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
                    if ($payment->payment_status !== 'paid') {
                        $payment->payment_status = 'paid';
                        $payment->payment_date = DateTime::now();
                        $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                        $this->savePayment($payment, $context + ['reason_code' => 'idempotent_payment_sync']);
                    }

                    return 'idempotent';

                case 'cancelled':
                    $this->markPaymentForReview(
                        $payment,
                        $session,
                        'cancelled_booking_paid_late',
                        'cancelled'
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
                        (string)$booking->booking_status
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
            ]);
            $this->savePayment($payment, $context + ['reason_code' => 'payment_marked_failed']);

            return 'failed';
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
    ): void {
        if (!in_array($payment->payment_status, ['refund_required', 'refunded', 'partially_refunded'], true)) {
            $payment->payment_status = 'refund_required';
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
