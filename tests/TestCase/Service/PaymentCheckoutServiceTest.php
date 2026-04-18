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
}
