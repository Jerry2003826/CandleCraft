<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * SiteMedia Entity
 *
 * @property int $id
 * @property string $file_name
 * @property string $file_path
 * @property string $file_url
 * @property string $mime_type
 * @property int $file_size
 * @property string|null $alt_text
 * @property int|null $uploaded_by_id
 * @property \Cake\I18n\DateTime $uploaded_at
 * @property \Cake\I18n\DateTime $updated_at
 */
class SiteMedia extends Entity
{
    protected array $_accessible = [
        'file_name' => true,
        'file_path' => true,
        'file_url' => true,
        'mime_type' => true,
        'file_size' => true,
        'alt_text' => true,
        'uploaded_by_id' => true,
        'uploaded_at' => true,
        'updated_at' => true,
    ];
}
