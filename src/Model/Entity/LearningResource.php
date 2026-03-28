<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LearningResource extends Entity
{
    protected array $_accessible = [
        'class_id' => true,
        'uploaded_by_teacher_id' => true,
        'resource_name' => true,
        'resource_type' => true,
        'resource_url' => true,
        'file_path' => true,
        'resource_description' => true,
        'resource_status' => true,
        'class' => true,
        'teacher' => true,
    ];
}
