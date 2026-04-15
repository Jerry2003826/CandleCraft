<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\Date;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class StudentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('students');
        $this->setDisplayField('student_name');
        $this->setPrimaryKey('student_id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
        ]);

        $this->belongsToMany('Parents', [
            'through' => 'ParentStudents',
            'foreignKey' => 'student_id',
            'targetForeignKey' => 'parent_id',
        ]);

        $this->hasMany('Bookings', [
            'foreignKey' => 'student_id',
            'bindingKey' => 'student_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('student_name')
            ->maxLength('student_name', 100)
            ->add('student_name', 'validLength', [
                'rule' => static function (mixed $value): bool {
                    return is_string($value) && mb_strlen(trim($value)) >= 2;
                },
                'message' => 'Student name must be at least 2 characters.',
            ])
            ->requirePresence('student_name', 'create')
            ->notEmptyString('student_name');

        $validator
            ->integer('declared_age')
            ->greaterThanOrEqual('declared_age', 1)
            ->lessThanOrEqual('declared_age', 120)
            ->requirePresence('declared_age', 'create')
            ->notEmptyString('declared_age', 'Please enter the student age.');

        $validator
            ->date('date_of_birth')
            ->allowEmptyDate('date_of_birth')
            ->add('date_of_birth', 'notFuture', [
                'rule' => static function (mixed $value): bool {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return $value <= new \DateTimeImmutable('today');
                },
                'message' => 'Date of birth cannot be in the future.',
            ]);

        $validator
            ->inList('student_status', ['active', 'inactive'])
            ->notEmptyString('student_status');

        $validator
            ->scalar('medical_notes')
            ->maxLength('medical_notes', 1000)
            ->allowEmptyString('medical_notes');

        return $validator;
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if (
            !$this->getSchema()->hasColumn('date_of_birth')
            || $this->getSchema()->isNullable('date_of_birth')
            || $entity->get('date_of_birth')
        ) {
            return;
        }

        $declaredAge = $entity->get('declared_age');
        if (!is_numeric($declaredAge)) {
            return;
        }

        $birthYear = max(1900, ((int)date('Y')) - (int)$declaredAge);
        $entity->set('date_of_birth', new Date(sprintf('%04d-01-01', $birthYear)));
    }
}
