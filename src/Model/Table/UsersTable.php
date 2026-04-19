<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('username');
        $this->setPrimaryKey('user_id');

        $this->hasOne('Admins', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);
        $this->hasOne('Parents', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);
        $this->hasOne('Teachers', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);
        $this->hasOne('Students', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);
        $this->hasMany('PaymentProfiles', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('username')
            ->maxLength('username', 50)
            ->minLength('username', 3)
            ->add('username', 'validFormat', [
                'rule' => static function (mixed $value): bool {
                    if (!is_string($value)) {
                        return false;
                    }

                    return (bool)preg_match('/^[a-zA-Z0-9_.-]+$/', $value);
                },
                'message' => 'Username can only contain letters, numbers, dot, underscore and hyphen.',
            ])
            ->requirePresence('username', 'create')
            ->notEmptyString('username');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('password_hash')
            ->minLength('password_hash', 8, 'Password must be at least 8 characters long.')
            ->requirePresence('password_hash', 'create')
            ->notEmptyString('password_hash');

        $validator
            ->inList('user_role', ['admin', 'teacher', 'student', 'parent', 'customer'])
            ->requirePresence('user_role', 'create')
            ->notEmptyString('user_role');

        $validator
            ->inList('account_status', ['active', 'inactive', 'suspended'])
            ->notEmptyString('account_status');

        return $validator;
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->isUnique(['username']), ['errorField' => 'username']);
        $rules->add($rules->isUnique(['email']), ['errorField' => 'email']);

        return $rules;
    }

    public function validationRegister(Validator $validator): Validator
    {
        $validator
            ->scalar('username')
            ->maxLength('username', 50)
            ->minLength('username', 3)
            ->add('username', 'validFormat', [
                'rule' => static function (mixed $value): bool {
                    return is_string($value) && (bool)preg_match('/^[a-zA-Z0-9_.-]+$/', $value);
                },
                'message' => 'Username can only contain letters, numbers, dot, underscore and hyphen.',
            ])
            ->requirePresence('username', 'create')
            ->notEmptyString('username');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('password_hash')
            ->minLength('password_hash', 8, 'Password must be at least 8 characters long.')
            ->requirePresence('password_hash', 'create')
            ->notEmptyString('password_hash');

        $validator
            ->inList('user_role', ['admin', 'teacher', 'student', 'parent', 'customer'])
            ->requirePresence('user_role', 'create')
            ->notEmptyString('user_role');

        $validator
            ->inList('account_status', ['active', 'inactive', 'suspended'])
            ->notEmptyString('account_status');

        return $validator;
    }

    public function findAuth(SelectQuery $query): SelectQuery
    {
        return $query
            ->where([
                'Users.account_status' => 'active',
            ]);
    }
}
