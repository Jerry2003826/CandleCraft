<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class LearningResource extends Entity
{
    protected array $_accessible = [
        'class_id' => true,
        'resource_name' => true,
        'resource_type' => true,
        'resource_url' => true,
        'resource_description' => true,
        'class' => true,
        'teacher' => true,
        'uploaded_by_teacher_id' => false,
        'file_path' => false,
        'resource_status' => false,
    ];
}
