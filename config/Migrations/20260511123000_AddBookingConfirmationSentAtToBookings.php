<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddBookingConfirmationSentAtToBookings extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('bookings');

        if (!$table->hasColumn('booking_confirmation_sent_at')) {
            $table
                ->addColumn('booking_confirmation_sent_at', 'datetime', [
                    'default' => null,
                    'null' => true,
                    'after' => 'reminder_sent_at',
                ])
                ->addIndex(['booking_confirmation_sent_at'], [
                    'name' => 'idx_bookings_booking_confirmation_sent_at',
                ])
                ->update();
        }
    }
}
