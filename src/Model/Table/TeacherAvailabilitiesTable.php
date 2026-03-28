<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TeacherAvailabilitiesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('teacher_availabilities');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Teachers', [
            'foreignKey' => 'teacher_id',
            'bindingKey' => 'teacher_id',
            'joinType' => 'INNER',
        ]);

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('teacher_id', 'create')
            ->notEmptyString('teacher_id');

        $validator
            ->integer('day_of_week')
            ->range('day_of_week', [1, 7], 'Day of week must be between 1 (Monday) and 7 (Sunday).')
            ->requirePresence('day_of_week', 'create')
            ->notEmptyString('day_of_week');

        $validator
            ->time('start_time')
            ->requirePresence('start_time', 'create')
            ->notEmptyTime('start_time');

        $validator
            ->time('end_time')
            ->requirePresence('end_time', 'create')
            ->notEmptyTime('end_time')
            ->add('end_time', 'afterStart', [
                'rule' => static function (mixed $value, array $context): bool {
                    $start = $context['data']['start_time'] ?? null;
                    if ($start === null || $value === null) {
                        return true;
                    }

                    return (string)$value > (string)$start;
                },
                'message' => 'End time must be after start time.',
            ]);

        $validator
            ->boolean('is_available')
            ->notEmptyString('is_available');

        return $validator;
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->existsIn('teacher_id', 'Teachers'), ['errorField' => 'teacher_id']);

        return $rules;
    }
}
