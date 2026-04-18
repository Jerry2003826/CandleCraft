<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;
use Throwable;

class BookingCancellationService
{
    private object $bookingsTable;
    private object $paymentsTable;
    private StripeCheckoutGatewayInterface $gateway;

    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripeCheckoutGatewayInterface $gateway = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->bookingsTable = $locator->get('Bookings');
        $this->paymentsTable = $locator->get('Payments');
        $this->gateway = $gateway ?? $this->buildGateway();
    }

    public function cancelBooking(int $bookingId, array $context = []): void
    {
        $connection = $this->paymentsTable->getConnection();
        $connection->transactional(function () use ($bookingId, $context): void {
            $booking = $this->loadBookingForUpdate($bookingId);

            if ($booking->booking_status === 'cancelled') {
                return;
            }

            $payments = $this->findBookingPayments($bookingId);
            foreach ($payments as $payment) {
                if (in_array($payment->payment_status, ['paid', 'refund_required', 'partially_refunded', 'refunded'], true)) {
                    throw new RuntimeException('Paid bookings require a refund or manual review before they can be cancelled.');
                }
            }

            foreach ($payments as $payment) {
                if ($payment->payment_status !== 'pending') {
                    continue;
                }

                $expirationResult = $this->expirePendingSession($payment, $context);
                $payment->payment_status = 'voided';
                $payment->notes = PaymentNotes::merge($payment->notes, [
                    'payment_resolution' => 'booking_cancelled',
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                    'checkout_session_expired' => $expirationResult['expired'],
                    'checkout_session_expiration_skipped' => $expirationResult['skipped'],
                ]);
                $this->paymentsTable->saveOrFail($payment);
            }

            $booking->booking_status = 'cancelled';
            $this->bookingsTable->saveOrFail($booking);
        });
    }

    public function voidPendingPaymentsForBooking(int $bookingId, array $context = []): int
    {
        $connection = $this->paymentsTable->getConnection();

        return $connection->transactional(function () use ($bookingId, $context): int {
            $this->loadBookingForUpdate($bookingId);
            $payments = $this->findBookingPayments($bookingId);
            $voided = 0;

            foreach ($payments as $payment) {
                if ($payment->payment_status !== 'pending') {
                    continue;
                }

                $expirationResult = $this->expirePendingSession($payment, $context);
                $payment->payment_status = 'voided';
                $payment->notes = PaymentNotes::merge($payment->notes, [
                    'payment_resolution' => 'checkout_cancelled',
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                    'checkout_session_expired' => $expirationResult['expired'],
                    'checkout_session_expiration_skipped' => $expirationResult['skipped'],
                ]);
                $this->paymentsTable->saveOrFail($payment);
                $voided++;
            }

            return $voided;
        });
    }

    private function expirePendingSession(object $payment, array $context): array
    {
        $transactionReference = (string)($payment->transaction_reference ?? '');
        if ($transactionReference === '') {
            return ['expired' => false, 'skipped' => true];
        }

        if (!$this->isStripeConfigured()) {
            return ['expired' => false, 'skipped' => true];
        }

        try {
            $session = $this->gateway->retrieveCheckoutSession($transactionReference);
            $paymentStatus = strtolower((string)($session->payment_status ?? ''));
            $sessionStatus = strtolower((string)($session->status ?? ''));

            if ($paymentStatus === 'paid' || $sessionStatus === 'complete') {
                throw new RuntimeException('This booking has already been paid and requires manual review.');
            }

            if ($sessionStatus === 'expired') {
                return ['expired' => false, 'skipped' => false];
            }

            $this->gateway->expireCheckoutSession($transactionReference);

            return ['expired' => true, 'skipped' => false];
        } catch (RuntimeException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::error('Failed to expire Stripe checkout session during cancellation: ' . json_encode([
                'payment_id' => (int)$payment->payment_id,
                'booking_id' => (int)$payment->booking_id,
                'portal_source' => $context['portal_source'] ?? 'unknown',
                'transaction_reference' => $transactionReference,
                'error' => $exception->getMessage(),
            ]));

            throw new RuntimeException('The checkout session could not be cancelled right now. Please try again.');
        }
    }

    private function findBookingPayments(int $bookingId)
    {
        return $this->paymentsTable->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->orderBy(['Payments.payment_id' => 'DESC'])
            ->all();
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

    private function supportsRowLocking(): bool
    {
        return $this->paymentsTable->getConnection()->getDriver() instanceof Mysql;
    }

    private function isStripeConfigured(): bool
    {
        $key = (string)Configure::read('Stripe.secret_key');

        return $key !== '' && $key !== 'sk_test_placeholder';
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
}
