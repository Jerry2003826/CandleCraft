<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ParentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('parents');
        $this->setDisplayField('parent_name');
        $this->setPrimaryKey('parent_id');
        $this->setEntityClass('App\Model\Entity\ParentEntity');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsToMany('Students', [
            'through' => 'ParentStudents',
            'foreignKey' => 'parent_id',
            'targetForeignKey' => 'student_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('parent_name')
            ->maxLength('parent_name', 100)
            ->requirePresence('parent_name', 'create')
            ->notEmptyString('parent_name');

        $validator
            ->scalar('phone_number')
            ->maxLength('phone_number', 30)
            ->requirePresence('phone_number', 'create')
            ->notEmptyString('phone_number');

        return $validator;
    }
}
