<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddPasswordResetFieldsToUsers extends BaseMigration
{
    public function change(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $table = $this->table('users');
        $changed = false;

        if (!$table->hasColumn('reset_token')) {
            $table->addColumn('reset_token', 'string', [
                'limit' => 128,
                'null' => true,
                'default' => null,
                'after' => 'password_hash',
            ]);
            $changed = true;
        }

        if (!$table->hasColumn('reset_token_expires')) {
            $table->addColumn('reset_token_expires', 'datetime', [
                'null' => true,
                'default' => null,
                'after' => 'reset_token',
            ]);
            $changed = true;
        }

        if ($changed) {
            $table->update();
        }

        $table = $this->table('users');
        if (!$table->hasIndexByName('idx_users_reset_token')) {
            $table->addIndex(['reset_token'], [
                'name' => 'idx_users_reset_token',
            ])->update();
        }
    }
}
