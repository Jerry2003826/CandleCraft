<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PaymentRefundService;
use App\Test\Support\FakeStripeRefundGateway;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;
use RuntimeException;

class PaymentRefundServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Bookings',
        'app.Payments',
        'app.PaymentRefunds',
        'app.Classes',
        'app.Courses',
        'app.Students',
        'app.Users',
    ];

    protected function tearDown(): void
    {
        FakeStripeRefundGateway::reset();

        parent::tearDown();
    }

    public function testIssuePartialRefundUpdatesPaymentAndStoresRefund(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_refund_partial';
        $payments->saveOrFail($payment);

        FakeStripeRefundGateway::$createHandler = static function (array $payload): object {
            return (object)[
                'id' => 're_partial',
                'amount' => $payload['amount'],
                'currency' => 'aud',
                'status' => 'succeeded',
                'reason' => $payload['reason'],
                'charge' => 'ch_partial',
                'payment_intent' => $payload['payment_intent'],
            ];
        };

        $service = new PaymentRefundService(gateway: new FakeStripeRefundGateway());
        $service->issueRefund(1, 25.00, 'requested_by_customer', 1);

        $payment = $payments->get(1);
        $refund = FactoryLocator::get('Table')->get('PaymentRefunds')->find()
            ->where(['PaymentRefunds.stripe_refund_id' => 're_partial'])
            ->firstOrFail();

        $this->assertSame(2500, FakeStripeRefundGateway::$createdPayloads[0]['amount'] ?? null);
        $this->assertSame('pi_refund_partial', FakeStripeRefundGateway::$createdPayloads[0]['payment_intent'] ?? null);
        $this->assertSame('partially_refunded', $payment->payment_status);
        $this->assertSame(25.0, (float)$payment->refunded_amount);
        $this->assertSame('succeeded', $refund->status);
    }

    public function testIssueFullRefundMarksPaymentRefunded(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_refund_full';
        $payments->saveOrFail($payment);

        $service = new PaymentRefundService(gateway: new FakeStripeRefundGateway());
        $service->issueRefund(1, 50.00, 'requested_by_customer', 1);

        $payment = $payments->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('refunded', $payment->payment_status);
        $this->assertSame(50.0, (float)$payment->refunded_amount);
        $this->assertSame('cancelled', $booking->booking_status);
    }

    public function testFailedRefundRecordsFailureWithoutChangingPaymentStatus(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_refund_failure';
        $payments->saveOrFail($payment);

        FakeStripeRefundGateway::$createHandler = static function (): object {
            throw new RuntimeException('Stripe outage');
        };

        $service = new PaymentRefundService(gateway: new FakeStripeRefundGateway());

        try {
            $service->issueRefund(1, 10.00, 'requested_by_customer', 1);
            $this->fail('Expected refund failure was not thrown.');
        } catch (RuntimeException) {
            $payment = $payments->get(1);
            $failed = FactoryLocator::get('Table')->get('PaymentRefunds')->find()
                ->where(['PaymentRefunds.status' => 'failed'])
                ->firstOrFail();

            $this->assertSame('paid', $payment->payment_status);
            $this->assertSame(0.0, (float)$payment->refunded_amount);
            $this->assertStringContainsString('Stripe outage', (string)$failed->failure_message);
        }
    }

    public function testDuplicateRefundWebhookUpdatesExistingRefund(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_refund_webhook';
        $payments->saveOrFail($payment);

        $service = new PaymentRefundService(gateway: new FakeStripeRefundGateway());
        $refund = (object)[
            'id' => 're_webhook',
            'amount' => 1000,
            'currency' => 'aud',
            'status' => 'succeeded',
            'reason' => 'requested_by_customer',
            'charge' => 'ch_webhook',
            'payment_intent' => 'pi_refund_webhook',
        ];

        $service->syncRefundObject($refund);
        $service->syncRefundObject($refund);

        $count = FactoryLocator::get('Table')->get('PaymentRefunds')->find()
            ->where(['PaymentRefunds.stripe_refund_id' => 're_webhook'])
            ->count();
        $payment = $payments->get(1);

        $this->assertSame(1, $count);
        $this->assertSame(10.0, (float)$payment->refunded_amount);
    }
}
