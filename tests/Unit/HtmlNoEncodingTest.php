<?php

declare(strict_types=1);

use OdinDev\CharWash\CharWash;
use OdinDev\CharWash\Processors\HtmlProcessor;

describe('HTML processor no encoding behavior', function () {
    it('does not encode ampersands', function () {
        $testCases = [
            "O'Reilly's Brand & Co.",
            "Sales & Marketing",
            "AT&T Corporation",
            "Q&A Session",
            "Terms & Conditions",
            "<p>Sales & Marketing</p>",
            '<a href="test.php?a=1&b=2">Link</a>',
        ];

        foreach ($testCases as $input) {
            $result = CharWash::sanitizeHtml($input);

            // Ensure ampersands are NOT encoded
            expect($result)->not->toContain('&amp;');
            expect($result)->toContain('&');
        }
    });

    it('does not encode other special characters', function () {
        $testCases = [
            ['input' => '<', 'should_not_contain' => '&lt;'],
            ['input' => '>', 'should_not_contain' => '&gt;'],
            ['input' => '"', 'should_not_contain' => '&quot;'],
            ['input' => "'", 'should_not_contain' => '&apos;'],
            ['input' => "Price < $100 & quality > average", 'should_not_contain' => ['&lt;', '&gt;', '&amp;']],
            ['input' => 'He said "Hello" & she said \'Goodbye\'', 'should_not_contain' => ['&quot;', '&apos;', '&amp;']],
        ];

        foreach ($testCases as $test) {
            $result = CharWash::sanitizeHtml($test['input']);

            $shouldNotContain = is_array($test['should_not_contain'])
                ? $test['should_not_contain']
                : [$test['should_not_contain']];

            foreach ($shouldNotContain as $pattern) {
                expect($result)->not->toContain($pattern);
            }
        }
    });

    it('decodes already encoded entities', function () {
        // Basic HTML entities
        expect(CharWash::sanitizeHtml('&amp;'))->toBe('&');
        expect(CharWash::sanitizeHtml('&lt;'))->toBe('<');
        expect(CharWash::sanitizeHtml('&gt;'))->toBe('>');
        expect(CharWash::sanitizeHtml('&quot;'))->toBe('"');

        // Numeric entities are decoded (not left as entities)
        $smartQuote = CharWash::sanitizeHtml('&#8220;');
        expect($smartQuote)->not->toContain('&#8220;'); // Entity is decoded
        expect($smartQuote)->not->toContain('&'); // No entities remain

        $emDash = CharWash::sanitizeHtml('&#8212;');
        expect($emDash)->not->toContain('&#8212;'); // Entity is decoded
        expect($emDash)->toBe('—'); // Actual em dash character

        // The key point is no double-encoding
        expect(CharWash::sanitizeHtml('&amp;amp;'))->toBe('&amp;'); // Double-encoded becomes single
    });

    it('preserves international characters without encoding', function () {
        $testCases = [
            'Café',
            'Naïve',
            'Résumé',
            '日本語',
            '中文',
            'Русский',
            '한국어',
        ];

        foreach ($testCases as $input) {
            $result = CharWash::sanitizeHtml($input);

            // Should remain unchanged
            expect($result)->toBe($input);

            // Should not contain any numeric entities
            expect($result)->not->toContain('&#');
        }
    });

    it('preserves special symbols without encoding', function () {
        $testCases = [
            '©®™',
            '€£¥$',
            '±×÷≠',
            '½¼¾',
            '∑∫∂∆',
        ];

        foreach ($testCases as $input) {
            $result = CharWash::sanitizeHtml($input);

            // Should remain unchanged
            expect($result)->toBe($input);

            // Should not contain any entities
            expect($result)->not->toContain('&');
        }
    });

    it('removes dangerous content without encoding', function () {
        $dangerousCases = [
            ['input' => '<script>alert("XSS")</script>Test', 'safe_output' => 'Test'],
            ['input' => '<img src="x" onerror="alert(1)">', 'safe_output' => '<img src="x" alt="x">'],
            ['input' => '<iframe src="evil.com"></iframe>Safe', 'safe_output' => 'Safe'],
        ];

        foreach ($dangerousCases as $test) {
            $result = CharWash::sanitizeHtml($test['input']);

            // Dangerous tags should be removed, not encoded
            expect($result)->not->toContain('<script');
            expect($result)->not->toContain('onerror');
            expect($result)->not->toContain('<iframe');

            // Should not contain encoded versions either
            expect($result)->not->toContain('&lt;script');
            expect($result)->not->toContain('&lt;iframe');
        }
    });

    it('works correctly through the full sanitization pipeline', function () {
        $testCases = [
            "O'Reilly's Brand & Co.",
            "Sales & Marketing Department",
            "AT&T Corporation",
            "<p>Content & More</p>",
        ];

        foreach ($testCases as $input) {
            $result = CharWash::sanitize($input);  // Full pipeline

            // Ampersands should NOT be encoded even through full pipeline
            expect($result)->not->toContain('&amp;');
        }
    });
});