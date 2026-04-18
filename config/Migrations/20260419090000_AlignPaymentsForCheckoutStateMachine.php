<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AlignPaymentsForCheckoutStateMachine extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('payments')) {
            return;
        }

        $table = $this->table('payments');
        $tableChanged = false;

        if (!$table->hasColumn('currency_code')) {
            $table->addColumn('currency_code', 'string', [
                'limit' => 3,
                'default' => 'AUD',
                'null' => false,
                'after' => 'amount',
            ]);
            $tableChanged = true;
        }
        if (!$table->hasColumn('payment_date')) {
            $table->addColumn('payment_date', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'payment_status',
            ]);
            $tableChanged = true;
        }
        if (!$table->hasColumn('transaction_reference')) {
            $table->addColumn('transaction_reference', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
                'after' => 'payment_date',
            ]);
            $tableChanged = true;
        }
        if (!$table->hasColumn('refunded_amount')) {
            $table->addColumn('refunded_amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'default' => 0.00,
                'null' => false,
                'after' => 'transaction_reference',
            ]);
            $tableChanged = true;
        }
        if (!$table->hasColumn('notes')) {
            $table->addColumn('notes', 'text', [
                'null' => true,
                'default' => null,
                'after' => 'refunded_amount',
            ]);
            $tableChanged = true;
        }
        if (!$table->hasColumn('created_at')) {
            $table->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ]);
            $tableChanged = true;
        }
        if (!$table->hasColumn('updated_at')) {
            $table->addColumn('updated_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ]);
            $tableChanged = true;
        }

        if ($tableChanged) {
            $table->update();
        }

        $adapter = $this->getAdapter();

        if ($adapter->hasColumn('payments', 'stripe_session_id')) {
            $this->execute(
                "UPDATE payments
                 SET transaction_reference = COALESCE(transaction_reference, stripe_session_id)
                 WHERE transaction_reference IS NULL OR transaction_reference = ''"
            );
        }
        if ($adapter->hasColumn('payments', 'transaction_id')) {
            $this->execute(
                "UPDATE payments
                 SET transaction_reference = COALESCE(transaction_reference, transaction_id)
                 WHERE transaction_reference IS NULL OR transaction_reference = ''"
            );
        }
        if ($adapter->hasColumn('payments', 'paid_at')) {
            $this->execute(
                "UPDATE payments
                 SET payment_date = COALESCE(payment_date, paid_at)
                 WHERE payment_date IS NULL"
            );
        }
        if ($adapter->hasColumn('payments', 'created')) {
            $this->execute(
                "UPDATE payments
                 SET created_at = COALESCE(created_at, created)
                 WHERE created_at IS NULL"
            );
        }
        if ($adapter->hasColumn('payments', 'modified')) {
            $this->execute(
                "UPDATE payments
                 SET updated_at = COALESCE(updated_at, modified)
                 WHERE updated_at IS NULL"
            );
        }

        $table = $this->table('payments');
        if (!$table->hasIndexByName('uk_payments_transaction_reference')) {
            $table->addIndex(['transaction_reference'], [
                'name' => 'uk_payments_transaction_reference',
                'unique' => true,
            ])->update();
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }
}
