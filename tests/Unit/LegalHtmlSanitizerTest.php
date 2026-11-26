<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Simplydigital\LegalDeskLawResources\Support\LegalHtmlSanitizer;

final class LegalHtmlSanitizerTest extends TestCase
{
    public function test_images_and_unsafe_markup_are_removed(): void
    {
        $html = LegalHtmlSanitizer::clean('<p onclick="alert(1)">Decision</p><img src="https://example.com/track"><img src="/storage/a.png"><img src="data:image/png;base64,AAAA"><iframe src="https://example.com"></iframe><script>alert(1)</script><a href="javascript:alert(1)">Link</a>');
        foreach (['<img', '<iframe', '<script', 'onclick=', 'javascript:', 'example.com/track'] as $unsafe) {
            self::assertStringNotContainsString($unsafe, $html);
        }
        self::assertStringContainsString('Decision', $html);
    }

    public function test_legal_formatting_is_preserved(): void
    {
        $html = LegalHtmlSanitizer::clean('<h2>Decision</h2><p><strong>Order</strong> <a href="https://example.com/law">Law</a></p><table><tbody><tr><td>Text</td></tr></tbody></table>');
        foreach (['<h2>', '<strong>', 'https://example.com/law', '<table>'] as $allowed) {
            self::assertStringContainsString($allowed, $html);
        }
        self::assertTrue(LegalHtmlSanitizer::hasText($html));
    }

    public function test_empty_or_image_only_decision_is_rejected(): void
    {
        self::assertFalse(LegalHtmlSanitizer::hasText(LegalHtmlSanitizer::clean('<p>&nbsp;<br></p><img src="https://example.com/a.png">')));
    }
}
