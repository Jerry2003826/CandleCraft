<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class TeacherBlockedDatesTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('teacher_blocked_dates');
        $this->setEntityClass('App\Model\Entity\TeacherBlockedDate');
        $this->addBehavior('Timestamp');
        $this->belongsTo('Teachers', [
            'foreignKey' => 'teacher_id',
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
            ->date('blocked_date')
            ->requirePresence('blocked_date', 'create')
            ->notEmptyDate('blocked_date');

        $validator
            ->scalar('reason')
            ->maxLength('reason', 255)
            ->allowEmptyString('reason');

        return $validator;
    }
}
