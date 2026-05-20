<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Cms;

use App\Service\Cms\ContentResolver;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;

class ContentResolverTest extends TestCase
{
    protected array $fixtures = [
        'app.SitePages',
        'app.SiteMedia',
        'app.PageSections',
    ];

    private ContentResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::drop('cms');
        Cache::setConfig('cms', ['className' => 'Array', 'duration' => '+1 hour', 'prefix' => 'test_cms_']);
        Cache::clear('cms');
        $this->resolver = new ContentResolver();
    }

    protected function tearDown(): void
    {
        Cache::clear('cms');
        parent::tearDown();
    }

    public function testReturnsActiveSectionsForKnownPage(): void
    {
        $bundle = $this->resolver->get('global');

        $this->assertArrayHasKey('branding.site_name', $bundle);
        $this->assertSame('CandleCraft Academy', $bundle['branding.site_name']['value']);
        $this->assertSame('text', $bundle['branding.site_name']['type']);
    }

    public function testInactiveSectionExcluded(): void
    {
        $bundle = $this->resolver->get('home');

        $this->assertArrayHasKey('hero.title', $bundle);
        $this->assertArrayNotHasKey('hero.eyebrow', $bundle, 'inactive sections should be excluded');
    }

    public function testImageTypeReturnsUrlAndAlt(): void
    {
        $bundle = $this->resolver->get('global');

        $this->assertArrayHasKey('branding.logo_image', $bundle);
        $logo = $bundle['branding.logo_image'];
        $this->assertSame('image', $logo['type']);
        $this->assertSame('/uploads/site/2026/05/abc123.png', $logo['url']);
        $this->assertSame('CandleCraft logo', $logo['alt']);
    }

    public function testUnknownPageReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->resolver->get('does-not-exist'));
    }

    public function testSecondCallUsesCache(): void
    {
        $first = $this->resolver->get('global');
        $cached = Cache::read('cms.page.global', 'cms');
        $this->assertNotNull($cached);
        $this->assertSame($first, $cached);
    }

    public function testInvalidateForcesRefetch(): void
    {
        $this->resolver->get('global');
        $this->assertNotNull(Cache::read('cms.page.global', 'cms'));

        $this->resolver->invalidate('global');
        $this->assertNull(Cache::read('cms.page.global', 'cms'));
    }
}
