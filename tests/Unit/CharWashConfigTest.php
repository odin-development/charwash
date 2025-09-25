<?php

declare(strict_types=1);

use OdinDev\CharWash\Config\CharWashConfig;

beforeEach(function () {
    CharWashConfig::reset();
});

describe('CharWashConfig', function () {
    it('sets and gets processor defaults', function () {
        // Test setting processor defaults
        CharWashConfig::setProcessorDefaults('unicode', [
            'removeInvisible' => false,
            'customOption' => true,
        ]);

        $defaults = CharWashConfig::getProcessorDefaults('unicode');

        expect($defaults['removeInvisible'])->toBeFalse();
        expect($defaults['customOption'])->toBeTrue();
    });

    it('returns empty array for unknown processor defaults', function () {
        $defaults = CharWashConfig::getProcessorDefaults('unknown');

        expect($defaults)->toBeArray();
        expect($defaults)->toBeEmpty();
    });

    it('loads configuration from array with processors', function () {
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

        expect(CharWashConfig::getHtmlPurifierCachePath())->toBe('/test/path');
        expect(CharWashConfig::getAllowedHtmlTags())->toBe(['div', 'span']);

        $htmlDefaults = CharWashConfig::getProcessorDefaults('html');
        expect($htmlDefaults['convertH1ToH2'])->toBeFalse();
        expect($htmlDefaults['removeEmptyTags'])->toBeFalse();

        $unicodeDefaults = CharWashConfig::getProcessorDefaults('unicode');
        expect($unicodeDefaults['removeInvisible'])->toBeFalse();
    });

    it('exports full configuration to array', function () {
        CharWashConfig::setHtmlPurifierCachePath('/test/cache');
        CharWashConfig::setAllowedHtmlTags(['p', 'div']);
        CharWashConfig::setProcessorDefaults('punctuation', [
            'flattenSmartQuotes' => false,
        ]);

        $config = CharWashConfig::toArray();

        expect($config['cache_path'])->toBe('/test/cache');
        expect($config['allowed_tags'])->toBe(['p', 'div']);
        expect($config)->toHaveKey('processors');
        expect($config['processors'])->toHaveKey('punctuation');
        expect($config['processors']['punctuation']['flattenSmartQuotes'])->toBeFalse();
    });

    it('resets all configuration', function () {
        CharWashConfig::setHtmlPurifierCachePath('/test/path');
        CharWashConfig::setAllowedHtmlTags(['div']);
        CharWashConfig::setProcessorDefaults('html', ['custom' => true]);

        CharWashConfig::reset();

        expect(CharWashConfig::getHtmlPurifierCachePath())->toBeNull();
        expect(CharWashConfig::getAllowedHtmlTags())->toBeEmpty();

        // After reset, should have default values
        $htmlDefaults = CharWashConfig::getProcessorDefaults('html');
        expect($htmlDefaults['convertH1ToH2'])->toBeTrue();
    });
});