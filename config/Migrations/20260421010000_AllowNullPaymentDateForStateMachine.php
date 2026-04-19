<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AllowNullPaymentDateForStateMachine extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('payments')) {
            return;
        }

        $table = $this->table('payments');
        if (!$table->hasColumn('payment_date')) {
            return;
        }

        $table->changeColumn('payment_date', 'datetime', [
            'null' => true,
            'default' => null,
        ])->update();
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }
}
