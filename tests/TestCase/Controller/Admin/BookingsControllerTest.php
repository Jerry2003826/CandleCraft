<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\Support\FakeStripeRefundGateway;
use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class BookingsControllerTest extends AppIntegrationTestCase
{
    protected function tearDown(): void
    {
        FakeStripeRefundGateway::reset();
        Configure::delete('Payments.refund_gateway_class');

        parent::tearDown();
    }

    public function testAdminCanRefundPaymentFromBookingView(): void
    {
        Configure::write('Payments.refund_gateway_class', FakeStripeRefundGateway::class);
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_controller_refund';
        $payments->saveOrFail($payment);

        $this->post('/admin/bookings/1/payments/1/refund', [
            'amount' => '10.00',
            'reason' => 'requested_by_customer',
        ]);

        $this->assertRedirectContains('/admin/bookings/view/1');
        $payment = $payments->get(1);

        $this->assertSame('partially_refunded', $payment->payment_status);
        $this->assertSame(10.0, (float)$payment->refunded_amount);
    }

    public function testAdminCanRefundCustomerRequestedPaymentFromBookingView(): void
    {
        Configure::write('Payments.refund_gateway_class', FakeStripeRefundGateway::class);
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'refund_required';
        $payment->stripe_payment_intent_id = 'pi_controller_customer_requested_refund';
        $payments->saveOrFail($payment);

        $this->post('/admin/bookings/1/payments/1/refund', [
            'amount' => '10.00',
            'reason' => 'requested_by_customer',
        ]);

        $this->assertRedirectContains('/admin/bookings/view/1');
        $payment = $payments->get(1);

        $this->assertSame('partially_refunded', $payment->payment_status);
        $this->assertSame(10.0, (float)$payment->refunded_amount);
        $this->assertCount(1, FakeStripeRefundGateway::$createdPayloads);
    }

    public function testRefundAmountCannotExceedRemainingBalance(): void
    {
        Configure::write('Payments.refund_gateway_class', FakeStripeRefundGateway::class);
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_payment_intent_id = 'pi_controller_refund_too_much';
        $payments->saveOrFail($payment);

        $this->post('/admin/bookings/1/payments/1/refund', [
            'amount' => '999.00',
            'reason' => 'requested_by_customer',
        ]);

        $this->assertRedirectContains('/admin/bookings/view/1');
        $payment = $payments->get(1);

        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame(0.0, (float)$payment->refunded_amount);
        $this->assertCount(0, FakeStripeRefundGateway::$createdPayloads);
    }

    public function testRefundRequiredFilterShowsOnlyRefundReviewBookings(): void
    {
        $this->loginAsAdmin();

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'refund_required';
        $payments->saveOrFail($payment);

        $this->get('/admin/bookings?status=refund_required');

        $this->assertResponseOk();
        $this->assertResponseContains('Refund Required');
        $this->assertResponseContains('BK-001');
        $this->assertResponseNotContains('BK-002');
    }

    public function testCancellingPaidBookingMarksPaymentForRefundReview(): void
    {
        $this->loginAsAdmin();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->payment_date = '2026-04-11 10:00:00';
        $payment->stripe_payment_intent_id = 'pi_cancelled_paid_booking';
        $payments->saveOrFail($payment);

        $this->post('/admin/bookings/edit/1', [
            'booking_status' => 'cancelled',
            'price_at_booking' => '50.00',
        ]);

        $this->assertRedirectContains('/admin/bookings');
        $payment = $payments->get(1);
        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertSame(0.0, (float)$payment->refunded_amount);
        $this->assertStringContainsString('"refund_required":true', (string)$payment->notes);
    }

    public function testRefundRequiredFilterIncludesCancelledPaidBookings(): void
    {
        $this->loginAsAdmin();

        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'cancelled';
        $bookings->saveOrFail($booking);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->refunded_amount = 0.00;
        $payments->saveOrFail($payment);

        $this->get('/admin/bookings?status=refund_required');

        $this->assertResponseOk();
        $this->assertResponseContains('BK-001');
        $this->assertResponseContains('Refund Required');
    }
}
