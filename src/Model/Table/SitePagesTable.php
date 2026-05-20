<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * SitePages Model
 *
 * @property \App\Model\Table\PageSectionsTable&\Cake\ORM\Association\HasMany $PageSections
 * @method \App\Model\Entity\SitePage newEmptyEntity()
 * @method \App\Model\Entity\SitePage newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\SitePage get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\SitePage|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 */
class SitePagesTable extends Table
{
    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('site_pages');
        $this->setDisplayField('page_title');
        $this->setPrimaryKey('id');

        $this->hasMany('PageSections', [
            'foreignKey' => 'page_id',
            'sort' => ['PageSections.sort_order' => 'ASC'],
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
            ->scalar('page_slug')
            ->maxLength('page_slug', 80)
            ->requirePresence('page_slug', 'create')
            ->notEmptyString('page_slug')
            ->add('page_slug', 'validFormat', [
                'rule' => ['custom', '/^[a-z0-9_-]+$/'],
                'message' => 'Slug must contain only lowercase letters, numbers, dashes or underscores.',
            ]);

        $validator
            ->scalar('page_title')
            ->maxLength('page_title', 255)
            ->requirePresence('page_title', 'create')
            ->notEmptyString('page_title');

        $validator->boolean('is_active')->notEmptyString('is_active');
        $validator->integer('sort_order');

        return $validator;
    }
}
