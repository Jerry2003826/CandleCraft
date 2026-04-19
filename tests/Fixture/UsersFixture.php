<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class UsersFixture extends TestFixture
{
    public function init(): void
    {
        $this->records = [
            [
                'user_id' => 1,
                'username' => 'admin',
                'email' => 'admin@candlecraft.com',
                'password_hash' => 'hashed-admin',
                'user_role' => 'admin',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'last_login_at' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'user_id' => 2,
                'username' => 'teacher1',
                'email' => 'teacher-one@candlecraft.com',
                'password_hash' => 'hashed-teacher-1',
                'user_role' => 'teacher',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'last_login_at' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'user_id' => 3,
                'username' => 'teacher2',
                'email' => 'teacher-two@candlecraft.com',
                'password_hash' => 'hashed-teacher-2',
                'user_role' => 'teacher',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'last_login_at' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'user_id' => 4,
                'username' => 'student1',
                'email' => 'student-one@candlecraft.com',
                'password_hash' => 'hashed-student-1',
                'user_role' => 'student',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'last_login_at' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'user_id' => 5,
                'username' => 'student2',
                'email' => 'student-two@candlecraft.com',
                'password_hash' => 'hashed-student-2',
                'user_role' => 'student',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'last_login_at' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
            [
                'user_id' => 6,
                'username' => 'parent1',
                'email' => 'parent-one@candlecraft.com',
                'password_hash' => 'hashed-parent-1',
                'user_role' => 'parent',
                'account_status' => 'active',
                'age_verified_by_admin' => true,
                'self_declared_adult' => true,
                'last_login_at' => null,
                'created_at' => '2026-04-01 09:00:00',
                'updated_at' => '2026-04-01 09:00:00',
            ],
        ];
        parent::init();
    }
}
