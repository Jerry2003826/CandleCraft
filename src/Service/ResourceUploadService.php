<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;
use finfo;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

class ResourceUploadService
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024;
    private const LEGACY_STORAGE_DIRECTORY = 'uploads/resources';

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

    private const ALLOWED_MEDIA_TYPES_BY_EXTENSION = [
        'doc' => [
            'application/msword',
        ],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ],
        'jpg' => [
            'image/jpeg',
        ],
        'jpeg' => [
            'image/jpeg',
        ],
        'pdf' => [
            'application/pdf',
        ],
        'png' => [
            'image/png',
        ],
        'ppt' => [
            'application/vnd.ms-powerpoint',
        ],
        'pptx' => [
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'application/zip',
        ],
        'mp4' => [
            'video/mp4',
            'application/mp4',
            'application/octet-stream',
        ],
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

        $size = $file->getSize();
        if ($size === null || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('File is too large.');
        }

        $detectedMediaType = $this->detectUploadedMediaType($file);
        if (!$this->isAllowedDetectedMediaType($extension, $detectedMediaType)) {
            throw new RuntimeException('Unsupported file media type.');
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
        return array_values(array_unique(array_merge(...array_values(self::ALLOWED_MEDIA_TYPES_BY_EXTENSION))));
    }

    public function getStorageDirectory(): string
    {
        return trim((string)Configure::read('Uploads.resources_url_prefix', '/resources'), '/');
    }

    public function getStorageAbsoluteDirectory(): string
    {
        return rtrim((string)Configure::read('Uploads.resources_root', ROOT . DS . 'storage' . DS . 'resources'), DIRECTORY_SEPARATOR);
    }

    public function resolveStoredFilePath(?string $relativePath): ?string
    {
        $storageLocation = $this->resolveStorageLocation($relativePath);
        if ($storageLocation === null || !is_file($storageLocation['absolute_path'])) {
            return null;
        }

        return $storageLocation['absolute_path'];
    }

    public function detectStoredFileMediaType(string $absolutePath): string
    {
        $mediaType = $this->detectFileMediaType($absolutePath);
        if ($mediaType !== null) {
            return $mediaType;
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return $this->fallbackMediaTypeForExtension($extension);
    }

    public function normalizeStoredPath(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        foreach ($this->getAllowedStorageDirectories() as $prefix) {
            if (str_starts_with($normalized, $prefix . '/')) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function getAllowedStorageDirectories(): array
    {
        return array_values(array_unique([
            $this->getStorageDirectory(),
            self::LEGACY_STORAGE_DIRECTORY,
        ]));
    }

    /**
     * @return array{absolute_path:string,file_name:string}|null
     */
    private function resolveStorageLocation(?string $relativePath): ?array
    {
        $normalizedPath = $this->normalizeStoredPath($relativePath);
        if ($normalizedPath === null) {
            return null;
        }

        foreach ($this->getStorageRootsByPrefix() as $prefix => $absoluteDirectory) {
            if (!str_starts_with($normalizedPath, $prefix . '/')) {
                continue;
            }

            $fileName = substr($normalizedPath, strlen($prefix) + 1);
            if ($fileName === false || $fileName === '' || basename($fileName) !== $fileName) {
                return null;
            }

            return [
                'absolute_path' => rtrim($absoluteDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName,
                'file_name' => $fileName,
            ];
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function getStorageRootsByPrefix(): array
    {
        return [
            $this->getStorageDirectory() => $this->getStorageAbsoluteDirectory(),
            self::LEGACY_STORAGE_DIRECTORY => rtrim(WWW_ROOT . 'uploads' . DS . 'resources', DIRECTORY_SEPARATOR),
        ];
    }

    private function detectUploadedMediaType(UploadedFileInterface $file): string
    {
        try {
            $stream = $file->getStream();
            $streamMetadata = $stream->getMetadata();
            $streamPath = is_array($streamMetadata) ? ($streamMetadata['uri'] ?? null) : null;
        } catch (\Throwable) {
            $streamPath = null;
        }

        if (!is_string($streamPath) || $streamPath === '' || !is_file($streamPath)) {
            throw new RuntimeException('Unable to inspect uploaded file.');
        }

        return strtolower((string)$this->detectStoredFileMediaType($streamPath));
    }

    private function detectFileMediaType(string $absolutePath): ?string
    {
        if (!is_file($absolutePath)) {
            return null;
        }

        $detector = new finfo(FILEINFO_MIME_TYPE);
        $mediaType = $detector->file($absolutePath);
        if (!is_string($mediaType) || $mediaType === '') {
            return null;
        }

        return strtolower($mediaType);
    }

    private function isAllowedDetectedMediaType(string $extension, string $mediaType): bool
    {
        $allowedMediaTypes = self::ALLOWED_MEDIA_TYPES_BY_EXTENSION[$extension] ?? [];

        return in_array($mediaType, $allowedMediaTypes, true);
    }

    private function fallbackMediaTypeForExtension(string $extension): string
    {
        return match ($extension) {
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg', 'jpeg' => 'image/jpeg',
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'mp4' => 'video/mp4',
            default => 'application/octet-stream',
        };
    }
}
