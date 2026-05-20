<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class PaymentProfilesTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('payment_profiles');
        $this->setDisplayField('billing_name');
        $this->setPrimaryKey('payment_profile_id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
            'joinType' => 'INNER',
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
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('billing_name')
            ->maxLength('billing_name', 150)
            ->requirePresence('billing_name', 'create')
            ->notEmptyString('billing_name');

        $validator
            ->email('billing_email')
            ->requirePresence('billing_email', 'create')
            ->notEmptyString('billing_email');

        $validator
            ->scalar('billing_phone')
            ->maxLength('billing_phone', 30)
            ->allowEmptyString('billing_phone');

        $validator
            ->scalar('billing_address_line1')
            ->maxLength('billing_address_line1', 255)
            ->allowEmptyString('billing_address_line1');

        $validator
            ->scalar('billing_address_line2')
            ->maxLength('billing_address_line2', 255)
            ->allowEmptyString('billing_address_line2');

        $validator
            ->scalar('billing_city')
            ->maxLength('billing_city', 120)
            ->allowEmptyString('billing_city');

        $validator
            ->scalar('billing_state')
            ->maxLength('billing_state', 120)
            ->allowEmptyString('billing_state');

        $validator
            ->scalar('billing_postcode')
            ->maxLength('billing_postcode', 20)
            ->allowEmptyString('billing_postcode');

        $validator
            ->scalar('billing_country')
            ->maxLength('billing_country', 120)
            ->allowEmptyString('billing_country');

        $validator
            ->scalar('preferred_payment_method')
            ->inList('preferred_payment_method', ['card', 'bank_transfer', 'cash', 'other'])
            ->requirePresence('preferred_payment_method', 'create')
            ->notEmptyString('preferred_payment_method');

        $validator
            ->scalar('profile_status')
            ->inList('profile_status', ['active', 'archived'])
            ->requirePresence('profile_status', 'create')
            ->notEmptyString('profile_status');

        $validator
            ->boolean('is_default')
            ->notEmptyString('is_default');

        return $validator;
    }

    /**
     * Build rules.
     *
     * @param mixed $rules Rules.
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);

        return $rules;
    }
}
