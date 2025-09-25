<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Tests;

use OdinDev\CharWash\Processors\HtmlProcessor;
use OdinDev\CharWash\Processors\UnicodeProcessor;
use OdinDev\CharWash\Processors\OfficeProcessor;
use OdinDev\CharWash\Processors\PunctuationProcessor;
use PHPUnit\Framework\TestCase;

class ProcessorsTest extends TestCase
{
    /**
     * Test UnicodeProcessor directly
     */
    public function testUnicodeProcessorRemovesBOM(): void
    {
        $processor = new UnicodeProcessor();

        // Test UTF-8 BOM
        $text = "\xEF\xBB\xBFHello World";
        $result = $processor->process($text);
        $this->assertEquals('Hello World', $result);

        // Test UTF-16 BE BOM
        $text = "\xFE\xFFHello World";
        $result = $processor->process($text);
        $this->assertEquals('Hello World', $result);
    }

    public function testUnicodeProcessorHandlesEmptyString(): void
    {
        $processor = new UnicodeProcessor();
        $this->assertEquals('', $processor->process(''));
    }

    public function testUnicodeProcessorNormalizesNFC(): void
    {
        $processor = new UnicodeProcessor();

        // Decomposed e + accent
        $text = "e\xCC\x81";
        $result = $processor->process($text);
        $this->assertEquals('é', $result);
    }

    /**
     * Test HtmlProcessor directly
     */
    public function testHtmlProcessorRemovesEmptyTags(): void
    {
        $processor = new HtmlProcessor();

        $html = '<p></p><div></div><span> </span><p>Content</p>';
        $result = $processor->process($html);

        $this->assertStringNotContainsString('<p></p>', $result);
        $this->assertStringNotContainsString('<div></div>', $result);
        $this->assertStringNotContainsString('<span> </span>', $result);
        $this->assertStringContainsString('<p>Content</p>', $result);
    }

    public function testHtmlProcessorHandlesMultipleBrTags(): void
    {
        $processor = new HtmlProcessor();

        $html = 'Line 1<br><br><br><br>Line 2';
        $result = $processor->process($html);

        // Should reduce to max 2 consecutive br tags
        $this->assertStringNotContainsString('<br><br><br>', $result);
    }

    public function testHtmlProcessorSecurityXSS(): void
    {
        $processor = new HtmlProcessor();

        // Various XSS attempts
        $tests = [
            '<script>alert("XSS")</script>' => '',
            '<img src=x onerror="alert(1)">' => '',
            '<a href="javascript:alert(1)">Click</a>' => '<a>Click</a>',
            '<iframe src="evil.com"></iframe>' => '',
            '<object data="evil.swf"></object>' => '',
            '<embed src="evil.swf">' => '',
        ];

        foreach ($tests as $input => $expected) {
            $result = $processor->process($input);
            $this->assertStringNotContainsString('<script', $result);
            $this->assertStringNotContainsString('javascript:', $result);
            $this->assertStringNotContainsString('onerror', $result);
            $this->assertStringNotContainsString('<iframe', $result);
            $this->assertStringNotContainsString('<object', $result);
            $this->assertStringNotContainsString('<embed', $result);
        }
    }

    /**
     * Test OfficeProcessor directly
     */
    public function testOfficeProcessorFixesEncoding(): void
    {
        $processor = new OfficeProcessor();

        // Test invalid UTF-8 handling
        $text = "Valid text\xFF\xFEinvalid bytes";
        $result = $processor->process($text);
        $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
    }

    public function testOfficeProcessorRemovesEmailArtifacts(): void
    {
        $processor = new OfficeProcessor();

        $text = "> Quote line 1\n>> Quote line 2\n-----Original Message-----\nFrom: sender@example.com\nNormal text";
        $result = $processor->process($text);

        $this->assertStringNotContainsString('> Quote', $result);
        $this->assertStringNotContainsString('-----Original Message', $result);
        $this->assertStringNotContainsString('From:', $result);
        $this->assertStringContainsString('Normal text', $result);
    }

    public function testOfficeProcessorRemovesVectorMarkup(): void
    {
        $processor = new OfficeProcessor();

        $text = '<v:shape>vector</v:shape><o:p>office</o:p><w:wrap>word</w:wrap>Normal text';
        $result = $processor->process($text);

        $this->assertStringNotContainsString('<v:', $result);
        $this->assertStringNotContainsString('<o:', $result);
        $this->assertStringNotContainsString('<w:', $result);
        $this->assertStringContainsString('Normal text', $result);
    }

    /**
     * Test PunctuationProcessor directly
     */
    public function testPunctuationProcessorNormalizesLigatures(): void
    {
        $processor = new PunctuationProcessor();

        $text = "\u{00C6}sop's \u{0153}uvre";
        $result = $processor->process($text);

        $this->assertEquals("AEsop's oeuvre", $result);
    }

    public function testPunctuationProcessorNormalizesSymbols(): void
    {
        $processor = new PunctuationProcessor();

        $text = "\u{00A9} 2024 Company\u{00AE} - Product\u{2122}";
        $result = $processor->process($text);

        $this->assertEquals("(c) 2024 Company(R) - Product(TM)", $result);
    }

    public function testPunctuationProcessorNormalizesFractions(): void
    {
        $processor = new PunctuationProcessor();

        $text = "\u{00BC} cup, \u{00BD} teaspoon, \u{00BE} tablespoon";
        $result = $processor->process($text);

        $this->assertEquals("1/4 cup, 1/2 teaspoon, 3/4 tablespoon", $result);
    }

    /**
     * Test malformed/edge case inputs
     */
    public function testProcessorsHandleNullBytes(): void
    {
        // Only UnicodeProcessor removes control characters including null bytes
        $processor = new UnicodeProcessor();
        $text = "Hello\x00World";
        $result = $processor->process($text);

        $this->assertStringNotContainsString("\x00", $result);
        $this->assertEquals('HelloWorld', $result);
    }

    public function testProcessorsHandleControlCharacters(): void
    {
        $processor = new UnicodeProcessor();

        // Various control characters
        $text = "Hello\x00\x01\x02\x03\x04\x05\x06\x07\x08World";
        $result = $processor->process($text);

        $this->assertEquals('HelloWorld', $result);

        // Tab and newline should be preserved (they're whitespace)
        $text = "Hello\tWorld\n";
        $result = $processor->process($text);

        $this->assertEquals("Hello\tWorld\n", $result);
    }

    public function testHtmlProcessorMalformedHTML(): void
    {
        $processor = new HtmlProcessor();

        // Unclosed tags
        $html = '<p>Paragraph <strong>bold text</p>';
        $result = $processor->process($html);
        $this->assertNotEmpty($result);

        // Mismatched tags
        $html = '<div><p>Content</div></p>';
        $result = $processor->process($html);
        $this->assertNotEmpty($result);

        // Invalid nesting
        $html = '<p><div>Invalid nesting</div></p>';
        $result = $processor->process($html);
        $this->assertNotEmpty($result);
    }
}