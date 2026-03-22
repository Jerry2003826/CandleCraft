<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class Course extends Entity
{
    protected array $_accessible = [
        'course_name' => true,
        'course_type' => true,
        'course_level' => true,
        'course_price' => true,
        'course_description' => true,
        'is_active' => true,
        'created_at' => true,
        'updated_at' => true,
        'classes' => true,
    ];
}
