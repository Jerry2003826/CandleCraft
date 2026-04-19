<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Teacher;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class ResourcesControllerTest extends AppIntegrationTestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = sys_get_temp_dir() . '/teacher-resource-downloads-' . bin2hex(random_bytes(6));
        mkdir($this->storageRoot, 0777, true);

        Configure::write('Uploads.resources_root', $this->storageRoot);
        Configure::write('Uploads.resources_url_prefix', '/resources');
    }

    protected function tearDown(): void
    {
        Configure::delete('Uploads.resources_root');
        Configure::delete('Uploads.resources_url_prefix');

        if (is_dir($this->storageRoot)) {
            exec('rm -rf ' . escapeshellarg($this->storageRoot));
        }

        parent::tearDown();
    }

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

    public function testTeacherCanDownloadResourceForOwnedClassEvenWhenNotUploader(): void
    {
        $this->prepareResourceFile('teacher-guide.pdf');

        $resources = FactoryLocator::get('Table')->get('LearningResources');
        $resource = $resources->get(1);
        $resource->uploaded_by_teacher_id = null;
        $resource->file_path = 'resources/teacher-guide.pdf';
        $resource->resource_url = null;
        $resources->saveOrFail($resource);

        $this->loginAsTeacher();

        $this->get('/teacher/resources/download/1');

        $this->assertResponseCode(200);
        $this->assertStringContainsString('nosniff', $this->_response->getHeaderLine('X-Content-Type-Options'));
        $this->assertStringContainsString('attachment', $this->_response->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('application/pdf', $this->_response->getHeaderLine('Content-Type'));
    }

    private function prepareResourceFile(string $fileName): void
    {
        file_put_contents($this->storageRoot . DIRECTORY_SEPARATOR . $fileName, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n");
    }
}
