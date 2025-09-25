<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Tests;

use OdinDev\CharWash\Config\CharWashConfig;
use PHPUnit\Framework\TestCase;

class CharWashConfigTest extends TestCase
{
    protected function setUp(): void
    {
        CharWashConfig::reset();
    }

    public function testSetAndGetProcessorDefaults(): void
    {
        // Test setting processor defaults
        CharWashConfig::setProcessorDefaults('unicode', [
            'removeInvisible' => false,
            'customOption' => true,
        ]);

        $defaults = CharWashConfig::getProcessorDefaults('unicode');

        $this->assertFalse($defaults['removeInvisible']);
        $this->assertTrue($defaults['customOption']);
    }

    public function testGetProcessorDefaultsForUnknownProcessor(): void
    {
        $defaults = CharWashConfig::getProcessorDefaults('unknown');

        $this->assertIsArray($defaults);
        $this->assertEmpty($defaults);
    }

    public function testLoadFromArrayWithProcessors(): void
    {
        CharWashConfig::loadFromArray([
            'cache_path' => '/test/path',
            'allowed_tags' => ['div', 'span'],
            'processors' => [
                'html' => [
                    'convertH1ToH2' => false,
                    'removeEmptyTags' => false,
                ],
                'unicode' => [
                    'removeInvisible' => false,
                ],
            ],
        ]);

        $this->assertEquals('/test/path', CharWashConfig::getHtmlPurifierCachePath());
        $this->assertEquals(['div', 'span'], CharWashConfig::getAllowedHtmlTags());

        $htmlDefaults = CharWashConfig::getProcessorDefaults('html');
        $this->assertFalse($htmlDefaults['convertH1ToH2']);
        $this->assertFalse($htmlDefaults['removeEmptyTags']);

        $unicodeDefaults = CharWashConfig::getProcessorDefaults('unicode');
        $this->assertFalse($unicodeDefaults['removeInvisible']);
    }

    public function testToArrayExportsFullConfiguration(): void
    {
        CharWashConfig::setHtmlPurifierCachePath('/test/cache');
        CharWashConfig::setAllowedHtmlTags(['p', 'div']);
        CharWashConfig::setProcessorDefaults('punctuation', [
            'flattenSmartQuotes' => false,
        ]);

        $config = CharWashConfig::toArray();

        $this->assertEquals('/test/cache', $config['cache_path']);
        $this->assertEquals(['p', 'div'], $config['allowed_tags']);
        $this->assertArrayHasKey('processors', $config);
        $this->assertArrayHasKey('punctuation', $config['processors']);
        $this->assertFalse($config['processors']['punctuation']['flattenSmartQuotes']);
    }

    public function testResetClearsAllConfiguration(): void
    {
        CharWashConfig::setHtmlPurifierCachePath('/test/path');
        CharWashConfig::setAllowedHtmlTags(['div']);
        CharWashConfig::setProcessorDefaults('html', ['custom' => true]);

        CharWashConfig::reset();

        $this->assertNull(CharWashConfig::getHtmlPurifierCachePath());
        $this->assertEmpty(CharWashConfig::getAllowedHtmlTags());

        // After reset, should have default values
        $htmlDefaults = CharWashConfig::getProcessorDefaults('html');
        $this->assertTrue($htmlDefaults['convertH1ToH2']);
    }
}