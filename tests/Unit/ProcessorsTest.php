<?php

declare(strict_types=1);

use OdinDev\CharWash\Processors\HtmlProcessor;
use OdinDev\CharWash\Processors\UnicodeProcessor;
use OdinDev\CharWash\Processors\OfficeProcessor;
use OdinDev\CharWash\Processors\PunctuationProcessor;

describe('UnicodeProcessor', function () {
    it('removes BOM characters', function () {
        $processor = new UnicodeProcessor();

        // Test UTF-8 BOM
        $text = "\xEF\xBB\xBFHello World";
        $result = $processor->process($text);
        expect($result)->toBe('Hello World');

        // Test UTF-16 BE BOM
        $text = "\xFE\xFFHello World";
        $result = $processor->process($text);
        expect($result)->toBe('Hello World');
    });

    it('handles empty strings', function () {
        $processor = new UnicodeProcessor();
        expect($processor->process(''))->toBe('');
    });

    it('normalizes to NFC', function () {
        $processor = new UnicodeProcessor();

        // Decomposed e + accent
        $text = "e\xCC\x81";
        $result = $processor->process($text);
        expect($result)->toBe('é');
    });

    it('handles null bytes and control characters', function () {
        $processor = new UnicodeProcessor();

        $text = "Hello\x00World";
        $result = $processor->process($text);
        expect($result)->not->toContain("\x00");
        expect($result)->toBe('HelloWorld');

        // Various control characters
        $text = "Hello\x00\x01\x02\x03\x04\x05\x06\x07\x08World";
        $result = $processor->process($text);
        expect($result)->toBe('HelloWorld');

        // Tab is preserved, newline is replaced with space
        $text = "Hello\tWorld\n";
        $result = $processor->process($text);
        expect($result)->toBe("Hello\tWorld ");
    });
});

describe('HtmlProcessor', function () {
    it('removes empty tags', function () {
        $processor = new HtmlProcessor();

        $html = '<p></p><div></div><span> </span><p>Content</p>';
        $result = $processor->process($html);

        expect($result)->not->toContain('<p></p>');
        expect($result)->not->toContain('<div></div>');
        expect($result)->not->toContain('<span> </span>');
        expect($result)->toContain('<p>Content</p>');
    });

    it('handles multiple br tags', function () {
        $processor = new HtmlProcessor();

        $html = 'Line 1<br><br><br><br>Line 2';
        $result = $processor->process($html);

        // Should reduce to max 2 consecutive br tags
        expect($result)->not->toContain('<br><br><br>');
    });

    it('prevents XSS attacks', function () {
        $processor = new HtmlProcessor();

        // Various XSS attempts
        $tests = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror="alert(1)">',
            '<a href="javascript:alert(1)">Click</a>',
            '<iframe src="evil.com"></iframe>',
            '<object data="evil.swf"></object>',
            '<embed src="evil.swf">',
        ];

        foreach ($tests as $input) {
            $result = $processor->process($input);
            expect($result)->not->toContain('<script');
            expect($result)->not->toContain('javascript:');
            expect($result)->not->toContain('onerror');
            expect($result)->not->toContain('<iframe');
            expect($result)->not->toContain('<object');
            expect($result)->not->toContain('<embed');
        }
    });

    it('handles malformed HTML', function () {
        $processor = new HtmlProcessor();

        // Unclosed tags
        $html = '<p>Paragraph <strong>bold text</p>';
        $result = $processor->process($html);
        expect($result)->not->toBeEmpty();

        // Mismatched tags
        $html = '<div><p>Content</div></p>';
        $result = $processor->process($html);
        expect($result)->not->toBeEmpty();

        // Invalid nesting
        $html = '<p><div>Invalid nesting</div></p>';
        $result = $processor->process($html);
        expect($result)->not->toBeEmpty();
    });
});

describe('OfficeProcessor', function () {
    it('fixes encoding issues', function () {
        $processor = new OfficeProcessor();

        // Test invalid UTF-8 handling
        $text = "Valid text\xFF\xFEinvalid bytes";
        $result = $processor->process($text);
        expect(mb_check_encoding($result, 'UTF-8'))->toBeTrue();
    });

    it('removes email artifacts', function () {
        $processor = new OfficeProcessor();

        $text = "> Quote line 1\n>> Quote line 2\n-----Original Message-----\nFrom: sender@example.com\nNormal text";
        $result = $processor->process($text);

        expect($result)->not->toContain('> Quote');
        expect($result)->not->toContain('-----Original Message');
        expect($result)->not->toContain('From:');
        expect($result)->toContain('Normal text');
    });

    it('removes vector markup', function () {
        $processor = new OfficeProcessor();

        $text = '<v:shape>vector</v:shape><o:p>office</o:p><w:wrap>word</w:wrap>Normal text';
        $result = $processor->process($text);

        expect($result)->not->toContain('<v:');
        expect($result)->not->toContain('<o:');
        expect($result)->not->toContain('<w:');
        expect($result)->toContain('Normal text');
    });
});

describe('PunctuationProcessor', function () {
    it('normalizes ligatures', function () {
        $processor = new PunctuationProcessor();

        $text = "\u{00C6}sop's \u{0153}uvre";
        $result = $processor->process($text);

        expect($result)->toBe("AEsop's oeuvre");
    });

    it('normalizes symbols', function () {
        $processor = new PunctuationProcessor();

        $text = "\u{00A9} 2024 Company\u{00AE} - Product\u{2122}";
        $result = $processor->process($text);

        expect($result)->toBe("(c) 2024 Company(R) - Product(TM)");
    });

    it('normalizes fractions', function () {
        $processor = new PunctuationProcessor();

        $text = "\u{00BC} cup, \u{00BD} teaspoon, \u{00BE} tablespoon";
        $result = $processor->process($text);

        expect($result)->toBe("1/4 cup, 1/2 teaspoon, 3/4 tablespoon");
    });
});