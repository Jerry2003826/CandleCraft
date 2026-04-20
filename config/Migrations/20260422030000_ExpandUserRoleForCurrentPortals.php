<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ExpandUserRoleForCurrentPortals extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users')) {
            return;
        }

        $this->table('users')
            ->changeColumn('user_role', 'string', [
                'limit' => 20,
                'null' => false,
            ])
            ->update();
    }

    public function down(): void
    {
        throw new RuntimeException('This migration is irreversible.');
    }
}
