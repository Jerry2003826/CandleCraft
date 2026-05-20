<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDateRangeToTeacherAvailabilities extends BaseMigration
{
    /**
     * Add optional validity dates to weekly teacher availability slots.
     */
    public function change(): void
    {
        $table = $this->table('teacher_availabilities');

        if (!$table->hasColumn('valid_from')) {
            $table->addColumn('valid_from', 'date', [
                'after' => 'end_time',
                'default' => null,
                'null' => true,
            ]);
        }

        if (!$table->hasColumn('valid_until')) {
            $table->addColumn('valid_until', 'date', [
                'after' => 'valid_from',
                'default' => null,
                'null' => true,
            ]);
        }

        $table->update();
    }
}
