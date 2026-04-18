<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Consumer;

use App\Test\Support\FakeStripeCheckoutGateway;
use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class PaymentsControllerTest extends AppIntegrationTestCase
{
    protected function tearDown(): void
    {
        FakeStripeCheckoutGateway::reset();
        Configure::delete('Payments.gateway_class');
        Configure::delete('Payments.demo_mode');
        Configure::delete('Stripe.secret_key');

        parent::tearDown();
    }

    public function testSuccessDoesNotExposeAnotherStudentsPayment(): void
    {
        $this->loginAsStudent();

        $this->get('/consumer/payments/success?session_id=cs_other');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/consumer/bookings');

        $payment = FactoryLocator::get('Table')->get('Payments')->get(2);
        $this->assertSame('pending', $payment->payment_status);
    }

    public function testSuccessKeepsPendingPaymentReadOnly(): void
    {
        $this->loginAsStudent();

        $this->get('/consumer/payments/success?session_id=cs_owned');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/consumer/bookings');

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame('pending', $booking->booking_status);
    }

    public function testProcessFailsClosedWhenStripeIsMissing(): void
    {
        Configure::write('Stripe.secret_key', null);
        Configure::write('Payments.demo_mode', false);
        $this->loginAsStudent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/consumer/payments/process/1');

        $this->assertResponseCode(200);
        $this->assertResponseContains('Online payments are temporarily unavailable.');

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);
        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame('pending', $booking->booking_status);
    }

    public function testProcessStripeDoesNotRedirectWhenPaymentSaveFails(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');
        Configure::write('Payments.gateway_class', FakeStripeCheckoutGateway::class);
        $bookingId = $this->insertBooking([
            'class_id' => 2,
            'student_id' => 1,
            'parent_id' => null,
            'booking_status' => 'pending',
            'price_at_booking' => 65.00,
            'booking_date' => '2026-04-10 12:00:00',
            'created_at' => '2026-04-10 12:00:00',
            'updated_at' => '2026-04-10 12:00:00',
        ]);

        FakeStripeCheckoutGateway::$createHandler = function (array $payload): object {
            return FakeStripeCheckoutGateway::makeSession('cs_owned', [
                'url' => 'https://checkout.stripe.test/failure',
                'amount_total' => 6500,
                'metadata' => $payload['metadata'],
            ]);
        };

        $this->loginAsStudent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/consumer/payments/process/' . $bookingId);

        $this->assertResponseCode(200);
        $this->assertResponseContains('Payment could not be initiated. Please try again later.');

        $paymentCount = FactoryLocator::get('Table')->get('Payments')->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->count();
        $this->assertSame(0, $paymentCount);
        $this->assertSame(['cs_owned'], FakeStripeCheckoutGateway::$expiredSessionIds);
    }

    public function testRepeatedPaymentSubmissionDoesNotCreateDuplicatePendingPayments(): void
    {
        Configure::write('Stripe.secret_key', 'sk_test_liveish');
        Configure::write('Payments.gateway_class', FakeStripeCheckoutGateway::class);

        $bookingId = $this->insertBooking([
            'class_id' => 2,
            'student_id' => 1,
            'parent_id' => null,
            'booking_status' => 'pending',
            'price_at_booking' => 65.00,
            'booking_date' => '2026-04-10 12:00:00',
            'created_at' => '2026-04-10 12:00:00',
            'updated_at' => '2026-04-10 12:00:00',
        ]);

        FakeStripeCheckoutGateway::$createHandler = function (array $payload): object {
            return FakeStripeCheckoutGateway::makeSession('cs_repeat_once', [
                'url' => 'https://checkout.stripe.test/repeat-once',
                'amount_total' => 6500,
                'metadata' => $payload['metadata'],
            ]);
        };
        FakeStripeCheckoutGateway::$retrieveHandler = function (string $sessionId) use ($bookingId): object {
            return FakeStripeCheckoutGateway::makeSession($sessionId, [
                'url' => 'https://checkout.stripe.test/repeat-once',
                'amount_total' => 6500,
                'metadata' => ['booking_id' => $bookingId, 'student_id' => 1],
            ]);
        };

        $this->loginAsStudent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/consumer/payments/process/' . $bookingId);
        $this->assertResponseCode(302);

        $this->post('/consumer/payments/process/' . $bookingId);
        $this->assertResponseCode(302);
        $this->assertRedirect('https://checkout.stripe.test/repeat-once');

        $payments = FactoryLocator::get('Table')->get('Payments')->find()
            ->where(['Payments.booking_id' => $bookingId])
            ->all();

        $this->assertCount(1, $payments);
        $this->assertSame(['cs_repeat_once'], array_column($payments->toList(), 'transaction_reference'));
        $this->assertCount(1, FakeStripeCheckoutGateway::$createdPayloads);
        $this->assertSame(['cs_repeat_once'], FakeStripeCheckoutGateway::$retrievedSessionIds);
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
