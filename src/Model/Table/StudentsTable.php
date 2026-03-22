<?php
declare(strict_types=1);

namespace App\Model\Table;

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
            ->requirePresence('student_name', 'create')
            ->notEmptyString('student_name');

        $validator
            ->date('date_of_birth')
            ->requirePresence('date_of_birth', 'create')
            ->notEmptyDate('date_of_birth');

        $validator
            ->inList('student_status', ['active', 'inactive'])
            ->notEmptyString('student_status');

        return $validator;
    }
}
