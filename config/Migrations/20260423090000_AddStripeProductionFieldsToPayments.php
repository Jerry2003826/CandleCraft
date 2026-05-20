<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddStripeProductionFieldsToPayments extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('payments')) {
            return;
        }

        $table = $this->table('payments');
        $columns = [
            'stripe_session_id' => ['limit' => 100, 'after' => 'transaction_reference'],
            'stripe_payment_intent_id' => ['limit' => 100, 'after' => 'stripe_session_id'],
            'stripe_charge_id' => ['limit' => 100, 'after' => 'stripe_payment_intent_id'],
            'stripe_customer_id' => ['limit' => 100, 'after' => 'stripe_charge_id'],
            'stripe_invoice_id' => ['limit' => 100, 'after' => 'stripe_customer_id'],
            'stripe_invoice_pdf_url' => ['limit' => 500, 'after' => 'stripe_invoice_id'],
            'stripe_receipt_url' => ['limit' => 500, 'after' => 'stripe_invoice_pdf_url'],
            'stripe_payment_method_type' => ['limit' => 50, 'after' => 'stripe_receipt_url'],
        ];

        $changed = false;
        foreach ($columns as $column => $options) {
            if (!$table->hasColumn($column)) {
                $table->addColumn($column, 'string', [
                    'limit' => $options['limit'],
                    'null' => true,
                    'default' => null,
                    'after' => $options['after'],
                ]);
                $changed = true;
            }
        }

        if ($changed) {
            $table->update();
        }

        $this->execute(
            "UPDATE payments
             SET stripe_session_id = transaction_reference
             WHERE (stripe_session_id IS NULL OR stripe_session_id = '')
               AND transaction_reference LIKE 'cs_%'"
        );

        $table = $this->table('payments');
        foreach ([
            'idx_payments_stripe_session_id' => ['stripe_session_id'],
            'idx_payments_stripe_payment_intent_id' => ['stripe_payment_intent_id'],
            'idx_payments_stripe_charge_id' => ['stripe_charge_id'],
            'idx_payments_payment_status_updated' => ['payment_status', 'updated_at'],
        ] as $indexName => $columns) {
            if (!$table->hasIndexByName($indexName)) {
                $table->addIndex($columns, ['name' => $indexName])->update();
            }
        }
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }
}
