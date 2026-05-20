<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTeacherBlockedDates extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('teacher_blocked_dates');
        $table->addColumn('teacher_id', 'biginteger', [
            'default' => null,
            'null' => false,
            'signed' => false,
        ]);
        $table->addColumn('blocked_date', 'date', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('reason', 'string', [
            'default' => null,
            'limit' => 255,
            'null' => true,
        ]);
        $table->addColumn('created', 'datetime', ['default' => null, 'null' => true]);
        $table->addColumn('modified', 'datetime', ['default' => null, 'null' => true]);
        $table->addIndex(['teacher_id'], ['name' => 'idx_teacher_blocked_dates_teacher_id']);
        $table->addForeignKey('teacher_id', 'teachers', 'teacher_id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
