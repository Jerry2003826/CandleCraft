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

    public function confirmCheckoutSession(object $session): void
    {
        $transactionReference = (string)($session->id ?? '');
        if ($transactionReference === '') {
            throw new RuntimeException('Stripe session id is missing.');
        }

        $connection = $this->paymentsTable->getConnection();
        $connection->transactional(function () use ($session, $transactionReference): void {
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

            if ($payment->payment_status !== 'paid') {
                $payment->payment_status = 'paid';
                $payment->payment_date = DateTime::now();
                $payment->notes = $this->mergeNotes($payment->notes, [
                    'stripe_checkout' => true,
                    'payment_intent' => (string)($session->payment_intent ?? ''),
                    'confirmation_source' => 'stripe_webhook',
                ]);
                $this->paymentsTable->saveOrFail($payment);
            }

            $booking = $this->bookingsTable->get($payment->booking_id);
            if (!in_array($booking->booking_status, ['confirmed', 'completed'], true)) {
                $booking->booking_status = 'confirmed';
                $this->bookingsTable->saveOrFail($booking);
            }
        });
    }

    private function mergeNotes(?string $existingNotes, array $newNotes): string
    {
        if (!$existingNotes) {
            return (string)json_encode($newNotes);
        }

        $decoded = json_decode($existingNotes, true);
        if (!is_array($decoded)) {
            $decoded = ['legacy_notes' => $existingNotes];
        }

        return (string)json_encode(array_merge($decoded, $newNotes));
    }
}
