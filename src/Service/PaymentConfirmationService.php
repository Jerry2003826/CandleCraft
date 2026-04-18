<?php
declare(strict_types=1);

namespace App\Service;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\PaymentWebhookException;
use App\Exception\Payments\RetriableWebhookException;
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
            $payment = $this->paymentsTable->find()
                ->where(['Payments.transaction_reference' => $transactionReference])
                ->first();

            if (!$payment) {
                return new ManualReviewWebhookException('Payment record not found.', [
                    'session_id' => $transactionReference,
                    'payment_id' => null,
                    'booking_id' => null,
                    'reason_code' => 'payment_not_found',
                ]);
            }

            $context = [
                'session_id' => $transactionReference,
                'payment_id' => (int)$payment->payment_id,
                'booking_id' => (int)$payment->booking_id,
            ];

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

            try {
                $booking = $this->bookingsTable->get($payment->booking_id);
            } catch (RecordNotFoundException $exception) {
                $this->markPaymentForReview($payment, $session, 'booking_not_found');

                return new ManualReviewWebhookException('Booking record not found for payment.', $context + [
                    'reason_code' => 'booking_not_found',
                ], previous: $exception);
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

    private function markPaymentForReview(
        object $payment,
        object $session,
        string $reasonCode,
        ?string $bookingStatus = null,
    ): void {
        if (!in_array($payment->payment_status, ['refund_required', 'refunded', 'partially_refunded'], true)) {
            $payment->payment_status = 'refund_required';
        }

        $payment->payment_date = $payment->payment_date ?: DateTime::now();
        $payment->notes = PaymentNotes::merge($payment->notes, array_filter([
            'stripe_checkout' => true,
            'payment_intent' => (string)($session->payment_intent ?? ''),
            'confirmation_source' => 'stripe_webhook',
            'event_type' => 'checkout.session.completed',
            'session_id' => (string)($session->id ?? ''),
            'manual_review_required' => true,
            'reason_code' => $reasonCode,
            'booking_status_at_confirmation' => $bookingStatus,
        ], static fn ($value) => $value !== null));

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
}
