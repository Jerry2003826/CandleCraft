<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PendingPaymentDispositionService;
use App\Test\Support\FakeStripeCheckoutGateway;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;
use RuntimeException;

class PendingPaymentDispositionServiceTest extends TestCase
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

    public function testAwaitingPaymentSessionPreventsVoidingPendingPayment(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');
        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]);
        };

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $service = new PendingPaymentDispositionService(gateway: new FakeStripeCheckoutGateway());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Your payment is still processing with Stripe. Please wait a moment and try again shortly.');

        try {
            $service->voidPendingPayment($payment, 'booking_cancelled', [
                'portal_source' => 'service_test',
            ]);
        } finally {
            $savedPayment = $payments->get(1);
            $this->assertSame('pending', $savedPayment->payment_status);
            $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$retrievedSessionIds);
            $this->assertSame([], FakeStripeCheckoutGateway::$expiredSessionIds);
        }
    }

    public function testContextCannotOverrideCoreDispositionAuditFields(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->transaction_reference = 'manual-review-reference';
        $payments->saveOrFail($payment);

        $service = new PendingPaymentDispositionService(gateway: new FakeStripeCheckoutGateway());
        $service->voidPendingPayment($payment, 'booking_cancelled', [
            'portal_source' => 'service_test',
            'payment_resolution' => 'forged_resolution',
            'checkout_session_expired' => true,
            'custom_flag' => 'keep_me',
        ]);

        $savedPayment = $payments->get(1);
        $notes = json_decode((string)$savedPayment->notes, true);

        $this->assertSame('voided', $savedPayment->payment_status);
        $this->assertSame('booking_cancelled', $notes['payment_resolution'] ?? null);
        $this->assertSame('service_test', $notes['portal_source'] ?? null);
        $this->assertFalse((bool)($notes['checkout_session_expired'] ?? true));
        $this->assertTrue((bool)($notes['checkout_session_expiration_skipped'] ?? false));
        $this->assertSame('forged_resolution', $notes['disposition_context']['payment_resolution'] ?? null);
        $this->assertTrue((bool)($notes['disposition_context']['checkout_session_expired'] ?? false));
        $this->assertSame('keep_me', $notes['disposition_context']['custom_flag'] ?? null);
    }

    public function testGatewayRuntimeExceptionIsWrappedAsGenericDispositionFailure(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');
        FakeStripeCheckoutGateway::$retrieveHandler = static function (): object {
            throw new RuntimeException('stripe transport failed');
        };

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $service = new PendingPaymentDispositionService(gateway: new FakeStripeCheckoutGateway());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The checkout session could not be cancelled right now. Please try again.');

        try {
            $service->voidPendingPayment($payment, 'booking_cancelled', [
                'portal_source' => 'service_test',
            ]);
        } finally {
            $savedPayment = $payments->get(1);
            $this->assertSame('pending', $savedPayment->payment_status);
            $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$retrievedSessionIds);
            $this->assertSame([], FakeStripeCheckoutGateway::$expiredSessionIds);
        }
    }

    public function testOpenUnknownPaymentStatusPreventsVoidingPendingPayment(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');
        FakeStripeCheckoutGateway::$retrieveHandler = static function (string $sessionId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'status' => 'open',
                'payment_status' => 'processing',
            ]);
        };

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $service = new PendingPaymentDispositionService(gateway: new FakeStripeCheckoutGateway());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'The current payment session is still open with an unknown payment status. Please try again shortly.',
        );

        try {
            $service->voidPendingPayment($payment, 'booking_cancelled', [
                'portal_source' => 'service_test',
            ]);
        } finally {
            $savedPayment = $payments->get(1);
            $this->assertSame('pending', $savedPayment->payment_status);
            $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$retrievedSessionIds);
            $this->assertSame([], FakeStripeCheckoutGateway::$expiredSessionIds);
        }
    }
}
