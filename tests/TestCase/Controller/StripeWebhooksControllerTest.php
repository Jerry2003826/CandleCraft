<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use App\Test\Support\FakePaymentConfirmationService;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class StripeWebhooksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.PaymentWebhookIncidents',
    ];

    protected function tearDown(): void
    {
        FakePaymentConfirmationService::reset();
        Configure::delete('Payments.confirmation_service_class');
        Configure::delete('Stripe.webhook_secret');

        parent::tearDown();
    }

    public function testInvalidSignatureReturnsBadRequestWithoutLoginRedirect(): void
    {
        Configure::write('Stripe.webhook_secret', 'whsec_test');

        $this->post('/stripe/webhook', '{}');

        $this->assertResponseCode(400);
        $this->assertResponseNotContains('login');
    }

    public function testLegacyConsumerWebhookReturnsGone(): void
    {
        $this->post('/consumer/payments/webhook', '{}');

        $this->assertResponseCode(410);
        $this->assertResponseContains('Stripe webhook endpoint has moved');
    }

    public function testLegacyStudentWebhookReturnsGone(): void
    {
        $this->post('/student/payments/webhook', '{}');

        $this->assertResponseCode(410);
        $this->assertResponseContains('Stripe webhook endpoint has moved');
    }

    public function testWebhookReturns500ForRetriableFailure(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (): string {
            throw new RetriableWebhookException('Temporary database issue.', [
                'session_id' => 'cs_retry',
                'reason_code' => 'temporary_db_failure',
            ]);
        };

        $payload = $this->completedSessionPayload('cs_retry');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(500);
    }

    public function testAsyncPaymentSucceededUsesConfirmationService(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        $payload = $this->sessionPayload('checkout.session.async_payment_succeeded', 'cs_async_success');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(200);
        $this->assertCount(1, FakePaymentConfirmationService::$receivedSessions);
        $this->assertSame('cs_async_success', FakePaymentConfirmationService::$receivedSessions[0]->id);
    }

    public function testAsyncPaymentFailedUsesFailureHandler(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        $payload = $this->sessionPayload('checkout.session.async_payment_failed', 'cs_async_failed');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(200);
        $this->assertCount(1, FakePaymentConfirmationService::$failedSessions);
        $this->assertSame('cs_async_failed', FakePaymentConfirmationService::$failedSessions[0]->id);
    }

    public function testWebhookReturns200ForManualReviewFailure(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (): string {
            throw new ManualReviewWebhookException('Cancelled booking paid late.', [
                'session_id' => 'cs_manual_review',
                'reason_code' => 'cancelled_booking_paid_late',
            ]);
        };

        $payload = $this->completedSessionPayload('cs_manual_review');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(200);
        $this->assertResponseContains('"received":true');
        $incident = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_manual_review'])
            ->first();
        $this->assertNotNull($incident);
        $this->assertSame('open', $incident->status);
        $this->assertSame('error', $incident->severity);
        $this->assertSame('cancelled_booking_paid_late', $incident->reason_code);
    }

    public function testWebhookReturns200ForNonRetriableFailureAndCreatesIncident(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (): string {
            throw new NonRetriableWebhookException('Stripe amount mismatch.', [
                'session_id' => 'cs_non_retriable',
                'payment_id' => 1,
                'booking_id' => 1,
                'reason_code' => 'amount_mismatch',
            ]);
        };

        $payload = $this->completedSessionPayload('cs_non_retriable');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(200);
        $incident = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_non_retriable'])
            ->first();
        $this->assertNotNull($incident);
        $this->assertSame('open', $incident->status);
        $this->assertSame('warning', $incident->severity);
        $this->assertSame('amount_mismatch', $incident->reason_code);
    }

    public function testDuplicateWebhookEventIdDoesNotCreateDuplicateOpenIncidents(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (): string {
            throw new ManualReviewWebhookException('Cancelled booking paid late.', [
                'session_id' => 'cs_duplicate_event',
                'reason_code' => 'cancelled_booking_paid_late',
            ]);
        };

        $payload = $this->sessionPayload('checkout.session.completed', 'cs_duplicate_event');
        $headers = [
            'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
            'Content-Type' => 'application/json',
        ];

        $this->configRequest(['headers' => $headers]);
        $this->post('/stripe/webhook', $payload);
        $this->assertResponseCode(200);

        $this->configRequest(['headers' => $headers]);
        $this->post('/stripe/webhook', $payload);
        $this->assertResponseCode(200);

        $incidents = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_duplicate_event'])
            ->all()
            ->toList();

        $this->assertCount(1, $incidents);
        $this->assertSame('open', $incidents[0]->status);
    }

    public function testRetriableWebhookFailureDoesNotCreateIncident(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (): string {
            throw new RetriableWebhookException('Temporary database issue.', [
                'session_id' => 'cs_retry_again',
                'reason_code' => 'temporary_db_failure',
            ]);
        };

        $payload = $this->completedSessionPayload('cs_retry_again');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(500);
        $count = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_retry_again'])
            ->count();
        $this->assertSame(0, $count);
    }

    private function completedSessionPayload(string $sessionId): string
    {
        return $this->sessionPayload('checkout.session.completed', $sessionId);
    }

    private function sessionPayload(string $eventType, string $sessionId): string
    {
        return (string)json_encode([
            'id' => 'evt_test_' . $sessionId,
            'type' => $eventType,
            'data' => [
                'object' => [
                    'id' => $sessionId,
                    'amount_total' => 5000,
                    'currency' => 'aud',
                    'payment_intent' => 'pi_' . $sessionId,
                    'payment_status' => 'paid',
                    'metadata' => [
                        'booking_id' => 1,
                    ],
                ],
            ],
        ]);
    }

    private function signatureForPayload(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }
}
