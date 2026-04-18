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
}
