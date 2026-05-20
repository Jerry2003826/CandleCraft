<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePaymentRefunds extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('payment_refunds')) {
            return;
        }

        $table = $this->table('payment_refunds', ['id' => false, 'primary_key' => ['refund_record_id']]);
        $table
            ->addColumn('refund_record_id', 'biginteger', [
                'identity' => true,
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('payment_id', 'biginteger', [
                'signed' => false,
                'null' => false,
            ])
            ->addColumn('stripe_refund_id', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
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
            ->addColumn('status', 'string', [
                'limit' => 30,
                'default' => 'pending',
                'null' => false,
            ])
            ->addColumn('reason', 'string', [
                'limit' => 100,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('initiated_by_admin_id', 'biginteger', [
                'signed' => false,
                'null' => true,
                'default' => null,
            ])
            ->addColumn('failure_message', 'text', [
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
            ->addIndex(['payment_id'], ['name' => 'idx_payment_refunds_payment_id'])
            ->addIndex(['stripe_refund_id'], [
                'name' => 'uk_payment_refunds_stripe_refund_id',
                'unique' => true,
            ])
            ->addIndex(['status'], ['name' => 'idx_payment_refunds_status'])
            ->addForeignKey('payment_id', 'payments', 'payment_id', [
                'delete' => 'CASCADE',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
