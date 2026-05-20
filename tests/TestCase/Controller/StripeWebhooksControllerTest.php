<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use App\Test\Support\FakePaymentConfirmationService;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class StripeWebhooksControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'app.PaymentWebhookIncidents',
        'app.StripeWebhookEvents',
        'app.Users',
        'app.Admins',
        'app.Notifications',
        'app.Bookings',
        'app.Payments',
        'app.PaymentRefunds',
        'app.PaymentDisputes',
        'app.Classes',
        'app.Courses',
        'app.Students',
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

        FakePaymentConfirmationService::$handler = static function (
            object $session,
            string $eventType,
            string $confirmationSource,
        ): string {
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

    public function testInProgressWebhookEventReturnsRetriableFailureInsteadOfDuplicateAck(): void
    {
        $secret = 'whsec_test';
        $freshTime = DateTime::now()->subMinutes(5);
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        $events = FactoryLocator::get('Table')->get('StripeWebhookEvents');
        $events->saveOrFail($events->newEntity([
            'event_id' => 'evt_test_cs_in_progress',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_in_progress',
            'business_event_key' => 'checkout.session.completed:cs_in_progress',
            'payload_hash' => hash('sha256', $this->completedSessionPayload('cs_in_progress')),
            'processing_status' => 'processing',
            'first_seen_at' => $freshTime,
            'processing_started_at' => $freshTime,
            'last_seen_at' => $freshTime,
        ]));

        $payload = $this->completedSessionPayload('cs_in_progress');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(503);
        $this->assertResponseNotContains('"duplicate":true');
        $this->assertCount(0, FakePaymentConfirmationService::$receivedSessions);
    }

    public function testSuspiciousWebhookEventReturnsTerminalAckWithoutProcessing(): void
    {
        $secret = 'whsec_test';
        $freshTime = DateTime::now()->subMinutes(5);
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        $events = FactoryLocator::get('Table')->get('StripeWebhookEvents');
        $events->saveOrFail($events->newEntity([
            'event_id' => 'evt_test_cs_suspicious',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_original_suspicious',
            'business_event_key' => 'checkout.session.completed:cs_original_suspicious',
            'payload_hash' => hash('sha256', $this->completedSessionPayload('cs_original_suspicious')),
            'processing_status' => 'processing',
            'first_seen_at' => $freshTime,
            'processing_started_at' => $freshTime,
            'last_seen_at' => $freshTime,
        ]));

        $payload = $this->completedSessionPayload('cs_replayed_suspicious', 'evt_test_cs_suspicious');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $event = $events->find()
            ->where(['event_id' => 'evt_test_cs_suspicious'])
            ->firstOrFail();

        $this->assertResponseCode(200);
        $this->assertResponseContains('"suspicious":true');
        $this->assertCount(0, FakePaymentConfirmationService::$receivedSessions);
        $this->assertSame('processing', $event->processing_status);
        $this->assertSame('checkout.session.completed:cs_original_suspicious', $event->business_event_key);
        $this->assertSame('suspicious', $event->suspicious_state);
        $this->assertSame('event_id_business_key_mismatch', $event->suspicious_reason_code);
        $this->assertSame('checkout.session.completed:cs_replayed_suspicious', $event->suspicious_business_event_key);
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
        $this->assertSame(
            'checkout.session.async_payment_succeeded',
            FakePaymentConfirmationService::$receivedEventTypes[0] ?? null,
        );
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

    public function testExpiredPaymentUsesExpirationHandler(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        $payload = $this->sessionPayload('checkout.session.expired', 'cs_expired');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(200);
        $this->assertCount(1, FakePaymentConfirmationService::$expiredSessions);
        $this->assertSame('cs_expired', FakePaymentConfirmationService::$expiredSessions[0]->id);
    }

    public function testWebhookReturns200ForManualReviewFailure(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (
            object $session,
            string $eventType,
            string $confirmationSource,
        ): string {
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

        FakePaymentConfirmationService::$handler = static function (
            object $session,
            string $eventType,
            string $confirmationSource,
        ): string {
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

        FakePaymentConfirmationService::$handler = static function (
            object $session,
            string $eventType,
            string $confirmationSource,
        ): string {
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

    public function testDifferentEventIdForSameBusinessEventDoesNotCreateDuplicateIncident(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (
            object $session,
            string $eventType,
            string $confirmationSource,
        ): string {
            throw new ManualReviewWebhookException('Cancelled booking paid late.', [
                'session_id' => 'cs_business_duplicate',
                'reason_code' => 'cancelled_booking_paid_late',
            ]);
        };

        $firstPayload = $this->sessionPayload(
            'checkout.session.completed',
            'cs_business_duplicate',
            'evt_business_duplicate_a',
        );
        $secondPayload = $this->sessionPayload(
            'checkout.session.completed',
            'cs_business_duplicate',
            'evt_business_duplicate_b',
        );

        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($firstPayload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->post('/stripe/webhook', $firstPayload);
        $this->assertResponseCode(200);

        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($secondPayload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);
        $this->post('/stripe/webhook', $secondPayload);

        $this->assertResponseCode(200);
        $this->assertResponseContains('"duplicate":true');
        $this->assertCount(1, FakePaymentConfirmationService::$receivedSessions);

        $incidents = FactoryLocator::get('Table')->get('PaymentWebhookIncidents')
            ->find()
            ->where(['session_id' => 'cs_business_duplicate'])
            ->all()
            ->toList();

        $this->assertCount(1, $incidents);
    }

    public function testResolvedIncidentWithProcessedEventIdDoesNotReopenOnDuplicateWebhook(): void
    {
        $secret = 'whsec_test';
        $resolvedTime = DateTime::now()->subMinutes(5);
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        $events = FactoryLocator::get('Table')->get('StripeWebhookEvents');
        $events->saveOrFail($events->newEntity([
            'event_id' => 'evt_test_cs_resolved_duplicate',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_resolved_duplicate',
            'business_event_key' => 'checkout.session.completed:cs_resolved_duplicate',
            'payload_hash' => hash('sha256', $this->completedSessionPayload('cs_resolved_duplicate')),
            'processing_status' => 'processed',
            'first_seen_at' => $resolvedTime,
            'processing_started_at' => $resolvedTime,
            'last_seen_at' => $resolvedTime,
        ]));

        $incidents = FactoryLocator::get('Table')->get('PaymentWebhookIncidents');
        $incidents->saveOrFail($incidents->newEntity([
            'event_id' => 'evt_test_cs_resolved_duplicate',
            'event_type' => 'checkout.session.completed',
            'session_id' => 'cs_resolved_duplicate',
            'reason_code' => 'cancelled_booking_paid_late',
            'severity' => 'error',
            'status' => 'resolved',
            'context_json' => '{}',
            'payload_hash' => hash('sha256', $this->completedSessionPayload('cs_resolved_duplicate')),
            'notes' => 'Previously resolved incident.',
            'created_at' => $resolvedTime,
            'updated_at' => $resolvedTime,
            'resolved_at' => DateTime::now(),
        ]));

        $payload = $this->completedSessionPayload('cs_resolved_duplicate');
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseCode(200);
        $this->assertResponseContains('"duplicate":true');
        $this->assertCount(0, FakePaymentConfirmationService::$receivedSessions);

        $allIncidents = $incidents->find()
            ->where(['session_id' => 'cs_resolved_duplicate'])
            ->all()
            ->toList();

        $this->assertCount(1, $allIncidents);
        $this->assertSame('resolved', $allIncidents[0]->status);
    }

    public function testRetriableWebhookFailureDoesNotCreateIncident(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);
        Configure::write('Payments.confirmation_service_class', FakePaymentConfirmationService::class);

        FakePaymentConfirmationService::$handler = static function (
            object $session,
            string $eventType,
            string $confirmationSource,
        ): string {
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

    public function testRefundWebhookSyncsLocalPayment(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_refund_webhook_controller';
        $payment->stripe_charge_id = 'ch_refund_webhook_controller';
        $payments->saveOrFail($payment);

        $payload = (string)json_encode([
            'id' => 'evt_refund_webhook_controller',
            'type' => 'refund.created',
            'data' => [
                'object' => [
                    'id' => 're_webhook_controller',
                    'amount' => 1000,
                    'currency' => 'aud',
                    'status' => 'succeeded',
                    'reason' => 'requested_by_customer',
                    'charge' => 'ch_refund_webhook_controller',
                    'payment_intent' => 'pi_refund_webhook_controller',
                ],
            ],
        ]);
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseOk();
        $payment = $payments->get(1);
        $this->assertSame('partially_refunded', $payment->payment_status);
        $this->assertSame(10.0, (float)$payment->refunded_amount);
    }

    public function testDisputeWebhookCreatesLocalDispute(): void
    {
        $secret = 'whsec_test';
        Configure::write('Stripe.webhook_secret', $secret);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_dispute_webhook_controller';
        $payment->stripe_charge_id = 'ch_dispute_webhook_controller';
        $payments->saveOrFail($payment);

        $payload = (string)json_encode([
            'id' => 'evt_dispute_webhook_controller',
            'type' => 'charge.dispute.created',
            'data' => [
                'object' => [
                    'id' => 'dp_webhook_controller',
                    'amount' => 5000,
                    'currency' => 'aud',
                    'reason' => 'fraudulent',
                    'status' => 'needs_response',
                    'charge' => 'ch_dispute_webhook_controller',
                    'payment_intent' => 'pi_dispute_webhook_controller',
                    'created' => 1777046400,
                    'evidence_details' => [
                        'due_by' => 1777651200,
                    ],
                ],
            ],
        ]);
        $this->configRequest([
            'headers' => [
                'Stripe-Signature' => $this->signatureForPayload($payload, $secret),
                'Content-Type' => 'application/json',
            ],
        ]);

        $this->post('/stripe/webhook', $payload);

        $this->assertResponseOk();
        $dispute = FactoryLocator::get('Table')->get('PaymentDisputes')->find()
            ->where(['PaymentDisputes.stripe_dispute_id' => 'dp_webhook_controller'])
            ->firstOrFail();
        $this->assertSame(1, (int)$dispute->payment_id);
        $this->assertSame('needs_response', $dispute->status);
    }

    private function completedSessionPayload(string $sessionId, ?string $eventId = null): string
    {
        return $this->sessionPayload('checkout.session.completed', $sessionId, $eventId);
    }

    private function sessionPayload(string $eventType, string $sessionId, ?string $eventId = null): string
    {
        return (string)json_encode([
            'id' => $eventId ?? 'evt_test_' . $sessionId,
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
