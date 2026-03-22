<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\ORM\Entity;

class User extends Entity
{
    protected array $_accessible = [
        'username' => true,
        'email' => true,
        'password_hash' => true,
        'user_role' => true,
        'account_status' => true,
        'last_login_at' => true,
        'created_at' => true,
        'updated_at' => true,
        'admin' => true,
        'parent' => true,
        'teacher' => true,
        'student' => true,
    ];

    protected array $_hidden = [
        'password_hash',
    ];

    protected function _setPasswordHash(string $password): ?string
    {
        if (strlen($password) > 0) {
            return (new DefaultPasswordHasher())->hash($password);
        }

        return null;
    }
}
