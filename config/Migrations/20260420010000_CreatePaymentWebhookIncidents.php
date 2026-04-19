<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreatePaymentWebhookIncidents extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('payment_webhook_incidents')) {
            return;
        }

        $table = $this->table('payment_webhook_incidents');
        $table
            ->addColumn('event_type', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('session_id', 'string', [
                'limit' => 120,
                'null' => false,
            ])
            ->addColumn('payment_id', 'biginteger', [
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('booking_id', 'biginteger', [
                'null' => true,
                'signed' => false,
            ])
            ->addColumn('reason_code', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('severity', 'string', [
                'limit' => 20,
                'null' => false,
            ])
            ->addColumn('status', 'string', [
                'limit' => 20,
                'null' => false,
                'default' => 'open',
            ])
            ->addColumn('context_json', 'text', [
                'null' => true,
            ])
            ->addColumn('payload_hash', 'string', [
                'limit' => 64,
                'null' => false,
            ])
            ->addColumn('notes', 'text', [
                'null' => true,
            ])
            ->addColumn('created_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('updated_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('resolved_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('resolved_by_admin_id', 'biginteger', [
                'null' => true,
                'signed' => false,
            ])
            ->addIndex(['status'], ['name' => 'idx_payment_webhook_incidents_status'])
            ->addIndex(['event_type'], ['name' => 'idx_payment_webhook_incidents_event_type'])
            ->addIndex(['session_id'], ['name' => 'idx_payment_webhook_incidents_session_id'])
            ->addIndex(['reason_code'], ['name' => 'idx_payment_webhook_incidents_reason_code'])
            ->addIndex(['created_at'], ['name' => 'idx_payment_webhook_incidents_created_at'])
            ->addForeignKey('payment_id', 'payments', 'payment_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('booking_id', 'bookings', 'booking_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->addForeignKey('resolved_by_admin_id', 'admins', 'admin_id', [
                'delete' => 'SET_NULL',
                'update' => 'CASCADE',
            ])
            ->create();
    }
}
