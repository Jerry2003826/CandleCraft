<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PaymentRefundsTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payment_refunds');
        $this->setDisplayField('stripe_refund_id');
        $this->setPrimaryKey('refund_record_id');

        $this->belongsTo('Payments', [
            'foreignKey' => 'payment_id',
            'bindingKey' => 'payment_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('InitiatedByAdmins', [
            'className' => 'Admins',
            'foreignKey' => 'initiated_by_admin_id',
            'bindingKey' => 'admin_id',
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
            ->requirePresence('payment_id', 'create')
            ->notEmptyString('payment_id');

        $validator
            ->scalar('stripe_refund_id')
            ->maxLength('stripe_refund_id', 100)
            ->allowEmptyString('stripe_refund_id');

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
            ->greaterThan('amount', 0, 'Refund amount must be greater than 0.')
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

        $validator
            ->scalar('currency_code')
            ->maxLength('currency_code', 3)
            ->requirePresence('currency_code', 'create')
            ->notEmptyString('currency_code');

        $validator
            ->inList('status', ['pending', 'succeeded', 'failed', 'canceled', 'requires_action'])
            ->requirePresence('status', 'create')
            ->notEmptyString('status');

        $validator
            ->scalar('reason')
            ->maxLength('reason', 100)
            ->allowEmptyString('reason');

        $validator
            ->allowEmptyString('initiated_by_admin_id');

        $validator
            ->scalar('failure_message')
            ->allowEmptyString('failure_message');

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
        $rules->add($rules->existsIn('payment_id', 'Payments'), ['errorField' => 'payment_id']);
        $rules->add($rules->isUnique(['stripe_refund_id'], 'This Stripe refund is already recorded.'), [
            'errorField' => 'stripe_refund_id',
            'allowMultipleNulls' => true,
        ]);

        return $rules;
    }
}
