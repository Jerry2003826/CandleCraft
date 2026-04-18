<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;

class PaymentConfirmationService
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
            throw new RuntimeException('Stripe session id is missing.');
        }

        $connection = $this->paymentsTable->getConnection();
        return $connection->transactional(function () use ($session, $transactionReference): string {
            $payment = $this->paymentsTable->find()
                ->where(['Payments.transaction_reference' => $transactionReference])
                ->first();

            if (!$payment) {
                throw new RuntimeException('Payment record not found.');
            }

            $metadataBookingId = $session->metadata->booking_id ?? null;
            if ($metadataBookingId !== null && (int)$metadataBookingId !== (int)$payment->booking_id) {
                throw new RuntimeException('Stripe booking metadata does not match the local payment.');
            }

            $expectedAmount = (int)round((float)$payment->amount * 100);
            if (isset($session->amount_total) && (int)$session->amount_total !== $expectedAmount) {
                throw new RuntimeException('Stripe amount does not match the local payment.');
            }

            $currency = strtolower((string)($session->currency ?? 'aud'));
            if ($currency !== 'aud') {
                throw new RuntimeException('Unsupported Stripe currency.');
            }

            $booking = $this->bookingsTable->get($payment->booking_id);
            $paymentMetadata = [
                'stripe_checkout' => true,
                'payment_intent' => (string)($session->payment_intent ?? ''),
                'confirmation_source' => 'stripe_webhook',
            ];

            switch ((string)$booking->booking_status) {
                case 'pending':
                    $payment->payment_status = 'paid';
                    $payment->payment_date = DateTime::now();
                    $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                    $this->paymentsTable->saveOrFail($payment);

                    $booking->booking_status = 'confirmed';
                    $this->bookingsTable->saveOrFail($booking);

                    return 'confirmed';

                case 'confirmed':
                case 'completed':
                    if ($payment->payment_status !== 'paid') {
                        $payment->payment_status = 'paid';
                        $payment->payment_date = DateTime::now();
                        $payment->notes = PaymentNotes::merge($payment->notes, $paymentMetadata);
                        $this->paymentsTable->saveOrFail($payment);
                    }

                    return 'idempotent';

                case 'cancelled':
                    if (!in_array($payment->payment_status, ['refund_required', 'refunded', 'partially_refunded'], true)) {
                        $payment->payment_status = 'refund_required';
                    }
                    $payment->payment_date = $payment->payment_date ?: DateTime::now();
                    $payment->notes = PaymentNotes::merge($payment->notes, array_merge($paymentMetadata, [
                        'booking_status_at_confirmation' => 'cancelled',
                        'manual_review_required' => true,
                    ]));
                    $this->paymentsTable->saveOrFail($payment);

                    return 'refund_required';

                default:
                    $payment->payment_status = 'refund_required';
                    $payment->payment_date = $payment->payment_date ?: DateTime::now();
                    $payment->notes = PaymentNotes::merge($payment->notes, array_merge($paymentMetadata, [
                        'booking_status_at_confirmation' => (string)$booking->booking_status,
                        'manual_review_required' => true,
                    ]));
                    $this->paymentsTable->saveOrFail($payment);

                    return 'manual_review';
            }
        });
    }
}
