<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateTeacherAvailabilities extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('teacher_availabilities');
        $table->addColumn('teacher_id', 'biginteger', [
            'default' => null,
            'limit' => null,
            'null' => false,
            'signed' => false,
        ]);
        $table->addColumn('day_of_week', 'integer', [
            'default' => null,
            'limit' => null,
            'null' => false,
            'comment' => '1=Monday, 7=Sunday',
        ]);
        $table->addColumn('start_time', 'time', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('end_time', 'time', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('is_available', 'boolean', [
            'default' => true,
            'null' => false,
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
            'teacher_id',
        ], ['name' => 'idx_teacher_availabilities_teacher_id']);
        $table->addForeignKey('teacher_id', 'teachers', 'teacher_id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
