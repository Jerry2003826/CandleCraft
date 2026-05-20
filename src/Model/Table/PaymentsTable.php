<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PaymentsTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payments');
        $this->setDisplayField('payment_id');
        $this->setPrimaryKey('payment_id');

        $this->belongsTo('Bookings', [
            'foreignKey' => 'booking_id',
            'bindingKey' => 'booking_id',
            'joinType' => 'INNER',
        ]);

        $this->hasMany('PaymentRefunds', [
            'foreignKey' => 'payment_id',
            'bindingKey' => 'payment_id',
        ]);

        $this->hasMany('PaymentDisputes', [
            'foreignKey' => 'payment_id',
            'bindingKey' => 'payment_id',
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
            ->requirePresence('booking_id', 'create')
            ->notEmptyString('booking_id');

        $validator
            ->decimal('amount')
            ->greaterThanOrEqual('amount', 0, 'Amount must be 0 or greater.')
            ->requirePresence('amount', 'create')
            ->notEmptyString('amount');

        $validator
            ->scalar('payment_method')
            ->inList('payment_method', ['cash', 'card', 'bank_transfer', 'online', 'other'])
            ->requirePresence('payment_method', 'create')
            ->notEmptyString('payment_method');

        $validator
            ->inList('payment_status', ['pending', 'paid', 'failed', 'expired', 'voided', 'refund_required', 'refunded', 'partially_refunded', 'disputed'])
            ->notEmptyString('payment_status');

        $validator
            ->scalar('transaction_reference')
            ->maxLength('transaction_reference', 100)
            ->allowEmptyString('transaction_reference');

        foreach (
            [
            'stripe_session_id' => 100,
            'stripe_payment_intent_id' => 100,
            'stripe_charge_id' => 100,
            'stripe_customer_id' => 100,
            'stripe_invoice_id' => 100,
            'stripe_payment_method_type' => 50,
            ] as $field => $maxLength
        ) {
            $validator
                ->scalar($field)
                ->maxLength($field, $maxLength)
                ->allowEmptyString($field);
        }

        $validator
            ->scalar('stripe_invoice_pdf_url')
            ->maxLength('stripe_invoice_pdf_url', 500)
            ->allowEmptyString('stripe_invoice_pdf_url');

        $validator
            ->scalar('stripe_receipt_url')
            ->maxLength('stripe_receipt_url', 500)
            ->allowEmptyString('stripe_receipt_url');

        $validator
            ->decimal('refunded_amount')
            ->greaterThanOrEqual('refunded_amount', 0)
            ->allowEmptyString('refunded_amount');

        $validator
            ->dateTime('payment_date')
            ->allowEmptyDateTime('payment_date');

        $validator
            ->scalar('notes')
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
        $rules->add($rules->existsIn('booking_id', 'Bookings'), ['errorField' => 'booking_id']);
        $rules->add($rules->isUnique(
            ['transaction_reference'],
            'This Stripe checkout session has already been recorded.',
        ), [
            'errorField' => 'transaction_reference',
            'allowMultipleNulls' => true,
        ]);

        return $rules;
    }

    /**
     * Before save.
     *
     * @param mixed $event Event.
     * @param mixed $entity Entity.
     */
    public function beforeSave(EventInterface $event, EntityInterface $entity): void
    {
        if (
            $entity->get('payment_status') === 'paid'
            && $entity->get('payment_date') === null
            && ($entity->isNew() || $entity->isDirty('payment_status'))
        ) {
            $entity->set('payment_date', DateTime::now());
        }

        if (!$entity->get('created_at')) {
            $entity->set('created_at', DateTime::now());
        }
        if (!$entity->get('updated_at')) {
            $entity->set('updated_at', DateTime::now());
        }
    }
}
