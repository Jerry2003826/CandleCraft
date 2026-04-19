<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Database\Schema\TableSchemaInterface;
use Cake\ORM\Table;
use Cake\Validation\Validator;
use Throwable;

class StripeWebhookEventsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('stripe_webhook_events');
        $this->setDisplayField('event_id');
        $this->setPrimaryKey('webhook_event_id');

        try {
            $schema = $this->getConnection()
                ->getSchemaCollection()
                ->describe('stripe_webhook_events', ['forceRefresh' => true]);
            $this->setSchema($schema);
            $this->setPrimaryKey($this->resolvePrimaryKey($schema));
        } catch (Throwable) {
            // The corrective migration creates webhook_event_id; falling back keeps
            // the table usable before migrations are applied in fresh environments.
        }
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
            ->scalar('business_event_key')
            ->maxLength('business_event_key', 255)
            ->allowEmptyString('business_event_key');

        $validator
            ->scalar('payload_hash')
            ->maxLength('payload_hash', 64)
            ->requirePresence('payload_hash', 'create')
            ->notEmptyString('payload_hash');

        $validator
            ->inList('processing_status', ['processing', 'processed', 'ignored', 'failed', 'suspicious'])
            ->requirePresence('processing_status', 'create')
            ->notEmptyString('processing_status');

        $validator
            ->inList('suspicious_state', ['clean', 'suspicious'])
            ->allowEmptyString('suspicious_state');

        $validator
            ->scalar('suspicious_reason_code')
            ->maxLength('suspicious_reason_code', 100)
            ->allowEmptyString('suspicious_reason_code');

        $validator
            ->dateTime('suspicious_seen_at')
            ->allowEmptyDateTime('suspicious_seen_at');

        $validator
            ->scalar('suspicious_business_event_key')
            ->maxLength('suspicious_business_event_key', 255)
            ->allowEmptyString('suspicious_business_event_key');

        $validator
            ->scalar('suspicious_payload_hash')
            ->maxLength('suspicious_payload_hash', 64)
            ->allowEmptyString('suspicious_payload_hash');

        $validator
            ->scalar('suspicious_target_status')
            ->maxLength('suspicious_target_status', 20)
            ->allowEmptyString('suspicious_target_status');

        $validator
            ->nonNegativeInteger('suspicious_count')
            ->allowEmptyString('suspicious_count');

        $validator
            ->dateTime('first_seen_at')
            ->requirePresence('first_seen_at', 'create')
            ->notEmptyDateTime('first_seen_at');

        $validator
            ->dateTime('processing_started_at')
            ->requirePresence('processing_started_at', 'create')
            ->notEmptyDateTime('processing_started_at');

        $validator
            ->dateTime('last_seen_at')
            ->requirePresence('last_seen_at', 'create')
            ->notEmptyDateTime('last_seen_at');

        return $validator;
    }

    private function resolvePrimaryKey(TableSchemaInterface $schema): string
    {
        if ($schema->hasColumn('webhook_event_id')) {
            return 'webhook_event_id';
        }

        return 'id';
    }
}
