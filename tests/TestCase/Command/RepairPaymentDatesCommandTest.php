<?php
declare(strict_types=1);

namespace App\Test\TestCase\Command;

use Cake\Command\Command;
use Cake\Console\TestSuite\ConsoleIntegrationTestTrait;
use Cake\Datasource\FactoryLocator;
use Cake\TestSuite\TestCase;

class RepairPaymentDatesCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    protected array $fixtures = [
        'app.Payments',
        'app.Bookings',
        'app.Classes',
        'app.Courses',
        'app.Students',
        'app.Users',
    ];

    public function testDryRunReportsAffectedRowsWithoutMutatingData(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');

        $pending = $payments->get(1);
        $pending->payment_date = '2026-04-10 10:00:00';
        $payments->saveOrFail($pending);

        $failed = $payments->get(2);
        $failed->payment_status = 'failed';
        $failed->payment_date = '2026-04-10 10:30:00';
        $payments->saveOrFail($failed);

        $this->exec('repair_payment_dates --dry-run');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Dry run: the following non-paid payment_date values would be cleared.');
        $this->assertOutputContains('failed: 1');
        $this->assertOutputContains('pending: 1');
        $this->assertOutputContains('Total: 2');

        $reloaded = $payments->get(1);
        $this->assertNotNull($reloaded->payment_date);
    }

    public function testCommandClearsOnlyNonPaidPaymentDates(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');

        $pending = $payments->get(1);
        $pending->payment_date = '2026-04-10 10:00:00';
        $payments->saveOrFail($pending);
        $originalPendingUpdatedAt = $pending->updated_at;

        $failed = $payments->get(2);
        $failed->payment_status = 'failed';
        $failed->payment_date = '2026-04-10 10:30:00';
        $payments->saveOrFail($failed);
        $originalFailedUpdatedAt = $failed->updated_at;

        $protected = $payments->newEntity([
            'booking_id' => 1,
            'amount' => 50.00,
            'currency_code' => 'AUD',
            'payment_date' => '2026-04-10 11:00:00',
            'payment_method' => 'online',
            'payment_status' => 'refund_required',
            'transaction_reference' => 'cs_repair_protected',
            'refunded_amount' => 0.00,
            'notes' => null,
            'created_at' => '2026-04-10 11:00:00',
            'updated_at' => '2026-04-10 11:00:00',
        ]);
        $payments->saveOrFail($protected);

        $this->exec('repair_payment_dates');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Cleared payment_date for the following non-paid payments.');
        $this->assertOutputContains('failed: 1');
        $this->assertOutputContains('pending: 1');
        $this->assertOutputContains('Total cleared: 2');

        $reloadedPending = $payments->get(1);
        $reloadedFailed = $payments->get(2);
        $reloadedProtected = $payments->get((int)$protected->payment_id);

        $this->assertNull($reloadedPending->payment_date);
        $this->assertSame($originalPendingUpdatedAt?->format('Y-m-d H:i:s'), $reloadedPending->updated_at?->format('Y-m-d H:i:s'));
        $this->assertNull($reloadedFailed->payment_date);
        $this->assertSame($originalFailedUpdatedAt?->format('Y-m-d H:i:s'), $reloadedFailed->updated_at?->format('Y-m-d H:i:s'));
        $this->assertSame('refund_required', $reloadedProtected->payment_status);
        $this->assertNotNull($reloadedProtected->payment_date);
    }
}
