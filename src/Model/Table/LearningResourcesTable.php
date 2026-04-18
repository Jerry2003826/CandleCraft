<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Event\EventInterface;
use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
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
            ->allowEmptyString('resource_url')
            ->url('resource_url')
            ->add('resource_url', 'scheme', [
                'rule' => function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    $scheme = strtolower((string)parse_url((string)$value, PHP_URL_SCHEME));

                    return in_array($scheme, ['http', 'https'], true);
                },
                'message' => 'Only http/https links are allowed.',
            ]);

        $validator
            ->scalar('file_path')
            ->maxLength('file_path', 255)
            ->allowEmptyString('file_path');
        $validator
            ->add('file_path', 'pathPattern', [
                'rule' => function ($value) {
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return preg_match('/^uploads\/resources\/[A-Za-z0-9._-]+$/', (string)$value) === 1;
                },
                'message' => 'Uploaded files must stay within uploads/resources.',
            ])
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

    public function beforeSave(EventInterface $event, EntityInterface $entity): void
    {
        if (!$entity->isNew() || $entity->get('resource_id')) {
            return;
        }

        $maxId = (int)($this->find()
            ->select(['max_id' => $this->find()->func()->max('resource_id')])
            ->disableHydration()
            ->first()['max_id'] ?? 0);

        $entity->set('resource_id', $maxId + 1);
        if (!$entity->get('uploaded_at')) {
            $entity->set('uploaded_at', DateTime::now());
        }
    }
}
