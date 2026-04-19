<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PaymentCheckoutService;
use App\Service\PaymentConfirmationService;
use App\Test\Support\FakeStripeCheckoutGateway;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;

class PaymentCheckoutServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Bookings',
        'app.Payments',
        'app.Classes',
        'app.Courses',
        'app.Students',
        'app.Users',
    ];

    protected function tearDown(): void
    {
        FakeStripeCheckoutGateway::reset();
        Configure::delete('Stripe.secret_key');

        parent::tearDown();
    }

    public function testCompletedPendingSessionIsConfirmedAndNotExpired(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');

        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'complete',
                'payment_status' => 'paid',
                'amount_total' => 5000,
                'metadata' => ['booking_id' => 1, 'student_id' => 1],
            ]);
        };

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => 1])
            ->firstOrFail();

        $service = new PaymentCheckoutService(
            gateway: new FakeStripeCheckoutGateway(),
            paymentConfirmationService: new PaymentConfirmationService()
        );

        $result = $service->startCheckout($booking, [
            'success_url' => 'http://localhost/success',
            'cancel_url' => 'http://localhost/cancel',
            'portal_source' => 'service_test',
            'payer_id' => 4,
        ]);

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);
        $payments = FactoryLocator::get('Table')->get('Payments')->find()
            ->where(['Payments.booking_id' => 1])
            ->all()
            ->toList();

        $this->assertSame('already_paid', $result['kind']);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertCount(1, $payments);
        $this->assertCount(0, FakeStripeCheckoutGateway::$createdPayloads);
        $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$retrievedSessionIds);
    }

    public function testZeroAmountBookingConfirmsWithoutStripeSession(): void
    {
        Configure::write('Stripe.secret_key', null);
        Configure::write('Payments.demo_mode', false);

        $bookingId = $this->insertBooking([
            'class_id' => 2,
            'student_id' => 1,
            'parent_id' => null,
            'booking_status' => 'pending',
            'price_at_booking' => 0.00,
            'booking_date' => '2026-04-10 12:00:00',
            'created_at' => '2026-04-10 12:00:00',
            'updated_at' => '2026-04-10 12:00:00',
        ]);

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => $bookingId])
            ->firstOrFail();

        $service = new PaymentCheckoutService(gateway: new FakeStripeCheckoutGateway());

        $result = $service->startCheckout($booking, [
            'success_url' => 'http://localhost/success',
            'cancel_url' => 'http://localhost/cancel',
            'portal_source' => 'service_test',
            'payer_id' => 4,
        ]);

        $savedBooking = FactoryLocator::get('Table')->get('Bookings')->get($bookingId);
        $payments = FactoryLocator::get('Table')->get('Payments')->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->all()
            ->toList();

        $this->assertSame('completed', $result['kind']);
        $this->assertSame('zero_amount', $result['completed_reason']);
        $this->assertSame('confirmed', $savedBooking->booking_status);
        $this->assertCount(1, $payments);
        $this->assertSame('paid', $payments[0]->payment_status);
        $this->assertSame(0.0, (float)$payments[0]->amount);
        $this->assertCount(0, FakeStripeCheckoutGateway::$createdPayloads);
        $this->assertSame([], FakeStripeCheckoutGateway::$retrievedSessionIds);
    }

    public function testTransientSessionInspectionFailureDoesNotExpirePendingPayment(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');

        FakeStripeCheckoutGateway::$retrieveHandler = static function (): object {
            throw new \RuntimeException('stripe temporarily unavailable');
        };

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => 1])
            ->firstOrFail();

        $service = new PaymentCheckoutService(gateway: new FakeStripeCheckoutGateway());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to recover the current payment session. Please try again shortly.');

        try {
            $service->startCheckout($booking, [
                'success_url' => 'http://localhost/success',
                'cancel_url' => 'http://localhost/cancel',
                'portal_source' => 'service_test',
                'payer_id' => 4,
            ]);
        } finally {
            $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
            $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);
            $payments = FactoryLocator::get('Table')->get('Payments')->find()
                ->where(['Payments.booking_id' => 1])
                ->all()
                ->toList();

            $this->assertSame('pending', $payment->payment_status);
            $this->assertSame('pending', $booking->booking_status);
            $this->assertCount(1, $payments);
            $this->assertCount(0, FakeStripeCheckoutGateway::$createdPayloads);
            $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$retrievedSessionIds);
        }
    }

    public function testCompleteButUnpaidPendingSessionIsNotExpiredAndNotConfirmed(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');

        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
                'amount_total' => 5000,
                'metadata' => ['booking_id' => 1, 'student_id' => 1],
            ]);
        };

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => 1])
            ->firstOrFail();

        $service = new PaymentCheckoutService(
            gateway: new FakeStripeCheckoutGateway(),
            paymentConfirmationService: new PaymentConfirmationService()
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Your payment is still processing with Stripe. Please wait a moment and try again shortly.');

        try {
            $service->startCheckout($booking, [
                'success_url' => 'http://localhost/success',
                'cancel_url' => 'http://localhost/cancel',
                'portal_source' => 'service_test',
                'payer_id' => 4,
            ]);
        } finally {
            $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
            $savedBooking = FactoryLocator::get('Table')->get('Bookings')->get(1);

            $this->assertSame('pending', $payment->payment_status);
            $this->assertSame('pending', $savedBooking->booking_status);
            $this->assertCount(0, FakeStripeCheckoutGateway::$createdPayloads);
            $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$retrievedSessionIds);
        }
    }

    public function testZeroAmountCheckoutRechecksPriceUnderLock(): void
    {
        Configure::write('Stripe.secret_key', null);
        Configure::write('Payments.demo_mode', false);

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => 1])
            ->firstOrFail();
        $booking->price_at_booking = 0.00;

        $service = new PaymentCheckoutService(gateway: new FakeStripeCheckoutGateway());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Booking amount changed during checkout. Please retry.');

        try {
            $service->startCheckout($booking, [
                'success_url' => 'http://localhost/success',
                'cancel_url' => 'http://localhost/cancel',
                'portal_source' => 'service_test',
                'payer_id' => 4,
            ]);
        } finally {
            $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
            $savedBooking = FactoryLocator::get('Table')->get('Bookings')->get(1);
            $payments = FactoryLocator::get('Table')->get('Payments')->find()
                ->where(['Payments.booking_id' => 1])
                ->all()
                ->toList();

            $this->assertSame('pending', $payment->payment_status);
            $this->assertSame('pending', $savedBooking->booking_status);
            $this->assertCount(1, $payments);
        }
    }

    public function testZeroAmountOverrideExpiresExistingOpenStripeSessionBeforeVoidingPayment(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');

        $bookingId = $this->insertBooking([
            'class_id' => 2,
            'student_id' => 1,
            'parent_id' => null,
            'booking_status' => 'pending',
            'price_at_booking' => 0.00,
            'booking_date' => '2026-04-10 12:00:00',
            'created_at' => '2026-04-10 12:00:00',
            'updated_at' => '2026-04-10 12:00:00',
        ]);
        $pendingPaymentId = $this->insertPayment([
            'booking_id' => $bookingId,
            'amount' => 50.00,
            'currency_code' => 'AUD',
            'payment_date' => '2026-04-10 12:05:00',
            'payment_method' => 'online',
            'payment_status' => 'pending',
            'transaction_reference' => 'cs_zero_pending',
            'refunded_amount' => 0.00,
            'notes' => null,
            'created_at' => '2026-04-10 12:05:00',
            'updated_at' => '2026-04-10 12:05:00',
        ]);

        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'open',
                'payment_status' => 'unpaid',
                'amount_total' => 5000,
            ]);
        };

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => $bookingId])
            ->firstOrFail();

        $service = new PaymentCheckoutService(gateway: new FakeStripeCheckoutGateway());

        $result = $service->startCheckout($booking, [
            'success_url' => 'http://localhost/success',
            'cancel_url' => 'http://localhost/cancel',
            'portal_source' => 'service_test',
            'payer_id' => 4,
        ]);

        $voidedPayment = FactoryLocator::get('Table')->get('Payments')->get($pendingPaymentId);
        $payments = FactoryLocator::get('Table')->get('Payments')->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->orderByAsc('Payments.payment_id')
            ->all()
            ->toList();

        $this->assertSame('completed', $result['kind']);
        $this->assertSame(['cs_zero_pending'], FakeStripeCheckoutGateway::$expiredSessionIds);
        $this->assertSame('voided', $voidedPayment->payment_status);
        $this->assertCount(2, $payments);
        $this->assertSame('paid', $payments[1]->payment_status);
        $this->assertSame(0.0, (float)$payments[1]->amount);
    }

    public function testZeroAmountOverrideDoesNotVoidAlreadyPaidStripeSession(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');

        $bookingId = $this->insertBooking([
            'class_id' => 2,
            'student_id' => 1,
            'parent_id' => null,
            'booking_status' => 'pending',
            'price_at_booking' => 0.00,
            'booking_date' => '2026-04-10 12:00:00',
            'created_at' => '2026-04-10 12:00:00',
            'updated_at' => '2026-04-10 12:00:00',
        ]);
        $pendingPaymentId = $this->insertPayment([
            'booking_id' => $bookingId,
            'amount' => 50.00,
            'currency_code' => 'AUD',
            'payment_date' => '2026-04-10 12:05:00',
            'payment_method' => 'online',
            'payment_status' => 'pending',
            'transaction_reference' => 'cs_zero_paid',
            'refunded_amount' => 0.00,
            'notes' => null,
            'created_at' => '2026-04-10 12:05:00',
            'updated_at' => '2026-04-10 12:05:00',
        ]);

        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'complete',
                'payment_status' => 'paid',
                'amount_total' => 5000,
            ]);
        };

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => $bookingId])
            ->firstOrFail();

        $service = new PaymentCheckoutService(gateway: new FakeStripeCheckoutGateway());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('This booking already has a processed payment and requires manual review.');

        try {
            $service->startCheckout($booking, [
                'success_url' => 'http://localhost/success',
                'cancel_url' => 'http://localhost/cancel',
                'portal_source' => 'service_test',
                'payer_id' => 4,
            ]);
        } finally {
            $payment = FactoryLocator::get('Table')->get('Payments')->get($pendingPaymentId);
            $savedBooking = FactoryLocator::get('Table')->get('Bookings')->get($bookingId);
            $count = FactoryLocator::get('Table')->get('Payments')->find()
                ->where(['Payments.booking_id' => $bookingId])
                ->count();

            $this->assertSame('pending', $payment->payment_status);
            $this->assertSame('pending', $savedBooking->booking_status);
            $this->assertSame(1, $count);
            $this->assertSame([], FakeStripeCheckoutGateway::$expiredSessionIds);
        }
    }

    public function testDemoOverrideExpiresExistingOpenStripeSessionBeforeVoidingPayment(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');
        Configure::write('Payments.demo_mode', true);

        $bookingId = $this->insertBooking([
            'class_id' => 2,
            'student_id' => 1,
            'parent_id' => null,
            'booking_status' => 'pending',
            'price_at_booking' => 20.00,
            'booking_date' => '2026-04-10 12:00:00',
            'created_at' => '2026-04-10 12:00:00',
            'updated_at' => '2026-04-10 12:00:00',
        ]);
        $pendingPaymentId = $this->insertPayment([
            'booking_id' => $bookingId,
            'amount' => 20.00,
            'currency_code' => 'AUD',
            'payment_date' => '2026-04-10 12:05:00',
            'payment_method' => 'online',
            'payment_status' => 'pending',
            'transaction_reference' => 'cs_demo_pending',
            'refunded_amount' => 0.00,
            'notes' => null,
            'created_at' => '2026-04-10 12:05:00',
            'updated_at' => '2026-04-10 12:05:00',
        ]);

        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'open',
                'payment_status' => 'unpaid',
                'amount_total' => 2000,
            ]);
        };

        $booking = FactoryLocator::get('Table')->get('Bookings')->find()
            ->contain(['Students', 'Classes' => ['Courses']])
            ->where(['Bookings.booking_id' => $bookingId])
            ->firstOrFail();

        $service = new class(null, new FakeStripeCheckoutGateway()) extends PaymentCheckoutService {
            public function isStripeConfigured(): bool
            {
                return false;
            }

            public function isDemoModeEnabled(): bool
            {
                return true;
            }
        };

        $result = $service->startCheckout($booking, [
            'success_url' => 'http://localhost/success',
            'cancel_url' => 'http://localhost/cancel',
            'portal_source' => 'service_test',
            'payer_id' => 4,
        ]);

        $voidedPayment = FactoryLocator::get('Table')->get('Payments')->get($pendingPaymentId);
        $payments = FactoryLocator::get('Table')->get('Payments')->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->orderByAsc('Payments.payment_id')
            ->all()
            ->toList();

        $this->assertSame('completed', $result['kind']);
        $this->assertSame('demo', $result['completed_reason']);
        $this->assertSame(['cs_demo_pending'], FakeStripeCheckoutGateway::$expiredSessionIds);
        $this->assertSame('voided', $voidedPayment->payment_status);
        $this->assertCount(2, $payments);
        $this->assertSame('paid', $payments[1]->payment_status);
    }

    private function insertBooking(array $values): int
    {
        $connection = FactoryLocator::get('Table')->get('Bookings')->getConnection();
        $maxId = (int)($connection->execute('SELECT COALESCE(MAX(booking_id), 0) AS max_id FROM bookings')
            ->fetch('assoc')['max_id'] ?? 0);
        $bookingId = $maxId + 1;

        $connection->insert('bookings', ['booking_id' => $bookingId] + $values, [
            'booking_id' => 'integer',
            'class_id' => 'integer',
            'student_id' => 'integer',
            'parent_id' => 'integer',
            'booking_status' => 'string',
            'price_at_booking' => 'decimal',
            'booking_date' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        return $bookingId;
    }

    private function insertPayment(array $values): int
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->newEntity($values);
        $payments->saveOrFail($payment);

        return (int)$payment->payment_id;
    }
}
