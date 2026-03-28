<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class CreateNotifications extends BaseMigration
{
    public function change(): void
    {
        if ($this->hasTable('notifications')) {
            return;
        }
        $table = $this->table('notifications');
        $table->addColumn('user_id', 'biginteger', [
            'default' => null,
            'limit' => null,
            'null' => false,
            'signed' => false,
        ]);
        $table->addColumn('title', 'string', [
            'default' => null,
            'limit' => 200,
            'null' => false,
        ]);
        $table->addColumn('message', 'text', [
            'default' => null,
            'null' => false,
        ]);
        $table->addColumn('notification_type', 'string', [
            'default' => null,
            'limit' => 50,
            'null' => false,
        ]);
        $table->addColumn('is_read', 'boolean', [
            'default' => false,
            'null' => false,
        ]);
        $table->addColumn('created', 'datetime', [
            'default' => null,
            'null' => true,
        ]);
        $table->addIndex([
            'user_id',
        ], ['name' => 'idx_notifications_user_id']);
        $table->addIndex([
            'is_read',
        ], ['name' => 'idx_notifications_is_read']);
        $table->addForeignKey('user_id', 'users', 'user_id', [
            'delete' => 'CASCADE',
            'update' => 'CASCADE',
        ]);
        $table->create();
    }
}
