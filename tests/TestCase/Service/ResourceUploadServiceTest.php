<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service;

use App\Service\ResourceUploadService;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;
use RuntimeException;

class ResourceUploadServiceTest extends TestCase
{
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
            'application/x-php'
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
            'application/pdf'
        ));
    }

    public function testDeleteStoredFileCannotEscapeUploadsRoot(): void
    {
        $resourcesDir = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'resources';
        $outsideDir = WWW_ROOT . 'uploads';
        if (!is_dir($resourcesDir)) {
            mkdir($resourcesDir, 0755, true);
        }
        if (!is_dir($outsideDir)) {
            mkdir($outsideDir, 0755, true);
        }

        $outsideFile = $outsideDir . DIRECTORY_SEPARATOR . 'escape.txt';
        file_put_contents($outsideFile, 'keep me');

        $service = new ResourceUploadService();
        $service->deleteStoredFile('uploads/resources/../escape.txt');

        $this->assertFileExists($outsideFile);
        unlink($outsideFile);
    }
}
