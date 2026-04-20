<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class ClearMinorAdultVerifications extends BaseMigration
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
                u.age_verified_by_admin = 0,
                u.updated_at = CURRENT_TIMESTAMP
            WHERE u.age_verified_by_admin = 1
              AND (
                    (s.date_of_birth IS NOT NULL AND TIMESTAMPDIFF(YEAR, s.date_of_birth, CURDATE()) < 18)
                 OR (s.date_of_birth IS NULL AND s.declared_age IS NOT NULL AND s.declared_age < 18)
              )
            SQL
        );
    }

    public function down(): void
    {
        // Intentionally irreversible because prior admin verification decisions
        // for minors should not be restored automatically.
    }
}
