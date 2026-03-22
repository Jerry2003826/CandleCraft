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
}
