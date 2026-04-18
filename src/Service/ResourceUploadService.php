<?php
declare(strict_types=1);

namespace App\Service;

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
        $targetDir = WWW_ROOT . 'uploads' . DS . $cleanDirectory;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Upload directory could not be created.');
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . $extension;
        $file->moveTo($targetDir . DS . $storedName);

        return 'uploads/' . $cleanDirectory . '/' . $storedName;
    }

    public function deleteStoredFile(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with($relativePath, 'uploads/resources/')) {
            return;
        }

        $uploadsRoot = realpath(WWW_ROOT . 'uploads' . DS . 'resources');
        if ($uploadsRoot === false) {
            return;
        }

        $absolutePath = realpath(WWW_ROOT . str_replace('/', DS, $relativePath));
        if ($absolutePath === false || !is_file($absolutePath)) {
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
        return 'uploads/resources';
    }

    public function getStorageAbsoluteDirectory(): string
    {
        return WWW_ROOT . 'uploads' . DS . 'resources';
    }

    public function normalizeStoredPath(?string $relativePath): ?string
    {
        if (!$relativePath || !str_starts_with($relativePath, 'uploads/resources/')) {
            return null;
        }

        return $relativePath;
    }
}
