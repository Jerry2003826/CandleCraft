<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePaymentDisputes extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('payment_disputes')) {
            return;
        }

        $table = $this->table('payment_disputes', ['id' => false, 'primary_key' => ['dispute_record_id']]);
        $table
            ->addColumn('dispute_record_id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('payment_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('stripe_dispute_id', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('stripe_charge_id', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('stripe_payment_intent_id', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('amount', 'decimal', [
                'precision' => 10,
                'scale' => 2,
                'null' => false,
            ])
            ->addColumn('currency_code', 'string', [
                'limit' => 3,
                'default' => 'AUD',
                'null' => false,
            ])
            ->addColumn('reason', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('status', 'string', [
                'limit' => 50,
                'null' => false,
            ])
            ->addColumn('evidence_due_by', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('opened_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('closed_at', 'datetime', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('raw_payload', 'text', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addIndex(['stripe_dispute_id'], [
                'name' => 'uk_payment_disputes_stripe_dispute_id',
                'unique' => true,
            ])
            ->addIndex(['payment_id'], ['name' => 'idx_payment_disputes_payment_id'])
            ->addIndex(['status', 'evidence_due_by'], ['name' => 'idx_payment_disputes_status_due'])
            ->addForeignKey('payment_id', 'payments', 'payment_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
