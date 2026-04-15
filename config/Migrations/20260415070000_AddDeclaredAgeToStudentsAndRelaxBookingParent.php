<?php
declare(strict_types=1);

use Migrations\BaseMigration;

class AddDeclaredAgeToStudentsAndRelaxBookingParent extends BaseMigration
{
    public function up(): void
    {
        $students = $this->table('students');

        if (!$students->hasColumn('declared_age')) {
            $students->addColumn('declared_age', 'integer', [
                'null' => true,
                'signed' => false,
                'after' => 'student_name',
                'comment' => 'Age declared by the student during account request',
            ]);
        }

        $students->update();

        $this->execute(
            'UPDATE students
             SET declared_age = TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE())
             WHERE declared_age IS NULL
               AND date_of_birth IS NOT NULL'
        );

        $students = $this->table('students');
        if ($students->hasColumn('date_of_birth')) {
            $students->changeColumn('date_of_birth', 'date', [
                'null' => true,
                'default' => null,
            ]);
        }
        $students->update();

        $bookings = $this->table('bookings');
        if ($bookings->hasColumn('parent_id')) {
            $bookings->changeColumn('parent_id', 'biginteger', [
                'null' => true,
                'signed' => false,
                'default' => null,
            ]);
        }
        $bookings->update();
    }

    public function down(): void
    {
        throw new \RuntimeException('This migration is irreversible because it backfills declared ages and relaxes legacy constraints.');
    }
}
