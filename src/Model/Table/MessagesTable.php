<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class MessagesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('messages');
        $this->setDisplayField('subject');
        $this->setPrimaryKey('message_id');

        $this->belongsTo('SenderUsers', [
            'className' => 'Users',
            'foreignKey' => 'sender_user_id',
            'bindingKey' => 'user_id',
        ]);

        $this->belongsTo('ReceiverUsers', [
            'className' => 'Users',
            'foreignKey' => 'receiver_user_id',
            'bindingKey' => 'user_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('sender_name')
            ->maxLength('sender_name', 100)
            ->allowEmptyString('sender_name');

        $validator
            ->email('sender_email')
            ->allowEmptyString('sender_email');

        $validator
            ->scalar('sender_phone')
            ->maxLength('sender_phone', 30)
            ->allowEmptyString('sender_phone');

        $validator
            ->scalar('source_page')
            ->maxLength('source_page', 255)
            ->allowEmptyString('source_page');

        $validator
            ->scalar('subject')
            ->maxLength('subject', 150)
            ->requirePresence('subject', 'create')
            ->notEmptyString('subject');

        $validator
            ->scalar('message_text')
            ->requirePresence('message_text', 'create')
            ->notEmptyString('message_text');

        $validator
            ->inList('message_type', ['internal', 'contact_form'])
            ->notEmptyString('message_type');

        $validator
            ->inList('message_status', ['unread', 'read', 'replied', 'archived'])
            ->notEmptyString('message_status');

        return $validator;
    }

    public function validationContactForm(Validator $validator): Validator
    {
        $validator = $this->validationDefault($validator);

        $validator
            ->requirePresence('sender_name', 'create')
            ->notEmptyString('sender_name');

        $validator
            ->requirePresence('sender_email', 'create')
            ->notEmptyString('sender_email');

        $validator
            ->requirePresence('sender_phone', 'create')
            ->notEmptyString('sender_phone')
            ->regex(
                'sender_phone',
                '/^[0-9+\-\s()]{6,30}$/',
                'Please enter a valid phone number.',
            );

        $validator
            ->requirePresence('source_page', 'create')
            ->notEmptyString('source_page');

        return $validator;
    }
}
