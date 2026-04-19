<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller\Consumer;

use App\Test\TestCase\Controller\AppIntegrationTestCase;
use Cake\Core\Configure;
use Cake\Datasource\FactoryLocator;

class ResourcesControllerTest extends AppIntegrationTestCase
{
    private string $storageRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storageRoot = sys_get_temp_dir() . '/consumer-resource-downloads-' . bin2hex(random_bytes(6));
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

    public function testPendingBookingCannotDownloadProtectedResourceFile(): void
    {
        $this->prepareResourceFile('guide.pdf');
        $this->loginAsStudent();

        $this->get('/consumer/resources/download/1');

        $this->assertResponseCode(302);
    }

    public function testConfirmedBookingCanDownloadProtectedResourceFile(): void
    {
        $bookings = FactoryLocator::get('Table')->get('Bookings');
        $booking = $bookings->get(1);
        $booking->booking_status = 'confirmed';
        $bookings->saveOrFail($booking);

        $this->prepareResourceFile('guide.pdf');
        $this->loginAsStudent();

        $this->get('/consumer/resources/download/1');

        $this->assertResponseCode(200);
        $this->assertStringContainsString('nosniff', $this->_response->getHeaderLine('X-Content-Type-Options'));
        $this->assertStringContainsString('application/pdf', $this->_response->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('attachment', $this->_response->getHeaderLine('Content-Disposition'));
    }

    private function prepareResourceFile(string $fileName): void
    {
        file_put_contents($this->storageRoot . DIRECTORY_SEPARATOR . $fileName, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n");

        $resources = FactoryLocator::get('Table')->get('LearningResources');
        $resource = $resources->get(1);
        $resource->file_path = 'resources/' . $fileName;
        $resource->resource_url = null;
        $resources->saveOrFail($resource);
    }
}
