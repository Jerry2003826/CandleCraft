<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PaymentWebhookIncidentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payment_webhook_incidents');
        $this->setDisplayField('reason_code');
        $this->setPrimaryKey('incident_id');

        $this->belongsTo('Payments', [
            'foreignKey' => 'payment_id',
            'bindingKey' => 'payment_id',
        ]);
        $this->belongsTo('Bookings', [
            'foreignKey' => 'booking_id',
            'bindingKey' => 'booking_id',
        ]);
        $this->belongsTo('ResolvedByAdmins', [
            'className' => 'Admins',
            'foreignKey' => 'resolved_by_admin_id',
            'bindingKey' => 'admin_id',
        ]);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('event_type')
            ->maxLength('event_type', 100)
            ->requirePresence('event_type', 'create')
            ->notEmptyString('event_type');

        $validator
            ->scalar('session_id')
            ->maxLength('session_id', 100)
            ->requirePresence('session_id', 'create')
            ->notEmptyString('session_id');

        $validator
            ->allowEmptyString('payment_id');

        $validator
            ->allowEmptyString('booking_id');

        $validator
            ->scalar('reason_code')
            ->maxLength('reason_code', 100)
            ->requirePresence('reason_code', 'create')
            ->notEmptyString('reason_code');

        $validator
            ->inList('severity', ['warning', 'error'])
            ->requirePresence('severity', 'create')
            ->notEmptyString('severity');

        $validator
            ->inList('status', ['open', 'resolved', 'ignored'])
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->scalar('context_json')
            ->allowEmptyString('context_json');

        $validator
            ->scalar('payload_hash')
            ->maxLength('payload_hash', 64)
            ->requirePresence('payload_hash', 'create')
            ->notEmptyString('payload_hash');

        $validator
            ->scalar('notes')
            ->allowEmptyString('notes');

        $validator
            ->dateTime('resolved_at')
            ->allowEmptyDateTime('resolved_at');

        $validator
            ->allowEmptyString('resolved_by_admin_id');

        return $validator;
    }

}
