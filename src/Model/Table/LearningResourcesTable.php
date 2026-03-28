<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LearningResourcesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('learning_resources');
        $this->setDisplayField('resource_name');
        $this->setPrimaryKey('resource_id');

        $this->belongsTo('Classes', [
            'foreignKey' => 'class_id',
            'bindingKey' => 'class_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Teachers', [
            'foreignKey' => 'uploaded_by_teacher_id',
            'bindingKey' => 'teacher_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('class_id', 'create')
            ->notEmptyString('class_id');

        $validator
            ->scalar('resource_name')
            ->maxLength('resource_name', 150)
            ->requirePresence('resource_name', 'create')
            ->notEmptyString('resource_name');

        $validator
            ->scalar('resource_description')
            ->allowEmptyString('resource_description');

        $validator
            ->scalar('resource_type')
            ->inList('resource_type', ['pdf', 'video', 'image', 'document', 'link', 'other'])
            ->requirePresence('resource_type', 'create')
            ->notEmptyString('resource_type');

        $validator
            ->scalar('resource_url')
            ->maxLength('resource_url', 500)
            ->allowEmptyString('resource_url');

        $validator
            ->scalar('file_path')
            ->maxLength('file_path', 255)
            ->allowEmptyString('file_path');

        $validator
            ->inList('resource_status', ['active', 'archived'])
            ->notEmptyString('resource_status');

        return $validator;
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->existsIn('class_id', 'Classes'), ['errorField' => 'class_id']);

        return $rules;
    }
}
