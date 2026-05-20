<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PageSectionRevision Entity
 *
 * @property int $id
 * @property int $section_id
 * @property string|null $content_value_snapshot
 * @property int|null $media_id_snapshot
 * @property int|null $changed_by_id
 * @property \Cake\I18n\DateTime $changed_at
 * @property string|null $change_summary
 *
 * @property \App\Model\Entity\PageSection|null $page_section
 */
class PageSectionRevision extends Entity
{
    protected array $_accessible = [
        'section_id' => true,
        'content_value_snapshot' => true,
        'media_id_snapshot' => true,
        'changed_by_id' => true,
        'changed_at' => true,
        'change_summary' => true,
    ];
}
