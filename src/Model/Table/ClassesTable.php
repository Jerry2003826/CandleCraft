<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ClassesTable extends Table
{
    public const LOCATION_OPTIONS = [
        'Studio A',
        'Studio B',
        'Room A',
        'Room B',
    ];

    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
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

    /**
     * Validation default.
     *
     * @param mixed $validator Validator.
     */
    public function validationDefault(Validator $validator): Validator
    {
        // class_code is the canonical identifier; class_name is an optional
        // human label so the public/admin forms can omit it without blocking save.
        $validator
            ->scalar('class_name')
            ->maxLength('class_name', 100)
            ->allowEmptyString('class_name');

        $validator
            ->scalar('class_code')
            ->maxLength('class_code', 30)
            ->add('class_code', 'validFormat', [
                'rule' => static function (mixed $value): bool {
                    if (!is_string($value)) {
                        return false;
                    }

                    return (bool)preg_match('/^[A-Za-z0-9-]{3,30}$/', $value);
                },
                'message' => 'Class code must be 3-30 characters and only contain letters, numbers and hyphen.',
            ])
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
            ->notEmptyDateTime('end_datetime')
            ->add('end_datetime', 'afterStart', [
                // Server-side guard: class end must be later than start.
                'rule' => static function (mixed $value, array $context): bool {
                    $start = $context['data']['start_datetime'] ?? null;
                    if ($start === null || $value === null) {
                        return true;
                    }

                    return strtotime((string)$value) > strtotime((string)$start);
                },
                'message' => 'End date/time must be after start date/time.',
            ]);

        $validator
            ->scalar('location')
            ->maxLength('location', 150)
            ->inList('location', self::LOCATION_OPTIONS, 'Please choose one of the available studio locations.')
            ->requirePresence('location', 'create')
            ->notEmptyString('location');

        $validator
            ->integer('capacity')
            ->greaterThan('capacity', 0, 'Capacity must be greater than 0.')
            ->lessThanOrEqual('capacity', 200, 'Capacity must be 200 or less.')
            ->notEmptyString('capacity');

        $validator
            ->inList('class_status', ['scheduled', 'ongoing', 'completed', 'cancelled', 'full'])
            ->notEmptyString('class_status');

        $validator
            ->scalar('notes')
            ->maxLength('notes', 2000)
            ->allowEmptyString('notes');

        return $validator;
    }

    /**
     * Build rules.
     *
     * @param mixed $rules Rules.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['class_code']), ['errorField' => 'class_code']);
        $rules->add($rules->existsIn('course_id', 'Courses'), ['errorField' => 'course_id']);
        $rules->add($rules->existsIn('teacher_id', 'Teachers'), ['errorField' => 'teacher_id']);
        $rules->add(
            fn(object $entity): bool => !$this->hasOverlappingClass($entity, ['Classes.course_id' => $entity->course_id]),
            'noDuplicateCourseTimeslot',
            [
                'errorField' => 'start_datetime',
                'message' => 'Another class for this course already uses that time slot.',
            ],
        );
        $rules->add(
            fn(object $entity): bool => !$this->hasOverlappingClass($entity, ['Classes.teacher_id' => $entity->teacher_id]),
            'noTeacherClash',
            [
                'errorField' => 'teacher_id',
                'message' => 'This teacher already has another class during the selected time.',
            ],
        );
        $rules->add(
            fn(object $entity): bool => !$this->hasOverlappingClass($entity, ['Classes.location' => $entity->location]),
            'noLocationClash',
            [
                'errorField' => 'location',
                'message' => 'This location is already booked during the selected time.',
            ],
        );

        return $rules;
    }

    /**
     * Has overlapping class.
     *
     * @param mixed $entity Entity.
     * @param mixed $conditions Conditions.
     */
    private function hasOverlappingClass(object $entity, array $conditions): bool
    {
        $start = $entity->start_datetime ?? null;
        $end = $entity->end_datetime ?? null;
        $status = (string)($entity->class_status ?? '');

        if ($start === null || $end === null || $conditions === [] || $status === 'cancelled') {
            return false;
        }

        foreach ($conditions as $value) {
            if ($value === null || $value === '') {
                return false;
            }
        }

        $query = $this->find()
            ->where($conditions)
            ->where([
                'Classes.start_datetime <' => $end,
                'Classes.end_datetime >' => $start,
                'Classes.class_status !=' => 'cancelled',
            ]);

        $classId = $entity->class_id ?? null;
        if ($classId !== null) {
            $query->where(['Classes.class_id !=' => $classId]);
        }

        return $query->count() > 0;
    }
}
