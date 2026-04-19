<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
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
        $statusCounts = $this->fetchStatusCounts($paymentsTable, $conditions);
        $affectedCount = array_sum($statusCounts);

        if ($affectedCount === 0) {
            $io->out('No non-paid payment_date values required repair.');

            return static::CODE_SUCCESS;
        }

        if ($args->getOption('dry-run')) {
            $io->out('Dry run: the following non-paid payment_date values would be cleared.');
            $this->writeStatusSummary($io, $statusCounts);
            $io->out(sprintf('Total: %d', $affectedCount));

            return static::CODE_SUCCESS;
        }

        $paymentsTable->updateQuery()
            ->set([
                'payment_date' => null,
            ])
            ->where($conditions)
            ->execute();

        $io->out('Cleared payment_date for the following non-paid payments.');
        $this->writeStatusSummary($io, $statusCounts);
        $io->out(sprintf('Total cleared: %d', $affectedCount));

        return static::CODE_SUCCESS;
    }

    private function fetchStatusCounts(object $paymentsTable, array $conditions): array
    {
        $query = $paymentsTable->find();
        $rows = $query
            ->select([
                'payment_status' => 'Payments.payment_status',
                'repair_count' => $query->func()->count('*'),
            ])
            ->where($conditions)
            ->groupBy(['Payments.payment_status'])
            ->orderBy(['Payments.payment_status' => 'ASC'])
            ->enableHydration(false)
            ->toArray();

        $statusCounts = [];
        foreach ($rows as $row) {
            $status = (string)($row['payment_status'] ?? '');
            if ($status === '') {
                continue;
            }
            $statusCounts[$status] = (int)($row['repair_count'] ?? 0);
        }

        return $statusCounts;
    }

    private function writeStatusSummary(ConsoleIo $io, array $statusCounts): void
    {
        foreach ($statusCounts as $status => $count) {
            $io->out(sprintf('%s: %d', $status, $count));
        }
    }
}
