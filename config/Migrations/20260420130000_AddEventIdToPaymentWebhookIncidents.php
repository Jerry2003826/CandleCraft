<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddEventIdToPaymentWebhookIncidents extends BaseMigration
{
    public function change(): void
    {
        if (!$this->hasTable('payment_webhook_incidents')) {
            return;
        }

        $table = $this->table('payment_webhook_incidents');

        if (!$table->hasColumn('event_id')) {
            $table->addColumn('event_id', 'string', [
                'limit' => 100,
                'null' => true,
            ]);
        }

        if (!$table->hasIndex(['event_id'])) {
            $table->addIndex(['event_id'], [
                'name' => 'idx_payment_webhook_incidents_event_id',
            ]);
        }

        if (!$table->hasIndex(['event_id', 'reason_code', 'status'])) {
            $table->addIndex(['event_id', 'reason_code', 'status'], [
                'name' => 'uk_payment_webhook_incidents_event_reason_status',
                'unique' => true,
            ]);
        }

        $table->update();
    }
}
