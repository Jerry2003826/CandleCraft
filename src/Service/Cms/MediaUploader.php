<?php
declare(strict_types=1);

namespace App\Service\Cms;

use Cake\Datasource\EntityInterface;
use Cake\I18n\DateTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use finfo;
use Psr\Http\Message\UploadedFileInterface;

/**
 * Validates an uploaded image, stores it under `webroot/uploads/site/`, and
 * persists a `site_media` row. Throws {@see MediaUploadException} on any
 * validation failure (mime / size / magic bytes).
 *
 * Defaults follow spec §12 (5 MB cap, mime/magic check, hashed filename).
 */
final class MediaUploader
{
    use LocatorAwareTrait;

    private const ALLOWED_MIME = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'image/x-icon' => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
    ];
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * Construct.
     *
     * @param mixed $uploadRoot Uploadroot.
     * @param mixed $urlRoot Urlroot.
     * @return mixed
     */
    public function __construct(
        private ?string $uploadRoot = null,
        private string $urlRoot = '/uploads/site',
    ) {
        if ($this->uploadRoot === null) {
            $this->uploadRoot = WWW_ROOT . 'uploads' . DIRECTORY_SEPARATOR . 'site';
        }
    }

    /**
     * Store.
     *
     * @param mixed $file File.
     * @param mixed $alt Alt.
     * @param mixed $userId Userid.
     */
    public function store(UploadedFileInterface $file, ?string $alt, ?int $userId): EntityInterface
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new MediaUploadException('Upload failed before reaching the server.');
        }

        $size = (int)$file->getSize();
        if ($size <= 0) {
            throw new MediaUploadException('Uploaded file is empty.');
        }
        if ($size > self::MAX_BYTES) {
            throw new MediaUploadException(sprintf(
                'File size %d exceeds the 5 MB limit.',
                $size,
            ));
        }

        $declared = strtolower((string)$file->getClientMediaType());
        if (!isset(self::ALLOWED_MIME[$declared])) {
            throw new MediaUploadException('Unsupported mime type: ' . $declared);
        }

        $stream = $file->getStream();
        $stream->rewind();
        $contents = (string)$stream->getContents();

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detected = strtolower((string)$finfo->buffer($contents));
        if (!$this->mimeMatches($declared, $detected)) {
            throw new MediaUploadException(sprintf(
                'File magic bytes do not match declared content type (got %s, expected %s).',
                $detected,
                $declared,
            ));
        }

        $extension = self::ALLOWED_MIME[$declared];
        $hash = sha1($contents . microtime(true));
        $relativeDir = sprintf('%s/%s', date('Y'), date('m'));
        $absoluteDir = $this->uploadRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new MediaUploadException('Failed to create upload directory.');
        }

        $relativePath = sprintf('uploads/site/%s/%s.%s', $relativeDir, $hash, $extension);
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $hash . '.' . $extension;

        if (file_put_contents($absolutePath, $contents) === false) {
            throw new MediaUploadException('Failed to write uploaded file to disk.');
        }

        $url = rtrim($this->urlRoot, '/') . '/' . $relativeDir . '/' . $hash . '.' . $extension;

        $now = DateTime::now();
        $entity = $this->mediaTable()->newEntity([
            'file_name' => $file->getClientFilename() ?? ($hash . '.' . $extension),
            'file_path' => $relativePath,
            'file_url' => $url,
            'mime_type' => $declared,
            'file_size' => $size,
            'alt_text' => $alt,
            'uploaded_by_id' => $userId,
            'uploaded_at' => $now,
            'updated_at' => $now,
        ]);

        if (!$this->mediaTable()->save($entity)) {
            if (is_file($absolutePath)) {
                unlink($absolutePath);
            }
            throw new MediaUploadException('Failed to persist media record.');
        }

        return $entity;
    }

    /**
     * Mime matches.
     *
     * @param mixed $declared Declared.
     * @param mixed $detected Detected.
     */
    private function mimeMatches(string $declared, string $detected): bool
    {
        if ($detected === $declared) {
            return true;
        }
        if ($declared === 'image/x-icon' && in_array($detected, ['image/vnd.microsoft.icon', 'image/x-icon'], true)) {
            return true;
        }
        if ($declared === 'image/vnd.microsoft.icon' && in_array($detected, ['image/x-icon', 'image/vnd.microsoft.icon'], true)) {
            return true;
        }
        if ($declared === 'image/svg+xml' && str_contains($detected, 'svg')) {
            return true;
        }

        return false;
    }

    /**
     * Media table.
     */
    private function mediaTable(): Table
    {
        return $this->fetchTable('SiteMedia');
    }
}
