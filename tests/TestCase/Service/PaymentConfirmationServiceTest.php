<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Exception\Payments\ManualReviewWebhookException;
use App\Exception\Payments\NonRetriableWebhookException;
use App\Exception\Payments\RetriableWebhookException;
use App\Service\PaymentConfirmationService;
use Cake\Datasource\FactoryLocator;
use Cake\I18n\DateTime;
use Cake\TestSuite\TestCase;
use RuntimeException;

class PaymentConfirmationServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Bookings',
        'app.Payments',
        'app.Classes',
        'app.Courses',
        'app.Students',
        'app.Users',
    ];

    private PaymentConfirmationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentConfirmationService();
    }

    public function testWebhookConfirmsPendingBooking(): void
    {
        $result = $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('confirmed', $result);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('confirmed', $booking->booking_status);
    }

    public function testWebhookStoresStripeReconciliationFields(): void
    {
        $session = $this->makeSession('cs_owned', 1, 5000, [
            'payment_intent' => (object)[
                'id' => 'pi_reconcile',
                'latest_charge' => (object)[
                    'id' => 'ch_reconcile',
                    'receipt_url' => 'https://pay.stripe.test/receipts/ch_reconcile',
                    'payment_method_details' => (object)[
                        'type' => 'card',
                    ],
                ],
            ],
            'customer' => 'cus_reconcile',
            'invoice' => (object)[
                'id' => 'in_reconcile',
                'invoice_pdf' => 'https://pay.stripe.test/invoices/in_reconcile.pdf',
            ],
        ]);

        $this->service->confirmCheckoutSession($session);

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);

        $this->assertSame('cs_owned', $payment->stripe_session_id);
        $this->assertSame('pi_reconcile', $payment->stripe_payment_intent_id);
        $this->assertSame('ch_reconcile', $payment->stripe_charge_id);
        $this->assertSame('cus_reconcile', $payment->stripe_customer_id);
        $this->assertSame('in_reconcile', $payment->stripe_invoice_id);
        $this->assertSame('https://pay.stripe.test/invoices/in_reconcile.pdf', $payment->stripe_invoice_pdf_url);
        $this->assertSame('https://pay.stripe.test/receipts/ch_reconcile', $payment->stripe_receipt_url);
        $this->assertSame('card', $payment->stripe_payment_method_type);
    }

    public function testWebhookThrowsManualReviewForCancelledBooking(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'cancelled';
        $bookings->saveOrFail($booking);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('cancelled_booking_paid_late', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertNotNull($payment->payment_date);
        $this->assertSame('cancelled', $booking->booking_status);
    }

    public function testRefundReviewOverwritesLegacyPendingPaymentDateWhenFundsAreCaptured(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $bookings = FactoryLocator::get('Table')->get('Bookings');

        $legacyPendingDate = new DateTime('2026-04-10 10:00:00');

        $payment = $payments->get(1);
        $payment->payment_status = 'pending';
        $payment->payment_date = $legacyPendingDate;
        $payments->saveOrFail($payment);

        $booking = $bookings->get(1);
        $booking->booking_status = 'cancelled';
        $bookings->saveOrFail($booking);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('cancelled_booking_paid_late', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = $payments->get(1);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertNotNull($payment->payment_date);
        $this->assertGreaterThan(
            $legacyPendingDate->getTimestamp(),
            $payment->payment_date->getTimestamp(),
        );
    }

    public function testRefundedPaymentIgnoresDuplicateCompletedWebhook(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'refunded';
        $payment->notes = json_encode([
            'manual_review_required' => false,
            'refund_required' => false,
            'funds_captured' => true,
            'reason_code' => 'payment_fully_refunded',
        ]);
        $payments->saveOrFail($payment);

        $result = $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('idempotent', $result);
        $this->assertSame('refunded', $payment->payment_status);
        $this->assertFalse((bool)($notes['manual_review_required'] ?? true));
        $this->assertFalse((bool)($notes['refund_required'] ?? true));
        $this->assertSame('duplicate_completed_event_after_refund', $notes['reason_code'] ?? null);
    }

    public function testPartiallyRefundedPaymentIgnoresDuplicateCompletedWebhook(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'partially_refunded';
        $payment->notes = json_encode([
            'manual_review_required' => false,
            'refund_required' => false,
            'funds_captured' => true,
            'reason_code' => 'payment_partially_refunded',
        ]);
        $payments->saveOrFail($payment);

        $result = $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('idempotent', $result);
        $this->assertSame('partially_refunded', $payment->payment_status);
        $this->assertFalse((bool)($notes['manual_review_required'] ?? true));
        $this->assertFalse((bool)($notes['refund_required'] ?? true));
        $this->assertSame('duplicate_completed_event_after_partial_refund', $notes['reason_code'] ?? null);
    }

    public function testWebhookIsIdempotentForAlreadyPaidPayment(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $bookings = FactoryLocator::get('Table')->get('Bookings');

        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payments->saveOrFail($payment);

        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $result = $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));

        $payment = $payments->get(1);
        $booking = $bookings->get(1);

        $this->assertSame('idempotent', $result);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('confirmed', $booking->booking_status);
    }

    public function testWebhookThrowsNonRetriableForAmountMismatch(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 9999));
            $this->fail('Expected non-retriable exception was not thrown.');
        } catch (NonRetriableWebhookException $exception) {
            $this->assertSame('amount_mismatch', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertNotNull($payment->payment_date);
        $this->assertStringContainsString('manual_review_required', (string)$payment->notes);
        $this->assertStringContainsString('amount_mismatch', (string)$payment->notes);
    }

    public function testWebhookThrowsRetriableWhenPaymentPersistenceFails(): void
    {
        $service = new class () extends PaymentConfirmationService {
            protected function persistPayment(object $payment): void
            {
                throw new RuntimeException('database temporarily unavailable');
            }
        };

        try {
            $service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));
            $this->fail('Expected retriable exception was not thrown.');
        } catch (RetriableWebhookException $exception) {
            $this->assertSame('payment_confirmation', $exception->getContext()['reason_code'] ?? null);
        }
    }

    public function testLateWebhookForVoidedPaymentDoesNotMarkItPaid(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $bookings = FactoryLocator::get('Table')->get('Bookings');

        $payment = $payments->get(1);
        $payment->payment_status = 'voided';
        $payments->saveOrFail($payment);

        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame(
                'completed_after_local_payment_voided_or_expired',
                $exception->getContext()['reason_code'] ?? null,
            );
        }

        $payment = $payments->get(1);
        $booking = $bookings->get(1);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertNotNull($payment->payment_date);
        $this->assertSame('confirmed', $booking->booking_status);
    }

    public function testLateWebhookForExpiredPaymentDoesNotMarkItPaid(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');

        $payment = $payments->get(1);
        $payment->payment_status = 'expired';
        $payments->saveOrFail($payment);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame(
                'completed_after_local_payment_voided_or_expired',
                $exception->getContext()['reason_code'] ?? null,
            );
        }

        $payment = $payments->get(1);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertNotNull($payment->payment_date);
    }

    public function testLateWebhookForVoidedPaymentWithoutPaidStatusDoesNotRequireRefund(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');

        $payment = $payments->get(1);
        $payment->payment_status = 'voided';
        $payments->saveOrFail($payment);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame(
                'completed_after_local_payment_voided_or_expired_without_paid_status',
                $exception->getContext()['reason_code'] ?? null,
            );
        }

        $payment = $payments->get(1);

        $this->assertSame('voided', $payment->payment_status);
        $this->assertStringContainsString('"refund_required":false', (string)$payment->notes);
        $this->assertStringContainsString('"funds_captured":false', (string)$payment->notes);
    }

    public function testCompletedSessionWithUnpaidPaymentStatusDoesNotConfirmBooking(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('checkout_completed_without_paid_status', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('pending', $payment->payment_status);
        $this->assertSame('pending', $booking->booking_status);
        $this->assertStringContainsString('checkout_completed_without_paid_status', (string)$payment->notes);
        $this->assertStringContainsString('"funds_captured":false', (string)$payment->notes);
        $this->assertStringContainsString('"refund_required":false', (string)$payment->notes);
    }

    public function testUnexpectedLocalStatusWithUnpaidCompletedSessionDoesNotRequireRefund(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'failed';
        $payments->saveOrFail($payment);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('unexpected_local_payment_status', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = $payments->get(1);
        $this->assertSame('failed', $payment->payment_status);
        $this->assertStringContainsString('"refund_required":false', (string)$payment->notes);
        $this->assertStringContainsString('"funds_captured":false', (string)$payment->notes);
    }

    public function testPaidCheckoutWithUnexpectedBookingStatusEntersRefundReview(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $bookings = FactoryLocator::get('Table')->get('Bookings');

        $booking = $bookings->get(1);
        $booking->booking_status = 'waitlisted';
        $bookings->saveOrFail($booking);

        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame(
                'unexpected_booking_status_after_paid_checkout',
                $exception->getContext()['reason_code'] ?? null,
            );
        }

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertNotNull($payment->payment_date);
        $this->assertTrue((bool)($notes['funds_captured'] ?? false));
        $this->assertTrue((bool)($notes['refund_required'] ?? false));
        $this->assertSame(
            'captured_payment_with_unexpected_booking_status',
            $notes['review_state'] ?? null,
        );
    }

    public function testAsyncPaymentFailedMarksPendingPaymentFailed(): void
    {
        $result = $this->service->markCheckoutSessionFailed($this->makeSession('cs_owned', 1, 5000, [
            'status' => 'complete',
            'payment_status' => 'unpaid',
        ]));

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('failed', $result);
        $this->assertSame('failed', $payment->payment_status);
        $this->assertSame('pending', $booking->booking_status);
        $this->assertStringContainsString('checkout.session.async_payment_failed', (string)$payment->notes);
    }

    public function testAsyncPaymentSucceededClearsManualReviewMarkers(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException) {
        }

        $result = $this->service->confirmCheckoutSession(
            $this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'paid',
            ]),
            'checkout.session.async_payment_succeeded',
        );

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('confirmed', $result);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('confirmed', $booking->booking_status);
        $this->assertFalse((bool)($notes['manual_review_required'] ?? true));
        $this->assertSame('resolved_by_payment_confirmation', $notes['review_state'] ?? null);
        $this->assertSame('checkout.session.async_payment_succeeded', $notes['event_type'] ?? null);
        $this->assertSame('payment_confirmed', $notes['reason_code'] ?? null);
        $this->assertTrue((bool)($notes['funds_captured'] ?? false));
        $this->assertFalse((bool)($notes['refund_required'] ?? true));
        $this->assertSame('stripe_webhook', $notes['confirmation_source'] ?? null);
    }

    public function testAsyncPaymentFailedClearsManualReviewMarkers(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException) {
        }

        $result = $this->service->markCheckoutSessionFailed($this->makeSession('cs_owned', 1, 5000, [
            'status' => 'complete',
            'payment_status' => 'unpaid',
        ]));

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('failed', $result);
        $this->assertSame('failed', $payment->payment_status);
        $this->assertFalse((bool)($notes['manual_review_required'] ?? true));
        $this->assertSame('resolved_by_async_payment_failure', $notes['review_state'] ?? null);
        $this->assertSame('checkout.session.async_payment_failed', $notes['event_type'] ?? null);
        $this->assertSame('stripe_async_payment_failed', $notes['reason_code'] ?? null);
        $this->assertFalse((bool)($notes['funds_captured'] ?? true));
        $this->assertFalse((bool)($notes['refund_required'] ?? true));
    }

    public function testDuplicateCompletedEventDoesNotOverwriteAsyncSuccessAuditTrail(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException) {
        }

        $this->service->confirmCheckoutSession(
            $this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'paid',
            ]),
            'checkout.session.async_payment_succeeded',
        );

        $this->service->confirmCheckoutSession(
            $this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'paid',
            ]),
            'checkout.session.completed',
        );

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('checkout.session.async_payment_succeeded', $notes['event_type'] ?? null);
        $this->assertSame('checkout.session.async_payment_succeeded', $notes['last_stripe_event_type'] ?? null);
        $this->assertSame(
            ['checkout.session.completed', 'checkout.session.async_payment_succeeded'],
            $notes['confirmation_events'] ?? null,
        );
    }

    public function testInvalidBookingMetadataIsRejected(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'metadata' => (object)['booking_id' => '1abc'],
            ]));
            $this->fail('Expected non-retriable exception was not thrown.');
        } catch (NonRetriableWebhookException $exception) {
            $this->assertSame('invalid_booking_metadata', $exception->getContext()['reason_code'] ?? null);
        }
    }

    public function testInvalidClientReferenceIdIsRejected(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'client_reference_id' => 'booking:abc:attempt:not-valid',
            ]));
            $this->fail('Expected non-retriable exception was not thrown.');
        } catch (NonRetriableWebhookException $exception) {
            $this->assertSame('invalid_client_reference_id', $exception->getContext()['reason_code'] ?? null);
        }
    }

    public function testClientReferenceIdMismatchIsRejected(): void
    {
        try {
            $this->service->confirmCheckoutSession($this->makeSession('cs_owned', 1, 5000, [
                'client_reference_id' => 'booking:999:attempt:abcdef1234567890',
            ]));
            $this->fail('Expected non-retriable exception was not thrown.');
        } catch (NonRetriableWebhookException $exception) {
            $this->assertSame('client_reference_id_mismatch', $exception->getContext()['reason_code'] ?? null);
        }
    }

    public function testExpiredWebhookMarksPendingPaymentExpired(): void
    {
        $result = $this->service->markCheckoutSessionExpired($this->makeSession('cs_owned', 1, 5000, [
            'status' => 'expired',
            'payment_status' => 'unpaid',
        ]));

        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);

        $this->assertSame('expired', $result);
        $this->assertSame('expired', $payment->payment_status);
        $this->assertSame('pending', $booking->booking_status);
        $this->assertStringContainsString('checkout.session.expired', (string)$payment->notes);
        $this->assertStringContainsString('stripe_checkout_session_expired', (string)$payment->notes);
    }

    public function testExpiredWebhookForPaidPaymentDoesNotAutoRequireRefund(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payments->saveOrFail($payment);

        try {
            $this->service->markCheckoutSessionExpired($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'expired',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('checkout_session_expired_after_processed_payment', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = $payments->get(1);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertStringContainsString('"refund_required":false', (string)$payment->notes);
        $this->assertStringContainsString(
            '"review_state":"contradictory_terminal_event_after_paid"',
            (string)$payment->notes,
        );
    }

    public function testAsyncFailedPreservesRefundReviewFlagForExistingRefundQueuePayment(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'refund_required';
        $payment->notes = json_encode([
            'manual_review_required' => true,
            'refund_required' => true,
            'funds_captured' => true,
            'reason_code' => 'cancelled_booking_paid_late',
        ]);
        $payments->saveOrFail($payment);

        try {
            $this->service->markCheckoutSessionFailed($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'complete',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('async_payment_failed_after_processed_payment', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertTrue((bool)($notes['refund_required'] ?? false));
        $this->assertTrue((bool)($notes['funds_captured'] ?? false));
        $this->assertSame('contradictory_terminal_event_while_refund_pending', $notes['review_state'] ?? null);
    }

    public function testExpiredTerminalEventClearsNonCapturedManualReviewOnVoidedPayment(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'voided';
        $payment->notes = json_encode([
            'manual_review_required' => true,
            'refund_required' => false,
            'funds_captured' => false,
            'reason_code' => 'completed_after_local_payment_voided_or_expired_without_paid_status',
            'review_state' => 'awaiting_payment_terminal_event',
            'session_id' => 'cs_owned',
        ]);
        $payments->saveOrFail($payment);

        $result = $this->service->markCheckoutSessionExpired($this->makeSession('cs_owned', 1, 5000, [
            'status' => 'expired',
            'payment_status' => 'unpaid',
        ]));

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('idempotent', $result);
        $this->assertSame('voided', $payment->payment_status);
        $this->assertFalse((bool)($notes['manual_review_required'] ?? true));
        $this->assertSame('resolved_by_session_expiration', $notes['review_state'] ?? null);
        $this->assertSame('checkout.session.expired', $notes['terminal_event_type'] ?? null);
        $this->assertFalse((bool)($notes['refund_required'] ?? true));
    }

    public function testExpiredTerminalEventClearsAwaitingTerminalReviewForUnexpectedLocalStatus(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'failed';
        $payment->notes = json_encode([
            'manual_review_required' => true,
            'refund_required' => false,
            'funds_captured' => false,
            'reason_code' => 'unexpected_local_payment_status',
            'review_state' => 'awaiting_payment_terminal_event',
            'session_id' => 'cs_owned',
        ]);
        $payments->saveOrFail($payment);

        $result = $this->service->markCheckoutSessionExpired($this->makeSession('cs_owned', 1, 5000, [
            'status' => 'expired',
            'payment_status' => 'unpaid',
        ]));

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('idempotent', $result);
        $this->assertSame('failed', $payment->payment_status);
        $this->assertFalse((bool)($notes['manual_review_required'] ?? true));
        $this->assertSame('resolved_by_session_expiration', $notes['review_state'] ?? null);
        $this->assertSame('stripe_checkout_session_expired', $notes['reason_code'] ?? null);
    }

    public function testExpiredWebhookForRefundedPaymentDoesNotSetRefundRequiredTrue(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'refunded';
        $payment->notes = json_encode([
            'manual_review_required' => false,
            'refund_required' => false,
            'funds_captured' => true,
            'reason_code' => 'payment_fully_refunded',
        ]);
        $payments->saveOrFail($payment);

        try {
            $this->service->markCheckoutSessionExpired($this->makeSession('cs_owned', 1, 5000, [
                'status' => 'expired',
                'payment_status' => 'unpaid',
            ]));
            $this->fail('Expected manual review exception was not thrown.');
        } catch (ManualReviewWebhookException $exception) {
            $this->assertSame('checkout_session_expired_after_processed_payment', $exception->getContext()['reason_code'] ?? null);
        }

        $payment = $payments->get(1);
        $notes = json_decode((string)$payment->notes, true);

        $this->assertSame('refunded', $payment->payment_status);
        $this->assertFalse((bool)($notes['refund_required'] ?? true));
        $this->assertSame('contradictory_terminal_event_after_full_refund', $notes['review_state'] ?? null);
    }

    private function makeSession(string $id, int $bookingId, int $amountTotal, array $overrides = []): object
    {
        return (object)array_merge([
            'id' => $id,
            'amount_total' => $amountTotal,
            'currency' => 'aud',
            'status' => 'complete',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_' . $id,
            'client_reference_id' => (string)$bookingId,
            'metadata' => (object)[
                'booking_id' => $bookingId,
            ],
        ], $overrides);
    }
}
