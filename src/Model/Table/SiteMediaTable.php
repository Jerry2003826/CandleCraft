<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SiteMedia Model
 *
 * @method \App\Model\Entity\SiteMedia newEmptyEntity()
 * @method \App\Model\Entity\SiteMedia get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SiteMedia|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class SiteMediaTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('site_media');
        $this->setDisplayField('file_name');
        $this->setPrimaryKey('id');

        $this->belongsTo('Uploader', [
            'className' => 'Users',
            'foreignKey' => 'uploaded_by_id',
            'joinType' => 'LEFT',
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
            ->scalar('file_name')->maxLength('file_name', 255)
            ->requirePresence('file_name', 'create')->notEmptyString('file_name');

        $validator
            ->scalar('file_path')->maxLength('file_path', 500)
            ->requirePresence('file_path', 'create')->notEmptyString('file_path');

        $validator
            ->scalar('file_url')->maxLength('file_url', 500)
            ->requirePresence('file_url', 'create')->notEmptyString('file_url');

        $validator
            ->scalar('mime_type')->maxLength('mime_type', 100)
            ->requirePresence('mime_type', 'create')->notEmptyString('mime_type');

        $validator
            ->integer('file_size')
            ->requirePresence('file_size', 'create')
            ->greaterThanOrEqual('file_size', 0);

        $validator->allowEmptyString('alt_text')->maxLength('alt_text', 500);

        return $validator;
    }
}
