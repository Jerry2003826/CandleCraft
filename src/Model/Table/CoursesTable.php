<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class CoursesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('courses');
        $this->setDisplayField('course_name');
        $this->setPrimaryKey('course_id');

        $this->hasMany('Classes', [
            'foreignKey' => 'course_id',
            'bindingKey' => 'course_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('course_name')
            ->maxLength('course_name', 100)
            ->add('course_name', 'validLength', [
                'rule' => static function (mixed $value): bool {
                    return is_string($value) && mb_strlen(trim($value)) >= 2;
                },
                'message' => 'Course name must be at least 2 characters.',
            ])
            ->requirePresence('course_name', 'create')
            ->notEmptyString('course_name');

        $validator
            ->scalar('course_type')
            ->maxLength('course_type', 50)
            ->add('course_type', 'validLength', [
                'rule' => static function (mixed $value): bool {
                    return is_string($value) && mb_strlen(trim($value)) >= 2;
                },
                'message' => 'Course type must be at least 2 characters.',
            ])
            ->requirePresence('course_type', 'create')
            ->notEmptyString('course_type');

        $validator
            ->inList('course_level', ['beginner', 'intermediate', 'advanced', 'all_levels'])
            ->notEmptyString('course_level');

        $validator
            ->decimal('course_price')
            ->requirePresence('course_price', 'create')
            ->notEmptyString('course_price')
            ->greaterThanOrEqual('course_price', 0, 'Course price must be 0 or greater.');

        $validator
            ->scalar('course_description')
            ->maxLength('course_description', 2000)
            ->allowEmptyString('course_description');

        return $validator;
    }
}
