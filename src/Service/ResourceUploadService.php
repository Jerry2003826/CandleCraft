<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class ResourceUploadService
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;

    private const ALLOWED_EXTENSIONS = [
        'pdf',
        'doc',
        'docx',
        'ppt',
        'pptx',
        'jpg',
        'jpeg',
        'png',
        'mp4',
    ];

    private const ALLOWED_MEDIA_TYPES = [
        'application/msword',
        'application/pdf',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'image/jpeg',
        'image/png',
        'video/mp4',
    ];

    public function storeUploadedFile(UploadedFileInterface $file, string $directory = 'resources'): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The uploaded file could not be processed.');
        }

        $clientFilename = (string)($file->getClientFilename() ?? '');
        $extension = strtolower(pathinfo(basename($clientFilename), PATHINFO_EXTENSION));
        if ($extension === '' || !in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Unsupported file type.');
        }

        $mediaType = strtolower((string)($file->getClientMediaType() ?? ''));
        if ($mediaType !== '' && !in_array($mediaType, self::ALLOWED_MEDIA_TYPES, true)) {
            throw new RuntimeException('Unsupported file media type.');
        }

        $size = $file->getSize();
        if ($size === null || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('File is too large.');
        }

        $cleanDirectory = trim($directory, '/\\');
        if ($cleanDirectory !== 'resources') {
            throw new RuntimeException('Unsupported upload directory.');
        }

        $targetDir = $this->getStorageAbsoluteDirectory();
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Upload directory could not be created.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $file->moveTo($targetDir . DS . $storedName);

        return $this->getStorageDirectory() . '/' . $storedName;
    }

    public function deleteStoredFile(?string $relativePath): void
    {
        $normalizedPath = $this->normalizeStoredPath($relativePath);
        if ($normalizedPath === null) {
            return;
        }

        $uploadsRoot = realpath($this->getStorageAbsoluteDirectory());
        if ($uploadsRoot === false) {
            return;
        }

        $prefix = $this->getStorageDirectory();
        $fileName = substr($normalizedPath, strlen($prefix) + 1);
        if ($fileName === false || $fileName === '' || basename($fileName) !== $fileName) {
            return;
        }

        $candidatePath = $uploadsRoot . DIRECTORY_SEPARATOR . $fileName;
        if (!is_file($candidatePath)) {
            return;
        }

        $absolutePath = realpath($candidatePath);
        if ($absolutePath === false) {
            return;
        }

        $normalizedRoot = rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($absolutePath, $normalizedRoot)) {
            return;
        }

        unlink($absolutePath);
    }

    public function getMaxFileSize(): int
    {
        return self::MAX_FILE_SIZE;
    }

    /**
     * @return list<string>
     */
    public function getAllowedExtensions(): array
    {
        return self::ALLOWED_EXTENSIONS;
    }

    /**
     * @return list<string>
     */
    public function getAllowedMediaTypes(): array
    {
        return self::ALLOWED_MEDIA_TYPES;
    }

    public function getStorageDirectory(): string
    {
        return trim((string)Configure::read('Uploads.resources_url_prefix', '/uploads/resources'), '/');
    }

    public function getStorageAbsoluteDirectory(): string
    {
        return rtrim((string)Configure::read('Uploads.resources_root', WWW_ROOT . 'uploads' . DS . 'resources'), DIRECTORY_SEPARATOR);
    }

    public function normalizeStoredPath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        $prefix = $this->getStorageDirectory();
        if (!str_starts_with($normalized, $prefix . '/')) {
            return null;
        }

        return $normalized;
    }
}
