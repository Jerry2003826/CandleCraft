<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PageSection Entity
 *
 * @property int $id
 * @property int $page_id
 * @property string $section_key
 * @property string $section_label
 * @property string|null $section_hint
 * @property string $content_type
 * @property string|null $content_value
 * @property int|null $media_id
 * @property bool $is_active
 * @property int $sort_order
 * @property \Cake\I18n\DateTime $updated_at
 * @property int|null $updated_by_id
 *
 * @property \App\Model\Entity\SitePage|null $site_page
 * @property \App\Model\Entity\SiteMedia|null $site_media
 * @property \App\Model\Entity\PageSectionRevision[] $page_section_revisions
 * @property \App\Model\Entity\PageSectionLock|null $page_section_lock
 */
class PageSection extends Entity
{
    protected array $_accessible = [
        'page_id' => true,
        'section_key' => true,
        'section_label' => true,
        'section_hint' => true,
        'content_type' => true,
        'content_value' => true,
        'media_id' => true,
        'is_active' => true,
        'sort_order' => true,
        'updated_at' => true,
        'updated_by_id' => true,
        'site_page' => true,
        'site_media' => true,
    ];
}
