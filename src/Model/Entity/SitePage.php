<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SitePage Entity
 *
 * @property int $id
 * @property string $page_slug
 * @property string $page_title
 * @property bool $is_active
 * @property int $sort_order
 * @property \Cake\I18n\DateTime $created_at
 * @property \Cake\I18n\DateTime $updated_at
 *
 * @property \App\Model\Entity\PageSection[] $page_sections
 */
class SitePage extends Entity
{
    protected array $_accessible = [
        'page_slug' => true,
        'page_title' => true,
        'is_active' => true,
        'sort_order' => true,
        'created_at' => true,
        'updated_at' => true,
        'page_sections' => true,
    ];
}
