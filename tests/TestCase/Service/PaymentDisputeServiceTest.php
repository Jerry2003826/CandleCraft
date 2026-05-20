<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\PaymentDisputeService;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;

class PaymentDisputeServiceTest extends TestCase
{
    protected array $fixtures = [
        'app.Bookings',
        'app.Payments',
        'app.PaymentDisputes',
        'app.Classes',
        'app.Courses',
        'app.Students',
        'app.Users',
    ];

    public function testDisputeWebhookCreatesAndUpdatesSingleRecord(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');
        $payment = $payments->get(1);
        $payment->stripe_charge_id = 'ch_dispute';
        $payment->stripe_payment_intent_id = 'pi_dispute';
        $payments->saveOrFail($payment);

        $service = new PaymentDisputeService();
        $dispute = (object)[
            'id' => 'dp_test',
            'charge' => 'ch_dispute',
            'payment_intent' => 'pi_dispute',
            'amount' => 5000,
            'currency' => 'aud',
            'reason' => 'fraudulent',
            'status' => 'needs_response',
            'created' => 1777046400,
            'evidence_details' => (object)[
                'due_by' => 1777651200,
            ],
        ];

        $service->syncDispute($dispute, 'charge.dispute.created');
        $dispute->status = 'won';
        $service->syncDispute($dispute, 'charge.dispute.closed');

        $records = FactoryLocator::get('Table')->get('PaymentDisputes')->find()
            ->where(['PaymentDisputes.stripe_dispute_id' => 'dp_test'])
            ->all()
            ->toList();
        $payment = $payments->get(1);

        $this->assertCount(1, $records);
        $this->assertSame(1, (int)$records[0]->payment_id);
        $this->assertSame('won', $records[0]->status);
        $this->assertNotNull($records[0]->closed_at);
        $this->assertSame('paid', $payment->payment_status);
    }
}
