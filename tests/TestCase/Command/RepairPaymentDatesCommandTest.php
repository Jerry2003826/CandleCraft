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

        $this->exec('repair_payment_dates --dry-run');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Dry run: 1 non-paid payment_date values would be cleared.');

        $reloaded = $payments->get(1);
        $this->assertNotNull($reloaded->payment_date);
    }

    public function testCommandClearsOnlyNonPaidPaymentDates(): void
    {
        $payments = FactoryLocator::get('Table')->get('Payments');

        $pending = $payments->get(1);
        $pending->payment_date = '2026-04-10 10:00:00';
        $payments->saveOrFail($pending);

        $protected = $payments->get(2);
        $protected->payment_status = 'refund_required';
        $protected->payment_date = '2026-04-10 10:30:00';
        $payments->saveOrFail($protected);

        $this->exec('repair_payment_dates');

        $this->assertExitCode(Command::CODE_SUCCESS);
        $this->assertOutputContains('Cleared payment_date on 1 non-paid payments.');

        $reloadedPending = $payments->get(1);
        $reloadedProtected = $payments->get(2);

        $this->assertNull($reloadedPending->payment_date);
        $this->assertSame('refund_required', $reloadedProtected->payment_status);
        $this->assertNotNull($reloadedProtected->payment_date);
    }
}
