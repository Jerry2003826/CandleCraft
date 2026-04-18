<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Consumer;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class PaymentsControllerTest extends AppIntegrationTestCase
{
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
}
