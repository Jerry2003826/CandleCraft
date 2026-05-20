<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AllowDisputedPaymentStatus extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('payments')) {
            return;
        }

        $adapter = $this->getAdapter();
        if ($adapter->getAdapterType() !== 'mysql') {
            return;
        }

        $this->execute(
            "ALTER TABLE payments
             MODIFY payment_status ENUM(
                'pending',
                'paid',
                'failed',
                'expired',
                'voided',
                'refund_required',
                'refunded',
                'partially_refunded',
                'disputed'
             ) NOT NULL DEFAULT 'pending'"
        );
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }
}
