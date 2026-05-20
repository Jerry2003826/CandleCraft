<?php
declare(strict_types=1);

namespace App\Service\Cms;

use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Resolves CMS section values for a given page slug, with caching.
 *
 * Returns an associative array keyed by `section_key`. Each value is
 * itself an associative array with at minimum `type` and one of:
 *   - text/textarea/html/url/email/number → `value` (string)
 *   - image                               → `url`, `alt`, `media_id`
 *
 * Callers use the value array directly; the CmsHelper wraps this for
 * ergonomic templating.
 */
final class ContentResolver
{
    use LocatorAwareTrait;

    public const CACHE_CONFIG = 'cms';

    /**
     * Get.
     *
     * @param mixed $pageSlug Pageslug.
     */
    public function get(string $pageSlug): array
    {
        $cacheKey = $this->cacheKey($pageSlug);
        $cached = $this->safeCacheRead($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $bundle = $this->fetchFromDb($pageSlug);
        $this->safeCacheWrite($cacheKey, $bundle);

        return $bundle;
    }

    /**
     * Invalidate.
     *
     * @param mixed $pageSlug Pageslug.
     */
    public function invalidate(string $pageSlug): void
    {
        $this->safeCacheDelete($this->cacheKey($pageSlug));
    }

    /**
     * Invalidate all.
     */
    public function invalidateAll(): void
    {
        try {
            Cache::clear(self::CACHE_CONFIG);
        } catch (Throwable) {
            // Cache backend not configured; nothing to invalidate.
        }
    }

    /**
     * Cache key.
     *
     * @param mixed $pageSlug Pageslug.
     */
    private function cacheKey(string $pageSlug): string
    {
        return 'cms.page.' . $pageSlug;
    }

    /**
     * Fetch from db.
     *
     * @param mixed $pageSlug Pageslug.
     */
    private function fetchFromDb(string $pageSlug): array
    {
        $sitePages = $this->fetchTable('SitePages');
        $page = $sitePages->find()
            ->where(['SitePages.page_slug' => $pageSlug, 'SitePages.is_active' => true])
            ->first();
        if ($page === null) {
            return [];
        }

        $sections = $this->fetchTable('PageSections')
            ->find()
            ->contain(['SiteMedia'])
            ->where([
                'PageSections.page_id' => $page->get('id'),
                'PageSections.is_active' => true,
            ])
            ->orderBy(['PageSections.sort_order' => 'ASC'])
            ->all();

        $bundle = [];
        foreach ($sections as $section) {
            $bundle[$section->get('section_key')] = $this->normalize($section);
        }

        return $bundle;
    }

    /**
     * Normalize.
     *
     * @param mixed $section Section.
     */
    private function normalize(EntityInterface $section): array
    {
        $type = (string)$section->get('content_type');
        if ($type === 'image') {
            $media = $section->get('site_media');

            return [
                'type' => 'image',
                'url' => $media?->get('file_url'),
                'alt' => $media?->get('alt_text'),
                'media_id' => $section->get('media_id'),
            ];
        }

        return [
            'type' => $type,
            'value' => (string)$section->get('content_value'),
        ];
    }

    /**
     * Safe cache read.
     *
     * @param mixed $key Key.
     */
    private function safeCacheRead(string $key): mixed
    {
        try {
            return Cache::read($key, self::CACHE_CONFIG);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Safe cache write.
     *
     * @param mixed $key Key.
     * @param mixed $value Value.
     */
    private function safeCacheWrite(string $key, array $value): void
    {
        try {
            Cache::write($key, $value, self::CACHE_CONFIG);
        } catch (Throwable) {
            // Cache unavailable; resolve direct from DB next time.
        }
    }

    /**
     * Safe cache delete.
     *
     * @param mixed $key Key.
     */
    private function safeCacheDelete(string $key): void
    {
        try {
            Cache::delete($key, self::CACHE_CONFIG);
        } catch (Throwable) {
            // ignore
        }
    }
}
