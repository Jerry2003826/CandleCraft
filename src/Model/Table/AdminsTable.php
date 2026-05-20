<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class AdminsTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('admins');
        $this->setDisplayField('admin_name');
        $this->setPrimaryKey('admin_id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'bindingKey' => 'user_id',
            'joinType' => 'INNER',
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
            ->scalar('admin_name')
            ->maxLength('admin_name', 100)
            ->requirePresence('admin_name', 'create')
            ->notEmptyString('admin_name');

        return $validator;
    }
}
