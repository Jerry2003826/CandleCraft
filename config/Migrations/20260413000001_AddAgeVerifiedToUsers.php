<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddAgeVerifiedToUsers extends BaseMigration
{
    public function change(): void
    {
        $table = $this->table('users', ['id' => false, 'primary_key' => ['user_id']]);
        $changed = false;

        if (!$table->hasColumn('age_verified_by_admin')) {
            $table->addColumn('age_verified_by_admin', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'account_status',
                'comment' => 'Admin confirms user is 18+ before payment is allowed',
            ]);
            $changed = true;
        }

        if (!$table->hasColumn('self_declared_adult')) {
            $table->addColumn('self_declared_adult', 'boolean', [
                'default' => false,
                'null' => false,
                'after' => 'age_verified_by_admin',
                'comment' => 'User self-declared they are 18+ during registration',
            ]);
            $changed = true;
        }

        if ($changed) {
            $table->update();
        }
    }
}
