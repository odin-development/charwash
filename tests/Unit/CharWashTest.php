<?php

declare(strict_types=1);

use OdinDev\CharWash\CharWash;
use OdinDev\CharWash\Config\CharWashConfig;

beforeEach(function () {
    resetCharWashConfig();
});

describe('CharWash sanitization', function () {
    it('performs complete sanitization', function () {
        // Test with various problematic inputs
        $text = "\u{201C}Hello\u{201D}\u{2014}World\u{200B}😊\r\n*x000D*<!--[if mso]>test<![endif]-->";
        $result = CharWash::sanitize($text);

        // Should clean smart quotes, dashes, invisible chars, hex markers, and conditional comments
        expect($result)->not->toContain("\u{200B}");
        expect($result)->not->toContain("*x000D*");
        expect($result)->not->toContain("<!--[if mso]");
        expect($result)->toContain('"Hello"-World');
    });

    it('sanitizes HTML correctly', function () {
        // Test H1 to H2 conversion
        $html = '<h1>Title</h1><p>Content</p>';
        $result = CharWash::sanitizeHtml($html);

        expect($result)->toContain('<h2>Title</h2>');
        expect($result)->not->toContain('<h1>');

        // Test empty tag removal
        $html = '<p></p><p>Content</p><p> </p>';
        $result = CharWash::sanitizeHtml($html);

        expect($result)->not->toContain('<p></p>');
        expect($result)->toContain('<p>Content</p>');

        // Test security attributes
        $html = '<a href="https://example.com">Link</a>';
        $result = CharWash::sanitizeHtml($html);

        expect($result)->toContain('rel=');

        // Test XSS prevention
        $html = '<script>alert("XSS")</script><p>Safe content</p>';
        $result = CharWash::sanitizeHtml($html);

        expect($result)->not->toContain('<script>');
        expect($result)->toContain('Safe content');
    });

    it('sanitizes Unicode properly', function () {
        // Test invisible character removal
        $text = "Hello\u{200B}\u{200C}\u{200D}World\u{FEFF}";
        $result = CharWash::sanitizeUnicode($text);

        expect($result)->toBe('HelloWorld');

        // Test control character removal
        $text = "Hello\x08\x7F\x00World";
        $result = CharWash::sanitizeUnicode($text);

        expect($result)->toBe('HelloWorld');

        // Test soft hyphen removal
        $text = "Hel\u{00AD}lo";
        $result = CharWash::sanitizeUnicode($text);

        expect($result)->toBe('Hello');

        // Test NFC normalization
        $text = "e\xCC\x81"; // e + combining acute accent
        $result = CharWash::sanitizeUnicode($text);

        expect($result)->toBe('é');
    });

    it('cleans Office and email content', function () {
        // Test MSO style removal
        $text = '<p style="mso-line-height-rule:exactly">Content</p>';
        $result = CharWash::sanitizeOffice($text);

        expect($result)->not->toContain('mso-');

        // Test conditional comment removal
        $text = '<!--[if mso]>Office content<![endif]-->Regular content';
        $result = CharWash::sanitizeOffice($text);

        expect($result)->not->toContain('<!--[if mso]');
        expect($result)->toContain('Regular content');

        // Test hex marker removal
        $text = 'Line 1*x000D*Line 2_x000D_Line 3';
        $result = CharWash::sanitizeOffice($text);

        expect($result)->not->toContain('*x000D*');
        expect($result)->not->toContain('_x000D_');

        // Test mojibake fixing
        $text = 'Ã¢â‚¬â„¢test'; // Mojibake for apostrophe
        $result = CharWash::sanitizeOffice($text);

        expect($result)->toContain("'test");
    });

    it('normalizes punctuation', function () {
        // Test smart quote flattening
        $text = "\u{201C}Hello\u{201D} \u{2018}World\u{2019}";
        $result = CharWash::sanitizePunctuation($text);

        expect($result)->toBe('"Hello" \'World\'');

        // Test dash normalization
        $text = "Hello\u{2014}World\u{2013}Test";
        $result = CharWash::sanitizePunctuation($text);

        expect($result)->toBe('Hello-World-Test');

        // Test ellipsis normalization
        $text = "Hello\u{2026}World";
        $result = CharWash::sanitizePunctuation($text);

        expect($result)->toBe('Hello...World');

        // Test bullet normalization
        $text = "• Item 1\n• Item 2";
        $result = CharWash::sanitizePunctuation($text);

        expect($result)->toBe("* Item 1\n* Item 2");

        // Test ligature normalization
        $text = "\u{00C6}sthetic \u{0153}uvre";
        $result = CharWash::sanitizePunctuation($text);

        expect($result)->toBe('AEsthetic oeuvre');

        // Test full-width ASCII normalization
        $text = "\u{FF28}\u{FF45}\u{FF4C}\u{FF4C}\u{FF4F}\u{FF01}";
        $result = CharWash::sanitizePunctuation($text);

        expect($result)->toBe('Hello!');
    });

    it('provides complete sanitization alias', function () {
        $text = "\u{201C}Test\u{201D}\u{2014}content\u{200B}";

        $result1 = CharWash::sanitize($text);
        $result2 = CharWash::sanitizeComplete($text);

        // Both methods should produce identical results
        expect($result1)->toBe($result2);
    });

    it('handles empty strings gracefully', function () {
        expect(CharWash::sanitize(''))->toBe('');
        expect(CharWash::sanitizeHtml(''))->toBe('');
        expect(CharWash::sanitizeUnicode(''))->toBe('');
        expect(CharWash::sanitizeOffice(''))->toBe('');
        expect(CharWash::sanitizePunctuation(''))->toBe('');
        expect(CharWash::sanitizeComplete(''))->toBe('');
    });

    it('handles complex real-world scenarios', function () {
        // Simulate text copied from Word with various issues
        $text = <<<TEXT
<h1>\u{201C}Title\u{201D}\u{2014}with\u{2013}dashes\u{2026}</h1>
<p style="mso-line-height-rule:exactly">Hello\u{2014}World</p>
<!--[if mso]>Office only<![endif]-->
<p>Regular content*x000D*</p>
<p></p>
<a href="http://example.com">Link</a>
Ã¢â‚¬â„¢test
TEXT;

        $result = CharWash::sanitize($text);

        // Check various cleanups
        expect($result)->not->toContain('<h1>');
        expect($result)->toContain('<h2>');
        expect($result)->not->toContain('mso-');
        expect($result)->not->toContain('<!--[if mso]');
        expect($result)->not->toContain('*x000D*');
        expect($result)->not->toContain('Ã¢â‚¬â„¢');
        expect($result)->toContain("'test");
        expect($result)->toContain('"Title"-with-dashes...');
    });

    it('preserves UTF-8 characters', function () {
        $text = 'Café München Москва 北京 مرحبا';
        $result = CharWash::sanitizeUnicode($text);

        // Should preserve valid UTF-8 characters
        expect($result)->toContain('Café');
        expect($result)->toContain('München');
        expect($result)->toContain('Москва');
        expect($result)->toContain('北京');
        expect($result)->toContain('مرحبا');
    });
});

describe('CharWash configuration', function () {
    it('manages configuration properly', function () {
        // Set custom cache path
        CharWashConfig::setHtmlPurifierCachePath('/tmp/charwash');

        expect(CharWashConfig::getHtmlPurifierCachePath())->toBe('/tmp/charwash');
        expect(CharWashConfig::hasCustomCachePath())->toBeTrue();

        // Set allowed HTML tags
        CharWashConfig::setAllowedHtmlTags(['p', 'br', 'strong']);

        expect(CharWashConfig::getAllowedHtmlTags())->toBe(['p', 'br', 'strong']);

        // Test configuration export/import
        $config = CharWashConfig::toArray();

        expect($config)->toBeArray();
        expect($config['cache_path'])->toBe('/tmp/charwash');
        expect($config['allowed_tags'])->toBe(['p', 'br', 'strong']);

        // Test loading from array
        CharWashConfig::reset();
        CharWashConfig::loadFromArray([
            'cache_path' => '/custom/path',
            'allowed_tags' => ['div', 'span'],
        ]);

        expect(CharWashConfig::getHtmlPurifierCachePath())->toBe('/custom/path');
        expect(CharWashConfig::getAllowedHtmlTags())->toBe(['div', 'span']);
    });
});

test('performance with large content', function () {
    // Generate large HTML content
    $content = str_repeat('<p>This is a test paragraph with some \u{201C}smart quotes\u{201D} and\u{2014}dashes.</p>', 1000);

    $startTime = microtime(true);
    $result = CharWash::sanitize($content);
    $elapsed = microtime(true) - $startTime;

    expect($result)->not->toBeEmpty();
    expect($elapsed)->toBeLessThan(5); // Processing should complete within 5 seconds
});