<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Cms;

use App\Service\Cms\MediaUploader;
use App\Service\Cms\MediaUploadException;
use Cake\TestSuite\TestCase;
use Laminas\Diactoros\UploadedFile;

class MediaUploaderTest extends TestCase
{
    protected array $fixtures = [
        'app.Users',
        'app.SiteMedia',
    ];

    private string $tmpRoot;
    private MediaUploader $uploader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/cms_uploader_test_' . bin2hex(random_bytes(4));
        mkdir($this->tmpRoot, 0775, true);
        $this->uploader = new MediaUploader(
            uploadRoot: $this->tmpRoot,
            urlRoot: '/uploads/site',
        );
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpRoot);
        parent::tearDown();
    }

    public function testValidPngStored(): void
    {
        $upload = $this->makeUpload($this->validPngBytes(), 'logo.png', 'image/png');

        $media = $this->uploader->store($upload, 'CandleCraft logo', 1);

        $this->assertNotNull($media->get('id'));
        $this->assertSame('logo.png', $media->get('file_name'));
        $this->assertSame('image/png', $media->get('mime_type'));
        $this->assertStringStartsWith('uploads/site/', $media->get('file_path'));
        $this->assertStringStartsWith('/uploads/site/', $media->get('file_url'));
        $this->assertFileExists($this->tmpRoot . '/' . str_replace('uploads/site/', '', $media->get('file_path')));
    }

    public function testRejectsOversize(): void
    {
        $bigBytes = str_repeat("\x89PNG\r\n\x1a\n", 1);
        $bigBytes .= str_repeat('A', 6 * 1024 * 1024);
        $upload = $this->makeUpload($bigBytes, 'big.png', 'image/png');

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessageMatches('/size/i');
        $this->uploader->store($upload, null, 1);
    }

    public function testRejectsBadMime(): void
    {
        $upload = $this->makeUpload('plain text', 'evil.exe', 'application/x-msdownload');

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessageMatches('/mime/i');
        $this->uploader->store($upload, null, 1);
    }

    public function testRejectsMagicMismatch(): void
    {
        $upload = $this->makeUpload('not really a png', 'fake.png', 'image/png');

        $this->expectException(MediaUploadException::class);
        $this->expectExceptionMessageMatches('/magic|content/i');
        $this->uploader->store($upload, null, 1);
    }

    public function testFilenameIsHashed(): void
    {
        $bytes = $this->validPngBytes();
        $upload = $this->makeUpload($bytes, 'My Original.png', 'image/png');

        $media = $this->uploader->store($upload, null, 1);
        $this->assertMatchesRegularExpression(
            '#^uploads/site/[0-9]{4}/[0-9]{2}/[a-f0-9]{40}\.png$#',
            $media->get('file_path'),
        );
    }

    private function makeUpload(string $contents, string $name, string $mime): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tmp, $contents);

        return new UploadedFile($tmp, strlen($contents), UPLOAD_ERR_OK, $name, $mime);
    }

    private function validPngBytes(): string
    {
        return base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgAAIAAAUAAeImBZsAAAAASUVORK5CYII=',
        );
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir) ?: [], ['.', '..']);
        foreach ($items as $i) {
            $p = $dir . '/' . $i;
            if (is_dir($p)) {
                $this->rrmdir($p);
            } elseif (is_file($p)) {
                unlink($p);
            }
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }
}
