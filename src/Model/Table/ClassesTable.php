<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ClassesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('classes');
        $this->setDisplayField('class_code');
        $this->setPrimaryKey('class_id');
        $this->setEntityClass('App\Model\Entity\ClassEntity');

        $this->belongsTo('Courses', [
            'foreignKey' => 'course_id',
            'bindingKey' => 'course_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Teachers', [
            'foreignKey' => 'teacher_id',
            'bindingKey' => 'teacher_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('Bookings', [
            'foreignKey' => 'class_id',
            'bindingKey' => 'class_id',
        ]);

        $this->hasMany('LearningResources', [
            'foreignKey' => 'class_id',
            'bindingKey' => 'class_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('class_code')
            ->maxLength('class_code', 30)
            ->requirePresence('class_code', 'create')
            ->notEmptyString('class_code');

        $validator
            ->requirePresence('course_id', 'create')
            ->notEmptyString('course_id');

        $validator
            ->requirePresence('teacher_id', 'create')
            ->notEmptyString('teacher_id');

        $validator
            ->dateTime('start_datetime')
            ->requirePresence('start_datetime', 'create')
            ->notEmptyDateTime('start_datetime');

        $validator
            ->dateTime('end_datetime')
            ->requirePresence('end_datetime', 'create')
            ->notEmptyDateTime('end_datetime');

        $validator
            ->scalar('location')
            ->maxLength('location', 150)
            ->requirePresence('location', 'create')
            ->notEmptyString('location');

        $validator
            ->integer('capacity')
            ->notEmptyString('capacity');

        $validator
            ->inList('class_status', ['scheduled', 'ongoing', 'completed', 'cancelled', 'full'])
            ->notEmptyString('class_status');

        return $validator;
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->isUnique(['class_code']), ['errorField' => 'class_code']);
        $rules->add($rules->existsIn('course_id', 'Courses'), ['errorField' => 'course_id']);
        $rules->add($rules->existsIn('teacher_id', 'Teachers'), ['errorField' => 'teacher_id']);

        return $rules;
    }
}
