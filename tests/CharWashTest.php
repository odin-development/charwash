<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Tests;

use OdinDev\CharWash\CharWash;
use OdinDev\CharWash\Config\CharWashConfig;
use PHPUnit\Framework\TestCase;

class CharWashTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset config before each test
        CharWashConfig::reset();
    }

    /**
     * Test complete sanitization
     */
    public function testSanitize(): void
    {
        // Test with various problematic inputs
        $text = "\u{201C}Hello\u{201D}\u{2014}World\u{200B}😊\r\n*x000D*<!--[if mso]>test<![endif]-->";
        $result = CharWash::sanitize($text);

        // Should clean smart quotes, dashes, invisible chars, hex markers, and conditional comments
        $this->assertStringNotContainsString("\u{200B}", $result);
        $this->assertStringNotContainsString("*x000D*", $result);
        $this->assertStringNotContainsString("<!--[if mso]", $result);
        $this->assertStringContainsString('"Hello"-World', $result);
    }

    /**
     * Test HTML sanitization
     */
    public function testSanitizeHtml(): void
    {
        // Test H1 to H2 conversion
        $html = '<h1>Title</h1><p>Content</p>';
        $result = CharWash::sanitizeHtml($html);
        $this->assertStringContainsString('<h2>Title</h2>', $result);
        $this->assertStringNotContainsString('<h1>', $result);

        // Test empty tag removal
        $html = '<p></p><p>Content</p><p> </p>';
        $result = CharWash::sanitizeHtml($html);
        $this->assertStringNotContainsString('<p></p>', $result);
        $this->assertStringContainsString('<p>Content</p>', $result);

        // Test security attributes
        $html = '<a href="https://example.com">Link</a>';
        $result = CharWash::sanitizeHtml($html);
        $this->assertStringContainsString('rel=', $result);

        // Test XSS prevention
        $html = '<script>alert("XSS")</script><p>Safe content</p>';
        $result = CharWash::sanitizeHtml($html);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Safe content', $result);
    }

    /**
     * Test Unicode sanitization
     */
    public function testSanitizeUnicode(): void
    {
        // Test invisible character removal
        $text = "Hello\u{200B}\u{200C}\u{200D}World\u{FEFF}";
        $result = CharWash::sanitizeUnicode($text);
        $this->assertEquals('HelloWorld', $result);

        // Test control character removal
        $text = "Hello\x08\x7F\x00World";
        $result = CharWash::sanitizeUnicode($text);
        $this->assertEquals('HelloWorld', $result);

        // Test soft hyphen removal
        $text = "Hel\u{00AD}lo";
        $result = CharWash::sanitizeUnicode($text);
        $this->assertEquals('Hello', $result);

        // Test NFC normalization
        $text = "e\xCC\x81"; // e + combining acute accent
        $result = CharWash::sanitizeUnicode($text);
        $this->assertEquals('é', $result);
    }

    /**
     * Test Office/email cleanup
     */
    public function testSanitizeOffice(): void
    {
        // Test MSO style removal
        $text = '<p style="mso-line-height-rule:exactly">Content</p>';
        $result = CharWash::sanitizeOffice($text);
        $this->assertStringNotContainsString('mso-', $result);

        // Test conditional comment removal
        $text = '<!--[if mso]>Office content<![endif]-->Regular content';
        $result = CharWash::sanitizeOffice($text);
        $this->assertStringNotContainsString('<!--[if mso]', $result);
        $this->assertStringContainsString('Regular content', $result);

        // Test hex marker removal
        $text = 'Line 1*x000D*Line 2_x000D_Line 3';
        $result = CharWash::sanitizeOffice($text);
        $this->assertStringNotContainsString('*x000D*', $result);
        $this->assertStringNotContainsString('_x000D_', $result);

        // Test mojibake fixing
        $text = 'Ã¢â‚¬â„¢test'; // Mojibake for apostrophe
        $result = CharWash::sanitizeOffice($text);
        $this->assertStringContainsString("'test", $result);
    }

    /**
     * Test punctuation normalization
     */
    public function testSanitizePunctuation(): void
    {
        // Test smart quote flattening
        $text = "\u{201C}Hello\u{201D} \u{2018}World\u{2019}";
        $result = CharWash::sanitizePunctuation($text);
        $this->assertEquals('"Hello" \'World\'', $result);

        // Test dash normalization
        $text = "Hello\u{2014}World\u{2013}Test";
        $result = CharWash::sanitizePunctuation($text);
        $this->assertEquals('Hello-World-Test', $result);

        // Test ellipsis normalization
        $text = "Hello\u{2026}World";
        $result = CharWash::sanitizePunctuation($text);
        $this->assertEquals('Hello...World', $result);

        // Test bullet normalization
        $text = "• Item 1\n• Item 2";
        $result = CharWash::sanitizePunctuation($text);
        $this->assertEquals("* Item 1\n* Item 2", $result);

        // Test ligature normalization
        $text = "\u{00C6}sthetic \u{0153}uvre";
        $result = CharWash::sanitizePunctuation($text);
        $this->assertEquals('AEsthetic oeuvre', $result);

        // Test full-width ASCII normalization
        $text = "\u{FF28}\u{FF45}\u{FF4C}\u{FF4C}\u{FF4F}\u{FF01}";
        $result = CharWash::sanitizePunctuation($text);
        $this->assertEquals('Hello!', $result);
    }

    /**
     * Test complete sanitization alias
     */
    public function testSanitizeComplete(): void
    {
        $text = "\u{201C}Test\u{201D}\u{2014}content\u{200B}";

        $result1 = CharWash::sanitize($text);
        $result2 = CharWash::sanitizeComplete($text);

        // Both methods should produce identical results
        $this->assertEquals($result1, $result2);
    }

    /**
     * Test configuration
     */
    public function testConfiguration(): void
    {
        // Set custom cache path
        CharWashConfig::setHtmlPurifierCachePath('/tmp/charwash');
        $this->assertEquals('/tmp/charwash', CharWashConfig::getHtmlPurifierCachePath());
        $this->assertTrue(CharWashConfig::hasCustomCachePath());

        // Set allowed HTML tags
        CharWashConfig::setAllowedHtmlTags(['p', 'br', 'strong']);
        $this->assertEquals(['p', 'br', 'strong'], CharWashConfig::getAllowedHtmlTags());

        // Test configuration export/import
        $config = CharWashConfig::toArray();
        $this->assertIsArray($config);
        $this->assertEquals('/tmp/charwash', $config['cache_path']);
        $this->assertEquals(['p', 'br', 'strong'], $config['allowed_tags']);

        // Test loading from array
        CharWashConfig::reset();
        CharWashConfig::loadFromArray([
            'cache_path' => '/custom/path',
            'allowed_tags' => ['div', 'span'],
        ]);
        $this->assertEquals('/custom/path', CharWashConfig::getHtmlPurifierCachePath());
        $this->assertEquals(['div', 'span'], CharWashConfig::getAllowedHtmlTags());
    }

    /**
     * Test empty string handling
     */
    public function testEmptyStringHandling(): void
    {
        $this->assertEquals('', CharWash::sanitize(''));
        $this->assertEquals('', CharWash::sanitizeHtml(''));
        $this->assertEquals('', CharWash::sanitizeUnicode(''));
        $this->assertEquals('', CharWash::sanitizeOffice(''));
        $this->assertEquals('', CharWash::sanitizePunctuation(''));
        $this->assertEquals('', CharWash::sanitizeComplete(''));
    }

    /**
     * Test complex real-world scenario
     */
    public function testComplexRealWorldScenario(): void
    {
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
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringContainsString('<h2>', $result);
        $this->assertStringNotContainsString('mso-', $result);
        $this->assertStringNotContainsString('<!--[if mso]', $result);
        $this->assertStringNotContainsString('*x000D*', $result);
        $this->assertStringNotContainsString('Ã¢â‚¬â„¢', $result);
        $this->assertStringContainsString("'test", $result);
        $this->assertStringContainsString('"Title"-with-dashes...', $result);
    }

    /**
     * Test UTF-8 preservation
     */
    public function testUTF8Preservation(): void
    {
        $text = 'Café München Москва 北京 مرحبا';
        $result = CharWash::sanitizeUnicode($text);

        // Should preserve valid UTF-8 characters
        $this->assertStringContainsString('Café', $result);
        $this->assertStringContainsString('München', $result);
        $this->assertStringContainsString('Москва', $result);
        $this->assertStringContainsString('北京', $result);
        $this->assertStringContainsString('مرحبا', $result);
    }

    /**
     * Test performance with large content
     */
    public function testPerformanceWithLargeContent(): void
    {
        // Generate large HTML content
        $content = str_repeat('<p>This is a test paragraph with some \u{201C}smart quotes\u{201D} and\u{2014}dashes.</p>', 1000);

        $startTime = microtime(true);
        $result = CharWash::sanitize($content);
        $elapsed = microtime(true) - $startTime;

        $this->assertNotEmpty($result);
        $this->assertLessThan(5, $elapsed, 'Processing should complete within 5 seconds');
    }
}