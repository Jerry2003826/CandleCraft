<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Parent;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class PaymentsControllerTest extends AppIntegrationTestCase
{
    public function testPaidBookingShowsReceiptOnParentPaymentsPage(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payments->saveOrFail($payment);

        $this->loginAsParent();

        $this->get('/parent/payments');

        $this->assertResponseOk();
        $this->assertResponseContains('Payment Paid');
        $this->assertResponseContains('/parent/payments/receipt/1');
    }

    public function testPaidBookingShowsRefundRequestOnParentPaymentsPage(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payments->saveOrFail($payment);

        $this->loginAsParent();

        $this->get('/parent/payments');

        $this->assertResponseOk();
        $this->assertResponseContains('Request Refund');
        $this->assertResponseContains('/parent/payments/request-refund/1');
    }

    public function testParentCanRequestRefundForLinkedChildPayment(): void
    {
        $this->loginAsParent();
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
        $payment->stripe_payment_intent_id = 'pi_parent_refund_request';
        $payments->saveOrFail($payment);

        $this->post('/parent/payments/request-refund/1');

        $this->assertRedirectContains('/parent/payments');
        $payment = $payments->get(1);
        $this->assertSame('refund_required', $payment->payment_status);
        $this->assertStringContainsString('"refund_request_portal":"parent_portal"', (string)$payment->notes);
    }

    public function testReceiptOpensForLinkedChildPayment(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payments->saveOrFail($payment);

        $this->loginAsParent();

        $this->get('/parent/payments/receipt/1');

        $this->assertResponseOk();
        $this->assertResponseContains('Payment Receipt');
        $this->assertResponseContains('PAY-000001');
    }

    public function testStaleSessionVerificationDoesNotBlockParentPaymentPortalWhenDatabaseIsApproved(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, true);
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/payments');

        $this->assertResponseOk();
        $this->assertResponseContains('Payments');
    }

    public function testUnderageParentCannotOpenPaymentPortal(): void
    {
        $this->setUserAgeVerifiedByAdmin(6, false);
        $this->loginAsParent(ageVerifiedByAdmin: false);

        $this->get('/parent/payments');

        $this->assertResponseCode(302);
        $this->assertRedirectContains('/parent');
        $this->assertSession(6, 'Auth.user_id');
        $this->assertSession('parent', 'Auth.user_role');
    }
}
