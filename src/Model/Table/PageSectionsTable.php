<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * PageSections Model
 *
 * @property \App\Model\Table\SitePagesTable&\Cake\ORM\Association\BelongsTo $SitePages
 * @property \App\Model\Table\SiteMediaTable&\Cake\ORM\Association\BelongsTo $SiteMedia
 * @property \App\Model\Table\PageSectionRevisionsTable&\Cake\ORM\Association\HasMany $PageSectionRevisions
 * @property \App\Model\Table\PageSectionLocksTable&\Cake\ORM\Association\HasOne $PageSectionLock
 * @method \App\Model\Entity\PageSection newEmptyEntity()
 * @method \App\Model\Entity\PageSection get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \App\Model\Entity\PageSection|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \App\Model\Entity\PageSection patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 */
class PageSectionsTable extends Table
{
    public const ALLOWED_TYPES = ['text', 'textarea', 'html', 'url', 'email', 'image', 'number'];

    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('page_sections');
        $this->setDisplayField('section_label');
        $this->setPrimaryKey('id');

        $this->belongsTo('SitePages', [
            'foreignKey' => 'page_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('SiteMedia', [
            'foreignKey' => 'media_id',
            'joinType' => 'LEFT',
        ]);
        $this->hasMany('PageSectionRevisions', [
            'foreignKey' => 'section_id',
            'sort' => ['PageSectionRevisions.changed_at' => 'DESC'],
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
        $this->hasOne('PageSectionLock', [
            'className' => 'PageSectionLocks',
            'foreignKey' => 'section_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
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
            ->integer('page_id')
            ->requirePresence('page_id', 'create')
            ->notEmptyString('page_id');

        $validator
            ->scalar('section_key')
            ->maxLength('section_key', 120)
            ->requirePresence('section_key', 'create')
            ->notEmptyString('section_key');

        $validator
            ->scalar('section_label')
            ->maxLength('section_label', 255)
            ->requirePresence('section_label', 'create')
            ->notEmptyString('section_label');

        $validator
            ->scalar('content_type')
            ->inList('content_type', self::ALLOWED_TYPES)
            ->requirePresence('content_type', 'create')
            ->notEmptyString('content_type');

        $validator->allowEmptyString('content_value');
        $validator->allowEmptyString('section_hint');

        $validator
            ->add('content_value', 'urlFormat', [
                'rule' => function ($value, $context) {
                    if (($context['data']['content_type'] ?? null) !== 'url') {
                        return true;
                    }
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return filter_var($value, FILTER_VALIDATE_URL) !== false;
                },
                'message' => 'Value must be a valid URL when content type is "url".',
            ])
            ->add('content_value', 'emailFormat', [
                'rule' => function ($value, $context) {
                    if (($context['data']['content_type'] ?? null) !== 'email') {
                        return true;
                    }
                    if ($value === null || $value === '') {
                        return true;
                    }

                    return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                },
                'message' => 'Value must be a valid email address when content type is "email".',
            ]);

        $validator->boolean('is_active');
        $validator->integer('sort_order');
        $validator->allowEmptyString('media_id');

        return $validator;
    }
}
