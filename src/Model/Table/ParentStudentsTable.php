<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ParentStudentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('parent_students');
        $this->setPrimaryKey(['parent_id', 'student_id']);

        $this->belongsTo('Parents', [
            'foreignKey' => 'parent_id',
            'bindingKey' => 'parent_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Students', [
            'foreignKey' => 'student_id',
            'bindingKey' => 'student_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('relationship_to_student')
            ->maxLength('relationship_to_student', 50)
            ->requirePresence('relationship_to_student', 'create')
            ->notEmptyString('relationship_to_student');

        return $validator;
    }
}
