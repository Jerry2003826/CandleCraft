<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Admin;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

class PaymentDisputesControllerTest extends AppIntegrationTestCase
{
    public function testAdminCanViewDisputes(): void
    {
        $this->loginAsAdmin();

        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->payment_status = 'paid';
        $payment->stripe_charge_id = 'ch_admin_dispute';
        $payments->saveOrFail($payment);

        $disputes = FactoryLocator::get('Table')->get('PaymentDisputes');
        $disputes->saveOrFail($disputes->newEntity([
            'payment_id' => 1,
            'stripe_dispute_id' => 'dp_admin',
            'stripe_charge_id' => 'ch_admin_dispute',
            'amount' => 50.00,
            'currency_code' => 'AUD',
            'reason' => 'fraudulent',
            'status' => 'needs_response',
        ]));

        $this->get('/admin/payment-disputes');

        $this->assertResponseOk();
        $this->assertResponseContains('Payment Disputes');
        $this->assertResponseContains('dp_admin');
        $this->assertResponseContains('fraudulent');
        $this->assertResponseContains('https://dashboard.stripe.com/disputes/dp_admin');
    }
}
