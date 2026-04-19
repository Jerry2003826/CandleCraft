<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\PaymentsTable;
use Cake\TestSuite\TestCase;

class PaymentsTableTest extends TestCase
{
    protected array $fixtures = [
        'app.Payments',
        'app.Bookings',
        'app.Classes',
        'app.Courses',
        'app.Students',
        'app.Users',
    ];

    private PaymentsTable $Payments;

    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Payments') ? [] : ['className' => PaymentsTable::class];
        $this->Payments = $this->getTableLocator()->get('Payments', $config);
    }

    public function testDatabaseAssignsPaymentIdOnInsert(): void
    {
        $payment = $this->Payments->newEntity([
            'booking_id' => 1,
            'amount' => 25.00,
            'payment_method' => 'online',
            'payment_status' => 'pending',
            'transaction_reference' => 'cs_auto_generated',
        ]);

        $this->Payments->saveOrFail($payment);

        $this->assertNotEmpty($payment->payment_id);
        $this->assertGreaterThan(2, (int)$payment->payment_id);
        $this->assertNull($payment->payment_date);
    }

    public function testPaidPaymentAssignsPaymentDateOnInsert(): void
    {
        $payment = $this->Payments->newEntity([
            'booking_id' => 1,
            'amount' => 25.00,
            'payment_method' => 'online',
            'payment_status' => 'paid',
            'transaction_reference' => 'cs_paid_generated',
        ]);

        $this->Payments->saveOrFail($payment);

        $this->assertNotEmpty($payment->payment_id);
        $this->assertNotEmpty($payment->payment_date);
    }

    public function testPaidTransitionAssignsPaymentDateWhenMissing(): void
    {
        $payment = $this->Payments->get(1);
        $payment->payment_date = null;
        $payment->payment_status = 'paid';

        $this->Payments->saveOrFail($payment);

        $reloaded = $this->Payments->get(1);
        $this->assertSame('paid', $reloaded->payment_status);
        $this->assertNotEmpty($reloaded->payment_date);
    }

    public function testFailedPaymentDoesNotAutoAssignPaymentDate(): void
    {
        $payment = $this->Payments->newEntity([
            'booking_id' => 2,
            'amount' => 40.00,
            'payment_method' => 'online',
            'payment_status' => 'failed',
            'transaction_reference' => 'cs_failed_generated',
        ]);

        $this->Payments->saveOrFail($payment);

        $this->assertNull($payment->payment_date);
    }
}
