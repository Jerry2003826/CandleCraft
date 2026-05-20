<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class EnsureAvaParkDemoIsMinor extends BaseMigration
{
    public function up(): void
    {
        if (!$this->hasTable('users') || !$this->hasTable('students')) {
            return;
        }

        $this->execute(
            <<<SQL
            UPDATE users u
            INNER JOIN students s ON s.user_id = u.user_id
            SET
                s.declared_age = 17,
                s.date_of_birth = NULL,
                s.medical_notes = 'Adult verification pending. Can browse courses but cannot book or pay yet.',
                s.updated_at = CURRENT_TIMESTAMP,
                u.age_verified_by_admin = 0,
                u.self_declared_adult = 0,
                u.updated_at = CURRENT_TIMESTAMP
            WHERE u.email = 'ava.park@candlecraft.com'
              AND u.user_role = 'customer'
              AND s.student_name = 'Ava Park'
            SQL
        );
    }

    public function down(): void
    {
        // Intentionally irreversible: this migration corrects demo data that was
        // presenting Ava Park as an adult in deployed databases.
    }
}
