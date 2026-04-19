<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\I18n\DateTime;
use Cake\ORM\TableRegistry;

class RepairPaymentDatesCommand extends Command
{
    private const NON_CAPTURED_STATUSES = ['pending', 'failed', 'expired', 'voided'];

    public static function defaultName(): string
    {
        return 'repair_payment_dates';
    }

    public static function getDescription(): string
    {
        return 'Clear legacy payment_date values from non-paid payments.';
    }

    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        return $parser->addOption('dry-run', [
            'boolean' => true,
            'help' => 'Preview the number of affected payments without writing changes.',
        ]);
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $paymentsTable = TableRegistry::getTableLocator()->get('Payments');
        $conditions = [
            'Payments.payment_status IN' => self::NON_CAPTURED_STATUSES,
            'Payments.payment_date IS NOT' => null,
        ];

        $affectedCount = $paymentsTable->find()
            ->where($conditions)
            ->count();

        if ($affectedCount === 0) {
            $io->out('No non-paid payment_date values required repair.');

            return static::CODE_SUCCESS;
        }

        if ($args->getOption('dry-run')) {
            $io->out(sprintf(
                'Dry run: %d non-paid payment_date values would be cleared.',
                $affectedCount
            ));

            return static::CODE_SUCCESS;
        }

        $paymentsTable->updateQuery()
            ->set([
                'payment_date' => null,
                'updated_at' => DateTime::now(),
            ])
            ->where($conditions)
            ->execute();

        $io->out(sprintf(
            'Cleared payment_date on %d non-paid payments.',
            $affectedCount
        ));

        return static::CODE_SUCCESS;
    }
}
