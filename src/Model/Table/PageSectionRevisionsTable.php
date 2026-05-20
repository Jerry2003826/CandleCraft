<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PageSectionRevisions Model
 *
 * @method \App\Model\Entity\PageSectionRevision newEmptyEntity()
 * @method \App\Model\Entity\PageSectionRevision get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PageSectionRevision|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class PageSectionRevisionsTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('page_section_revisions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('PageSections', [
            'foreignKey' => 'section_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('ChangedBy', [
            'className' => 'Users',
            'foreignKey' => 'changed_by_id',
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
        $validator->dateTime('changed_at')->requirePresence('changed_at', 'create');
        $validator->allowEmptyString('content_value_snapshot');
        $validator->allowEmptyString('media_id_snapshot');
        $validator->allowEmptyString('changed_by_id');
        $validator->allowEmptyString('change_summary')->maxLength('change_summary', 500);

        return $validator;
    }
}
