<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Rule\ExistsIn;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PaymentDisputesTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payment_disputes');
        $this->setDisplayField('stripe_dispute_id');
        $this->setPrimaryKey('dispute_record_id');

        $this->belongsTo('Payments', [
            'foreignKey' => 'payment_id',
            'bindingKey' => 'payment_id',
            'joinType' => 'LEFT',
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

    /**
     * Validation default.
     *
     * @param mixed $validator Validator.
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->allowEmptyString('payment_id');

        $validator
            ->scalar('stripe_dispute_id')
            ->maxLength('stripe_dispute_id', 100)
            ->requirePresence('stripe_dispute_id', 'create')
            ->notEmptyString('stripe_dispute_id');

        $validator
            ->scalar('stripe_charge_id')
            ->maxLength('stripe_charge_id', 100)
            ->allowEmptyString('stripe_charge_id');

        $validator
            ->scalar('stripe_payment_intent_id')
            ->maxLength('stripe_payment_intent_id', 100)
            ->allowEmptyString('stripe_payment_intent_id');

        $validator
            ->decimal('amount')
            ->greaterThanOrEqual('amount', 0)
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

        $validator
            ->scalar('currency_code')
            ->maxLength('currency_code', 3)
            ->requirePresence('currency_code', 'create')
            ->notEmptyString('currency_code');

        $validator
            ->scalar('reason')
            ->maxLength('reason', 100)
            ->allowEmptyString('reason');

        $validator
            ->scalar('status')
            ->maxLength('status', 50)
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->dateTime('evidence_due_by')
            ->allowEmptyDateTime('evidence_due_by');

        $validator
            ->dateTime('opened_at')
            ->allowEmptyDateTime('opened_at');

        $validator
            ->dateTime('closed_at')
            ->allowEmptyDateTime('closed_at');

        $validator
            ->scalar('raw_payload')
            ->allowEmptyString('raw_payload');

        return $validator;
    }

    /**
     * Build rules.
     *
     * @param mixed $rules Rules.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add(new ExistsIn('payment_id', 'Payments', ['allowNullableNulls' => true]), ['errorField' => 'payment_id']);
        $rules->add($rules->isUnique(['stripe_dispute_id'], 'This Stripe dispute is already recorded.'), [
            'errorField' => 'stripe_dispute_id',
        ]);

        return $rules;
    }
}
