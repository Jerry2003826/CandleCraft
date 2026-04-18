<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Teacher;

use App\Test\TestCase\Controller\AppIntegrationTestCase;

class ResourcesControllerTest extends AppIntegrationTestCase
{
    public function testTeacherCannotAttachResourceToAnotherTeachersClass(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/teacher/resources/add', [
            'class_id' => 2,
            'resource_name' => 'Cross-class resource',
            'resource_type' => 'link',
            'resource_url' => 'https://example.com/resource',
        ]);

        $this->assertResponseCode(403);
    }
}
