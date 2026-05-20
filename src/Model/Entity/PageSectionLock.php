<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * PageSectionLock Entity
 *
 * @property int $id
 * @property int $section_id
 * @property int $locked_by_id
 * @property \Cake\I18n\DateTime $locked_at
 * @property \Cake\I18n\DateTime $expires_at
 */
class PageSectionLock extends Entity
{
    protected array $_accessible = [
        'section_id' => true,
        'locked_by_id' => true,
        'locked_at' => true,
        'expires_at' => true,
    ];
}
