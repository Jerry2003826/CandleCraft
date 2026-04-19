<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class StripeWebhookEventsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stripe_webhook_events');
        $this->setDisplayField('event_id');
        $this->setPrimaryKey('webhook_event_id');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('event_id')
            ->maxLength('event_id', 100)
            ->requirePresence('event_id', 'create')
            ->notEmptyString('event_id');

        $validator
            ->scalar('event_type')
            ->maxLength('event_type', 100)
            ->requirePresence('event_type', 'create')
            ->notEmptyString('event_type');

        $validator
            ->scalar('session_id')
            ->maxLength('session_id', 100)
            ->allowEmptyString('session_id');

        $validator
            ->scalar('payload_hash')
            ->maxLength('payload_hash', 64)
            ->requirePresence('payload_hash', 'create')
            ->notEmptyString('payload_hash');

        $validator
            ->inList('processing_status', ['processing', 'processed', 'ignored', 'failed'])
            ->requirePresence('processing_status', 'create')
            ->notEmptyString('processing_status');

        $validator
            ->dateTime('first_seen_at')
            ->requirePresence('first_seen_at', 'create')
            ->notEmptyDateTime('first_seen_at');

        $validator
            ->dateTime('last_seen_at')
            ->requirePresence('last_seen_at', 'create')
            ->notEmptyDateTime('last_seen_at');

        return $validator;
    }
}
