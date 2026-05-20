<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;
use DateTimeImmutable;

class TeachersTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('teachers');
        $this->setDisplayField('teacher_name');
        $this->setPrimaryKey('teacher_id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('Classes', [
            'foreignKey' => 'teacher_id',
            'bindingKey' => 'teacher_id',
        ]);
    }

    /**
     * Validation default.
     *
     * @param mixed $validator Validator.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('teacher_name')
            ->maxLength('teacher_name', 100)
            ->add('teacher_name', 'validLength', [
                'rule' => static function (mixed $value): bool {
                    return is_string($value) && mb_strlen(trim($value)) >= 2;
                },
                'message' => 'Teacher name must be at least 2 characters.',
            ])
            ->requirePresence('teacher_name', 'create')
            ->notEmptyString('teacher_name');

        $validator
            ->scalar('phone_number')
            ->maxLength('phone_number', 30)
            ->allowEmptyString('phone_number')
            ->add('phone_number', 'validPhone', [
                'rule' => static function (mixed $value): bool {
                    if ($value === null || $value === '') {
                        return true;
                    }
                    if (!is_string($value)) {
                        return false;
                    }

                    return (bool)preg_match('/^\+?[0-9\s()-]{8,30}$/', $value);
                },
                'message' => 'Please enter a valid phone number.',
            ]);

        $validator
            ->inList('specialization', ['pottery', 'knitting', 'both'])
            ->allowEmptyString('specialization');

        $validator
            ->inList('teacher_status', ['active', 'inactive'])
            ->notEmptyString('teacher_status');

        $validator
            ->date('hire_date')
            ->allowEmptyDate('hire_date')
            ->add('hire_date', 'notFuture', [
                'rule' => static function (mixed $value): bool {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return $value <= new DateTimeImmutable('today');
                },
                'message' => 'Hire date cannot be in the future.',
            ]);

        return $validator;
    }
}
