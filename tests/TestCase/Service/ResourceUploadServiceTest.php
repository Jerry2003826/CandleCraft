<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ResourceUploadService;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use RuntimeException;

class ResourceUploadServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Configure::delete('Uploads.resources_root');
        Configure::delete('Uploads.resources_url_prefix');

        parent::tearDown();
    }

    public function testPhpUploadIsRejected(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tmpFile, '<?php echo "bad";');

        $service = new ResourceUploadService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported file type.');
        $service->storeUploadedFile(new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            '../../evil.php',
            'application/x-php',
        ));
    }

    public function testOversizedUploadIsRejected(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        $handle = fopen($tmpFile, 'c');
        ftruncate($handle, 21 * 1024 * 1024);
        fclose($handle);

        $service = new ResourceUploadService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('File is too large.');
        $service->storeUploadedFile(new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'large.pdf',
            'application/pdf',
        ));
    }

    public function testDeleteStoredFileCannotEscapeUploadsRoot(): void
    {
        $resourcesDir = sys_get_temp_dir() . '/resource-upload-root-' . bin2hex(random_bytes(6));
        $outsideDir = dirname($resourcesDir);
        Configure::write('Uploads.resources_root', $resourcesDir);
        Configure::write('Uploads.resources_url_prefix', '/resources');

        if (!is_dir($resourcesDir)) {
            mkdir($resourcesDir, 0755, true);
        }

        $outsideFile = $outsideDir . DIRECTORY_SEPARATOR . 'escape.txt';
        file_put_contents($outsideFile, 'keep me');

        $service = new ResourceUploadService();
        $service->deleteStoredFile('resources/../escape.txt');

        $this->assertFileExists($outsideFile);
        unlink($outsideFile);
        rmdir($resourcesDir);
    }

    public function testUsesConfiguredStorageRootAndUrlPrefix(): void
    {
        $storageRoot = sys_get_temp_dir() . '/resource-upload-root-' . bin2hex(random_bytes(6));
        Configure::write('Uploads.resources_root', $storageRoot);
        Configure::write('Uploads.resources_url_prefix', '/dev/uploads/resources');

        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tmpFile, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n");

        $service = new ResourceUploadService();
        $storedPath = $service->storeUploadedFile(new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'notes.pdf',
            'application/pdf',
        ));

        $this->assertStringStartsWith('dev/uploads/resources/', $storedPath);
        $this->assertDirectoryExists($storageRoot);
        $this->assertFileExists($storageRoot . DIRECTORY_SEPARATOR . basename($storedPath));

        unlink($storageRoot . DIRECTORY_SEPARATOR . basename($storedPath));
        rmdir($storageRoot);
    }

    public function testClientMimeSpoofingIsRejectedByServerSideInspection(): void
    {
        $storageRoot = sys_get_temp_dir() . '/resource-upload-root-' . bin2hex(random_bytes(6));
        Configure::write('Uploads.resources_root', $storageRoot);
        Configure::write('Uploads.resources_url_prefix', '/resources');

        $tmpFile = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tmpFile, '<html><body>not really a pdf</body></html>');

        $service = new ResourceUploadService();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported file media type.');
        $service->storeUploadedFile(new UploadedFile(
            $tmpFile,
            filesize($tmpFile),
            UPLOAD_ERR_OK,
            'fake.pdf',
            'application/pdf',
        ));
    }
}
