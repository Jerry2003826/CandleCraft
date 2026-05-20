<?php
declare(strict_types=1);

namespace App\Service\Cms;

use DOMDocument;
use DOMElement;

/**
 * Tiny dependency-free HTML sanitizer used by CmsHelper for `html` content.
 *
 * Whitelist:
 *   - Tags: p, br, strong, em, b, i, u, a, ul, ol, li, span
 *   - Attributes: only `href`, `target`, `rel` on `<a>`
 *   - URL schemes on href: http, https, mailto
 *   - Force `rel="nofollow noopener"` on every <a>
 *
 * Policy is applied on render (not on save) so we can tighten without
 * re-migrating stored values.
 */
final class HtmlSanitizer
{
    private const ALLOWED_TAGS = ['p', 'br', 'strong', 'em', 'b', 'i', 'u', 'a', 'ul', 'ol', 'li', 'span'];
    private const ALLOWED_HREF_SCHEMES = ['http', 'https', 'mailto'];

    /**
     * Clean.
     *
     * @param mixed $html Html.
     */
    public function clean(?string $html): string
    {
        $html = (string)$html;
        if ($html === '') {
            return '';
        }

        $html = preg_replace(
            '#<\s*(script|style|iframe|object|embed)\b[^>]*>.*?<\s*/\s*\1\s*>#is',
            '',
            $html,
        ) ?? $html;
        $html = preg_replace(
            '#<\s*(script|style|iframe|object|embed)\b[^>]*/?>#is',
            '',
            $html,
        ) ?? $html;

        $allowedTagString = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $stripped = strip_tags($html, $allowedTagString);

        if ($stripped === '') {
            return '';
        }

        $doc = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $loaded = $doc->loadHTML(
            '<?xml encoding="UTF-8"?><div id="__cms_root__">' . $stripped . '</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING,
        );
        libxml_clear_errors();

        if ($loaded === false) {
            return strip_tags($stripped);
        }

        $root = $doc->getElementById('__cms_root__');
        if ($root === null) {
            $els = $doc->getElementsByTagName('div');
            if ($els->length > 0) {
                $root = $els->item(0);
            }
        }

        if ($root instanceof DOMElement) {
            $this->sanitizeNode($root);
        }

        $result = '';
        if ($root !== null) {
            foreach ($root->childNodes as $child) {
                $result .= $doc->saveHTML($child);
            }
        }

        return trim((string)$result);
    }

    /**
     * Sanitize node.
     *
     * @param mixed $node Node.
     */
    private function sanitizeNode(DOMElement $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if (!$child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);
            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                $this->unwrap($child);
                continue;
            }

            $this->sanitizeAttributes($child, $tag);
            $this->sanitizeNode($child);
        }
    }

    /**
     * Sanitize attributes.
     *
     * @param mixed $element Element.
     * @param mixed $tag Tag.
     */
    private function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = $tag === 'a' ? ['href', 'target', 'rel'] : [];
        $attributes = [];
        foreach ($element->attributes as $attr) {
            $attributes[$attr->name] = $attr->value;
        }

        foreach (array_keys($attributes) as $name) {
            if (!in_array(strtolower($name), $allowed, true)) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a') {
            $href = $attributes['href'] ?? '';
            $href = trim($href);
            $scheme = strtolower((string)parse_url($href, PHP_URL_SCHEME));
            if ($href === '' || ($scheme !== '' && !in_array($scheme, self::ALLOWED_HREF_SCHEMES, true))) {
                $element->removeAttribute('href');
            }
            $element->setAttribute('rel', 'nofollow noopener');
            if (!empty($attributes['target'])) {
                $element->setAttribute('target', $attributes['target']);
            }
        }
    }

    /**
     * Unwrap.
     *
     * @param mixed $element Element.
     */
    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if ($parent === null) {
            return;
        }
        while ($element->firstChild !== null) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }
}
