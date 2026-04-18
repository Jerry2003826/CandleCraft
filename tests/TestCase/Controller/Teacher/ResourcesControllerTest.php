<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Teacher;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Datasource\FactoryLocator;

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

    public function testTeacherCannotMassAssignFilePath(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/teacher/resources/add', [
            'class_id' => 1,
            'resource_name' => 'Forged path resource',
            'resource_type' => 'link',
            'resource_url' => 'https://example.com/resource',
            'file_path' => 'uploads/resources/forged.pdf',
        ]);

        $this->assertResponseCode(302);
        $resource = FactoryLocator::get('Table')->get('LearningResources')->find()
            ->where(['LearningResources.resource_name' => 'Forged path resource'])
            ->firstOrFail();

        $this->assertNull($resource->file_path);
    }

    public function testTeacherCannotMassAssignResourceStatus(): void
    {
        $this->loginAsTeacher();
        $this->enableCsrfToken();
        $this->enableSecurityToken();

        $this->post('/teacher/resources/add', [
            'class_id' => 1,
            'resource_name' => 'Teacher status attempt',
            'resource_type' => 'link',
            'resource_url' => 'https://example.com/resource',
            'resource_status' => 'archived',
        ]);

        $this->assertResponseCode(302);
        $resource = FactoryLocator::get('Table')->get('LearningResources')->find()
            ->where(['LearningResources.resource_name' => 'Teacher status attempt'])
            ->firstOrFail();

        $this->assertSame('active', $resource->resource_status);
    }
}
