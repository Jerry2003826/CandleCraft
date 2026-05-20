<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Datasource\FactoryLocator;
use Cake\ORM\Locator\LocatorInterface;
use RuntimeException;

class BookingCancellationService
{
    private object $bookingsTable;
    private object $paymentsTable;
    private PendingPaymentDispositionService $pendingPaymentDispositionService;

    /**
     * Construct.
     *
     * @param mixed $tableLocator Tablelocator.
     * @param mixed $gateway Gateway.
     * @param mixed $pendingPaymentDispositionService Pendingpaymentdispositionservice.
     * @return mixed
     */
    public function __construct(
        ?LocatorInterface $tableLocator = null,
        ?StripeCheckoutGatewayInterface $gateway = null,
        ?PendingPaymentDispositionService $pendingPaymentDispositionService = null,
    ) {
        $locator = $tableLocator ?? FactoryLocator::get('Table');
        $this->bookingsTable = $locator->get('Bookings');
        $this->paymentsTable = $locator->get('Payments');
        $resolvedGateway = $gateway ?? $this->buildGateway();
        $this->pendingPaymentDispositionService = $pendingPaymentDispositionService ?? new PendingPaymentDispositionService(
            $locator,
            $resolvedGateway,
        );
    }

    /**
     * Cancel booking.
     *
     * @param mixed $bookingId Bookingid.
     * @param mixed $context Context.
     */
    public function cancelBooking(int $bookingId, array $context = []): void
    {
        $connection = $this->paymentsTable->getConnection();
        $connection->transactional(function () use ($bookingId, $context): void {
            $booking = $this->loadBookingForUpdate($bookingId);

            if ($booking->booking_status === 'cancelled') {
                return;
            }

            $payments = $this->findBookingPaymentsForUpdate($bookingId);
            foreach ($payments as $payment) {
                if (in_array($payment->payment_status, ['paid', 'refund_required', 'partially_refunded', 'refunded', 'disputed'], true)) {
                    throw new RuntimeException(
                        'Paid bookings require a refund or manual review before they can be cancelled.',
                    );
                }
            }

            foreach ($payments as $payment) {
                if ($payment->payment_status !== 'pending') {
                    continue;
                }

                $this->pendingPaymentDispositionService->voidPendingPayment($payment, 'booking_cancelled', [
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                ]);
            }

            $booking->booking_status = 'cancelled';
            $this->bookingsTable->saveOrFail($booking);
        });
    }

    /**
     * Void pending payments for booking.
     *
     * @param mixed $bookingId Bookingid.
     * @param mixed $context Context.
     */
    public function voidPendingPaymentsForBooking(int $bookingId, array $context = []): int
    {
        $connection = $this->paymentsTable->getConnection();

        return $connection->transactional(function () use ($bookingId, $context): int {
            $this->loadBookingForUpdate($bookingId);
            $payments = $this->findBookingPaymentsForUpdate($bookingId);
            $voided = 0;

            foreach ($payments as $payment) {
                if ($payment->payment_status !== 'pending') {
                    continue;
                }

                $this->pendingPaymentDispositionService->voidPendingPayment($payment, 'checkout_cancelled', [
                    'portal_source' => (string)($context['portal_source'] ?? 'unknown'),
                ]);
                $voided++;
            }

            return $voided;
        });
    }

    /**
     * Find booking payments for update.
     *
     * @param mixed $bookingId Bookingid.
     * @return mixed
     */
    private function findBookingPaymentsForUpdate(int $bookingId): mixed
    {
        $query = $this->paymentsTable->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->orderBy(['Payments.payment_id' => 'DESC']);

        if ($this->supportsRowLocking()) {
            $query->epilog('FOR UPDATE');
        }

        return $query->all();
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
     * Supports row locking.
     */
    private function supportsRowLocking(): bool
    {
        return $this->paymentsTable->getConnection()->getDriver() instanceof Mysql;
    }

    /**
     * Build gateway.
     */
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
