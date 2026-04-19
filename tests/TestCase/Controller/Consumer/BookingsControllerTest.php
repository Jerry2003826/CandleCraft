<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Consumer;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class BookingsControllerTest extends AppIntegrationTestCase
{
    public function testCancelVoidsPendingPayment(): void
    {
        $this->loginAsStudent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/consumer/bookings/cancel/1');

        $this->assertResponseCode(302);
        $payment = FactoryLocator::get('Table')->get('Payments')->get(1);
        $booking = FactoryLocator::get('Table')->get('Bookings')->get(1);
        $this->assertSame('voided', $payment->payment_status);
        $this->assertSame('cancelled', $booking->booking_status);
    }

    public function testPaidBookingCannotBeSilentlyCancelledWithoutRefundPath(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $bookings = FactoryLocator::get('Table')->get('Bookings');

        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payments->saveOrFail($payment);

        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $this->loginAsStudent();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/consumer/bookings/cancel/1');

        $this->assertResponseCode(302);
        $payment = $payments->get(1);
        $booking = $bookings->get(1);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('confirmed', $booking->booking_status);
    }

    public function testIndexIgnoresInvalidWeekStartValue(): void
    {
        $this->loginAsStudent();

        $this->get('/consumer/bookings?week_start=not-a-date');

        $this->assertResponseCode(200);
        $this->assertResponseContains('View Schedule & Attendance');
    }
}
