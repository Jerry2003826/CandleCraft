<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePayments extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('payments')) {
            return;
        }
        $table = $this->table('payments');
        $table->addColumn('booking_id', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
        ]);
        $table->addColumn('amount', 'decimal', [
            'default' => null,
            'null' => false,
            'precision' => 10,
            'scale' => 2,
        ]);
        $table->addColumn('payment_method', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false,
        ]);
        $table->addColumn('payment_status', 'string', [
            'default' => 'pending',
            'limit' => 30,
            'null' => false,
        ]);
        $table->addColumn('transaction_id', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->addColumn('stripe_session_id', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->addColumn('paid_at', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addColumn('modified', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex([
            'booking_id',
        ], ['name' => 'idx_payments_booking_id']);
        $table->addIndex([
            'transaction_id',
        ], ['name' => 'idx_payments_transaction_id']);
        $table->addIndex([
            'stripe_session_id',
        ], ['name' => 'idx_payments_stripe_session_id']);
        $table->addForeignKey('booking_id', 'bookings', 'booking_id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
