<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TeachersTable extends Table
{
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('teacher_name')
            ->maxLength('teacher_name', 100)
            ->requirePresence('teacher_name', 'create')
            ->notEmptyString('teacher_name');

        $validator
            ->inList('teacher_status', ['active', 'inactive'])
            ->notEmptyString('teacher_status');

        return $validator;
    }
}
