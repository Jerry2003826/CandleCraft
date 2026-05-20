<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PageSectionLocks Model
 *
 * @method \App\Model\Entity\PageSectionLock newEmptyEntity()
 * @method \App\Model\Entity\PageSectionLock get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PageSectionLock|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class PageSectionLocksTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('page_section_locks');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('PageSections', [
            'foreignKey' => 'section_id',
            'joinType' => 'LEFT',
        ]);
        $this->belongsTo('LockedBy', [
            'className' => 'Users',
            'foreignKey' => 'locked_by_id',
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
        $validator->integer('section_id')->requirePresence('section_id', 'create')->notEmptyString('section_id');
        $validator->integer('locked_by_id')->requirePresence('locked_by_id', 'create')->notEmptyString('locked_by_id');
        $validator->dateTime('locked_at')->requirePresence('locked_at', 'create')->notEmptyString('locked_at');
        $validator->dateTime('expires_at')->requirePresence('expires_at', 'create')->notEmptyString('expires_at');

        return $validator;
    }
}
