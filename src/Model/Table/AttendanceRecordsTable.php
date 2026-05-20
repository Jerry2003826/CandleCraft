<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class AttendanceRecordsTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('attendance_records');
        $this->setDisplayField('attendance_id');
        $this->setPrimaryKey('attendance_id');

        $this->belongsTo('Bookings', [
            'foreignKey' => 'booking_id',
            'bindingKey' => 'booking_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('Teachers', [
            'foreignKey' => 'marked_by_teacher_id',
            'bindingKey' => 'teacher_id',
        ]);
    }

    /**
     * Validation default.
     *
     * @param mixed $validator Validator.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('booking_id', 'create')
            ->notEmptyString('booking_id');

        $validator
            ->inList('attendance_status', ['present', 'absent', 'late', 'excused'])
            ->notEmptyString('attendance_status');

        $validator
            ->scalar('attendance_notes')
            ->maxLength('attendance_notes', 1000)
            ->allowEmptyString('attendance_notes');

        return $validator;
    }

    /**
     * Build rules.
     *
     * @param mixed $rules Rules.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('booking_id', 'Bookings'), ['errorField' => 'booking_id']);
        $rules->add($rules->existsIn('marked_by_teacher_id', 'Teachers'), ['errorField' => 'marked_by_teacher_id']);

        return $rules;
    }
}
