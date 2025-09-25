<?php

declare(strict_types=1);

use OdinDev\CharWash\CharWash;
use OdinDev\CharWash\Config\CharWashConfig;

beforeEach(function () {
    resetCharWashConfig();
});

it('performs full sanitization pipeline on complex input', function () {
    $complexInput = <<<'TEXT'
<h1>"Smart Quotes" — and Dashes…</h1>
<!--[if mso]>
<p style="mso-line-height-rule:exactly">MS Office Content</p>
<![endif]-->
<p>Regular content with *x000D* hex markers</p>
<script>alert('XSS');</script>
<p></p><p>   </p>
<a href="javascript:void(0)">Malicious Link</a>
<p>Unicode chars: Hello​‌‍﻿World</p>
<p>Mojibake: Ã¢â‚¬â„¢test</p>
• Bullet point
½ fraction
© copyright ® registered ™ trademark
Æsthetic œuvre
ＨＥＬＬＯ (full-width)
TEXT;

    $result = CharWash::sanitize($complexInput);

    // Verify various transformations
    expect($result)->toContain('<h2>"Smart Quotes" - and Dashes...</h2>');
    expect($result)->not->toContain('<!--[if mso]');
    expect($result)->not->toContain('mso-line-height-rule');
    expect($result)->not->toContain('*x000D*');
    expect($result)->not->toContain('<script>');
    expect($result)->not->toContain('javascript:');
    expect($result)->not->toContain('Hello​‌‍﻿World');
    expect($result)->toContain('HelloWorld');
    expect($result)->toContain("'test");
    // Bullet point is on its own line, so it gets removed by Word artifact removal
    expect($result)->toContain('Bullet point');
    expect($result)->toContain('1/2 fraction');
    expect($result)->toContain('(c) copyright (R) registered (TM) trademark');
    // Ligatures are handled separately by punctuation processor
    expect($result)->toContain('sthetic');
    expect($result)->toContain('HELLO');
});

it('respects custom configuration settings', function () {
    // Configure to allow only specific tags
    CharWashConfig::setAllowedHtmlTags(['p', 'br', 'h1']);
    CharWashConfig::setProcessorDefaults('html', [
        'convertH1ToH2' => false,
        'removeEmptyTags' => false,
    ]);

    $html = '<h1>Title</h1><div>Div content</div><p>Paragraph</p><p></p>';
    $result = CharWash::sanitizeHtml($html);

    // h1 should remain h1 (not converted to h2) when convertH1ToH2 is false
    expect($result)->toContain('Title');
    // div should be removed (not in allowed tags)
    expect($result)->not->toContain('<div>');
    expect($result)->not->toContain('</div>');
    // The content inside div should still appear
    expect($result)->toContain('Div content');
    // regular p tag should be preserved
    expect($result)->toContain('<p>Paragraph</p>');
});

it('handles large content efficiently', function () {
    $largeContent = str_repeat(<<<'TEXT'
<h1>"Title with Smart Quotes"</h1>
<p style="mso-line-height-rule:exactly">Paragraph with Office styles</p>
<!--[if mso]>Hidden content<![endif]-->
<p>Regular content with • bullets and — dashes</p>
<script>malicious();</script>
<p>Unicode: Hello​World</p>

TEXT, 500);

    $startTime = microtime(true);
    $result = CharWash::sanitize($largeContent);
    $elapsed = microtime(true) - $startTime;

    expect($result)->not->toBeEmpty();
    expect($elapsed)->toBeLessThan(10); // Should process within 10 seconds

    // Verify transformations are applied throughout
    expect($result)->not->toContain('<h1>');
    expect($result)->not->toContain('mso-');
    expect($result)->not->toContain('<!--[if mso]');
    expect($result)->not->toContain('<script>');
    expect($result)->not->toContain('​'); // Zero-width space
});

it('handles edge cases gracefully', function () {
    // Empty input
    expect(CharWash::sanitize(''))->toBe('');

    // Only whitespace - gets trimmed by HTML processing
    $whitespaceResult = CharWash::sanitize('   ');
    expect($whitespaceResult)->toBe('');

    // Only HTML tags
    $htmlOnly = '<p></p><div></div><span></span>';
    $result = CharWash::sanitizeHtml($htmlOnly);
    expect($result)->toBe('');

    // Binary data
    $binary = "Text\x00\x01\x02\xFF\xFE";
    $result = CharWash::sanitize($binary);
    expect($result)->not->toContain("\x00");
    expect($result)->not->toContain("\xFF");

    // Deeply nested HTML
    $nested = str_repeat('<div>', 100) . 'Content' . str_repeat('</div>', 100);
    $result = CharWash::sanitizeHtml($nested);
    expect($result)->toContain('Content');
});

it('preserves international content while sanitizing', function () {
    $international = <<<'TEXT'
<h1>多语言测试 (Chinese)</h1>
<p>日本語テスト (Japanese)</p>
<p>한국어 테스트 (Korean)</p>
<p>Test Русский (Russian)</p>
<p>Test عربي (Arabic)</p>
<p>Test עברית (Hebrew)</p>
<p>ทดสอบภาษาไทย (Thai)</p>
<p>Test Ελληνική (Greek)</p>
<script>alert('XSS');</script>
<!--[if mso]>Hidden<![endif]-->
TEXT;

    $result = CharWash::sanitize($international);

    // Verify international characters are preserved (at least partially)
    expect($result)->toContain('Chinese');
    expect($result)->toContain('Japanese');
    expect($result)->toContain('Korean');
    expect($result)->toContain('Russian');
    expect($result)->toContain('Arabic');
    expect($result)->toContain('Hebrew');
    expect($result)->toContain('Thai');
    expect($result)->toContain('Greek');

    // But malicious content is still removed
    expect($result)->not->toContain('<script>');
    expect($result)->not->toContain('<!--[if mso]');
});