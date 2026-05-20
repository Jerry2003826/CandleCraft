<?php
declare(strict_types=1);

namespace App\Test\TestCase\Service\Cms;

use App\Service\Cms\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new HtmlSanitizer();
    }

    public function testEmptyInputReturnsEmpty(): void
    {
        $this->assertSame('', $this->sanitizer->clean(''));
        $this->assertSame('', $this->sanitizer->clean(null));
    }

    public function testAllowedTagsSurvive(): void
    {
        $html = '<p>Hello <strong>world</strong> with <em>emphasis</em>.</p>';
        $this->assertSame($html, $this->sanitizer->clean($html));
    }

    public function testListsAndLinksSurvive(): void
    {
        $html = '<ul><li>One</li><li>Two</li></ul><p>See <a href="https://example.com">link</a>.</p>';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertStringContainsString('<ul>', $cleaned);
        $this->assertStringContainsString('<li>One</li>', $cleaned);
        $this->assertStringContainsString('<a href="https://example.com"', $cleaned);
        $this->assertStringContainsString('rel="nofollow noopener"', $cleaned);
    }

    public function testScriptTagsAreStripped(): void
    {
        $html = '<p>Safe</p><script>alert(1)</script>';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertStringNotContainsString('<script', $cleaned);
        $this->assertStringNotContainsString('alert(1)', $cleaned);
        $this->assertStringContainsString('<p>Safe</p>', $cleaned);
    }

    public function testJavascriptUrlIsStripped(): void
    {
        $html = '<a href="javascript:alert(1)">click</a>';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertStringNotContainsString('javascript:', $cleaned);
    }

    public function testOnEventAttributesStripped(): void
    {
        $html = '<p onclick="alert(1)">x</p>';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertStringNotContainsString('onclick', $cleaned);
    }

    public function testMailtoUrlAllowed(): void
    {
        $html = '<a href="mailto:hi@example.com">Email</a>';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertStringContainsString('mailto:hi@example.com', $cleaned);
    }

    public function testMalformedHtmlDoesNotThrow(): void
    {
        $html = '<p>Unclosed <strong>tag';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertNotSame('', $cleaned);
        $this->assertStringContainsString('Unclosed', $cleaned);
    }

    public function testDisallowedTagContentSurvives(): void
    {
        $html = '<div><p>Inside</p></div>';
        $cleaned = $this->sanitizer->clean($html);
        $this->assertStringNotContainsString('<div>', $cleaned);
        $this->assertStringContainsString('<p>Inside</p>', $cleaned);
    }
}
