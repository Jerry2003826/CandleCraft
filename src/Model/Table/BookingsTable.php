<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class BookingsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('bookings');
        $this->setDisplayField('booking_id');
        $this->setPrimaryKey('booking_id');

        $this->belongsTo('Classes', [
            'foreignKey' => 'class_id',
            'bindingKey' => 'class_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Students', [
            'foreignKey' => 'student_id',
            'bindingKey' => 'student_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Parents', [
            'foreignKey' => 'parent_id',
            'bindingKey' => 'parent_id',
            'joinType' => 'LEFT',
        ]);

        $this->hasMany('Payments', [
            'foreignKey' => 'booking_id',
            'bindingKey' => 'booking_id',
        ]);

        $this->hasOne('AttendanceRecords', [
            'foreignKey' => 'booking_id',
            'bindingKey' => 'booking_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('class_id', 'create')
            ->notEmptyString('class_id');

        $validator
            ->allowEmptyString('parent_id');

        $validator
            ->requirePresence('student_id', 'create')
            ->notEmptyString('student_id');

        $validator
            ->inList('booking_status', ['pending', 'confirmed', 'cancelled', 'completed', 'waitlisted'])
            ->notEmptyString('booking_status');

        $validator
            ->decimal('price_at_booking')
            ->requirePresence('price_at_booking', 'create')
            ->notEmptyString('price_at_booking');

        $validator
            ->dateTime('reminder_sent_at')
            ->allowEmptyDateTime('reminder_sent_at');

        return $validator;
    }

    public function buildRules(\Cake\ORM\RulesChecker $rules): \Cake\ORM\RulesChecker
    {
        $rules->add($rules->existsIn('class_id', 'Classes'), ['errorField' => 'class_id']);
        $rules->add($rules->existsIn('student_id', 'Students'), ['errorField' => 'student_id']);
        $rules->add(new ExistsIn('parent_id', 'Parents', ['allowNullableNulls' => true]), ['errorField' => 'parent_id']);

        return $rules;
    }
}
