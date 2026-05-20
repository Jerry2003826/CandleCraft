<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\Cms\ContentResolver;
use App\Service\Cms\HtmlSanitizer;
use Cake\View\Helper;

/**
 * View helper for CMS-managed content.
 *
 * Public API:
 *   $this->Cms->text($pageSlug, $sectionKey, $fallback = ''): string
 *   $this->Cms->html($pageSlug, $sectionKey, $fallback = ''): string  (sanitized)
 *   $this->Cms->image($pageSlug, $sectionKey, ?string $fallback = null): ?string
 *   $this->Cms->imageAlt($pageSlug, $sectionKey, $fallback = ''): string
 *   $this->Cms->all($pageSlug): array
 *
 * `text()` and the value-of-html sections interpolate `{year}` to the
 * current 4-digit year, so the footer copyright stays current automatically.
 */
class CmsHelper extends Helper
{
    private ContentResolver $resolver;
    private HtmlSanitizer $sanitizer;

    /**
     * @var array<string, array<string, mixed>> Per-render bundle cache
     */
    private array $loaded = [];

    /**
     * Initialize.
     *
     * @param mixed $config Config.
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->resolver = new ContentResolver();
        $this->sanitizer = new HtmlSanitizer();
    }

    /**
     * Text.
     *
     * @param mixed $pageSlug Pageslug.
     * @param mixed $sectionKey Sectionkey.
     * @param mixed $fallback Fallback.
     */
    public function text(string $pageSlug, string $sectionKey, string $fallback = ''): string
    {
        $entry = $this->lookup($pageSlug, $sectionKey);
        if ($entry === null) {
            return $this->interpolate($fallback);
        }
        if (($entry['type'] ?? null) === 'image') {
            return $this->interpolate($fallback);
        }

        return $this->interpolate((string)($entry['value'] ?? $fallback));
    }

    /**
     * Html.
     *
     * @param mixed $pageSlug Pageslug.
     * @param mixed $sectionKey Sectionkey.
     * @param mixed $fallback Fallback.
     */
    public function html(string $pageSlug, string $sectionKey, string $fallback = ''): string
    {
        $entry = $this->lookup($pageSlug, $sectionKey);
        $raw = $entry['value'] ?? null;
        if ($raw === null || $raw === '') {
            $raw = $fallback;
        }

        return $this->sanitizer->clean($this->interpolate((string)$raw));
    }

    /**
     * Image.
     *
     * @param mixed $pageSlug Pageslug.
     * @param mixed $sectionKey Sectionkey.
     * @param mixed $fallback Fallback.
     */
    public function image(string $pageSlug, string $sectionKey, ?string $fallback = null): ?string
    {
        $entry = $this->lookup($pageSlug, $sectionKey);
        if ($entry === null || ($entry['type'] ?? null) !== 'image') {
            return $fallback;
        }
        $url = $entry['url'] ?? null;

        return $url !== null && $url !== '' ? (string)$url : $fallback;
    }

    /**
     * Image alt.
     *
     * @param mixed $pageSlug Pageslug.
     * @param mixed $sectionKey Sectionkey.
     * @param mixed $fallback Fallback.
     */
    public function imageAlt(string $pageSlug, string $sectionKey, string $fallback = ''): string
    {
        $entry = $this->lookup($pageSlug, $sectionKey);
        if ($entry === null || ($entry['type'] ?? null) !== 'image') {
            return $fallback;
        }
        $alt = (string)($entry['alt'] ?? '');

        return $alt !== '' ? $alt : $fallback;
    }

    /**
     * All.
     *
     * @param mixed $pageSlug Pageslug.
     */
    public function all(string $pageSlug): array
    {
        return $this->bundle($pageSlug);
    }

    /**
     * Lookup.
     *
     * @param mixed $pageSlug Pageslug.
     * @param mixed $sectionKey Sectionkey.
     */
    private function lookup(string $pageSlug, string $sectionKey): ?array
    {
        $bundle = $this->bundle($pageSlug);

        return $bundle[$sectionKey] ?? null;
    }

    /**
     * Bundle.
     *
     * @param mixed $pageSlug Pageslug.
     */
    private function bundle(string $pageSlug): array
    {
        if (!array_key_exists($pageSlug, $this->loaded)) {
            $this->loaded[$pageSlug] = $this->resolver->get($pageSlug);
        }

        return $this->loaded[$pageSlug];
    }

    /**
     * Interpolate.
     *
     * @param mixed $value Value.
     */
    private function interpolate(string $value): string
    {
        if (!str_contains($value, '{year}')) {
            return $value;
        }

        return str_replace('{year}', (string)date('Y'), $value);
    }
}
