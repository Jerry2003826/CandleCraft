<?php
declare(strict_types=1);

namespace App\Test\TestCase\View\Helper;

use App\View\Helper\CmsHelper;
use Cake\Cache\Cache;
use Cake\TestSuite\TestCase;
use Cake\View\View;

class CmsHelperTest extends TestCase
{
    protected array $fixtures = [
        'app.SitePages',
        'app.SiteMedia',
        'app.PageSections',
    ];

    private CmsHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::drop('cms');
        Cache::setConfig('cms', ['className' => 'Array', 'duration' => '+1 hour', 'prefix' => 'test_cms_']);
        Cache::clear('cms');
        $this->helper = new CmsHelper(new View());
    }

    protected function tearDown(): void
    {
        Cache::clear('cms');
        parent::tearDown();
    }

    public function testTextReturnsValue(): void
    {
        $this->assertSame('CandleCraft Academy', $this->helper->text('global', 'branding.site_name'));
    }

    public function testTextFallbackWhenMissing(): void
    {
        $this->assertSame('Default', $this->helper->text('global', 'no.such.key', 'Default'));
    }

    public function testYearInterpolated(): void
    {
        $year = (string)date('Y');
        $this->assertSame(
            '© ' . $year . ' CandleCraft Academy.',
            $this->helper->text('global', 'branding.copyright_text'),
        );
    }

    public function testHtmlSanitizesScriptTags(): void
    {
        $rendered = $this->helper->html('contact', 'intro.body', '<p>fallback</p>');
        $this->assertStringContainsString('<p>Welcome', $rendered);
        $this->assertStringContainsString('<strong>everyone</strong>', $rendered);
        $this->assertStringNotContainsString('<script', $rendered);
    }

    public function testImageReturnsUrlWhenSet(): void
    {
        $this->assertSame('/uploads/site/2026/05/abc123.png', $this->helper->image('global', 'branding.logo_image'));
    }

    public function testImageReturnsFallbackWhenMissing(): void
    {
        $this->assertNull($this->helper->image('global', 'no.image.here'));
        $this->assertSame('/default.png', $this->helper->image('global', 'no.image.here', '/default.png'));
    }

    public function testImageAltReturnsAltOrFallback(): void
    {
        $this->assertSame('CandleCraft logo', $this->helper->imageAlt('global', 'branding.logo_image'));
        $this->assertSame('placeholder', $this->helper->imageAlt('global', 'no.image.here', 'placeholder'));
    }

    public function testAllReturnsBundle(): void
    {
        $bundle = $this->helper->all('global');
        $this->assertArrayHasKey('branding.site_name', $bundle);
        $this->assertArrayHasKey('branding.copyright_text', $bundle);
    }
}
