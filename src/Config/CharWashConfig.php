<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Config;

/**
 * CharWashConfig - Configuration management for CharWash package
 */
class CharWashConfig
{
    /**
     * HTMLPurifier cache path
     */
    private static ?string $htmlPurifierCachePath = null;

    /**
     * Allowed HTML tags
     */
    private static array $allowedHtmlTags = [];

    /**
     * Default processor options
     */
    private static array $defaultOptions = [
        'unicode' => [
            'removeInvisible' => true,
            'removeControl' => true,
            'removeSoftHyphens' => true,
            'normalizeNFC' => true,
        ],
        'html' => [
            'convertH1ToH2' => true,
            'removeEmptyTags' => true,
            'enforceSecureLinks' => true,
        ],
        'office' => [
            'removeMsoStyles' => true,
            'removeConditionalComments' => true,
            'fixMojibake' => true,
            'removeHexMarkers' => true,
        ],
        'punctuation' => [
            'flattenSmartQuotes' => true,
            'normalizeDashes' => true,
            'normalizeEllipsis' => true,
            'normalizeBullets' => true,
            'normalizeLigatures' => true,
        ],
    ];

    /**
     * Set HTMLPurifier cache path
     *
     * @param string $path The cache path
     */
    public static function setHtmlPurifierCachePath(string $path): void
    {
        self::$htmlPurifierCachePath = $path;
    }

    /**
     * Get HTMLPurifier cache path
     *
     * @return string|null The cache path
     */
    public static function getHtmlPurifierCachePath(): ?string
    {
        return self::$htmlPurifierCachePath;
    }

    /**
     * Check if custom cache path is set
     *
     * @return bool True if custom cache path is set
     */
    public static function hasCustomCachePath(): bool
    {
        return self::$htmlPurifierCachePath !== null;
    }

    /**
     * Set allowed HTML tags
     *
     * @param array $tags Array of allowed tag names
     */
    public static function setAllowedHtmlTags(array $tags): void
    {
        self::$allowedHtmlTags = $tags;
    }

    /**
     * Get allowed HTML tags
     *
     * @return array Array of allowed tag names
     */
    public static function getAllowedHtmlTags(): array
    {
        return self::$allowedHtmlTags;
    }

    /**
     * Set default options for a processor
     *
     * @param string $processor The processor name (unicode, html, office, punctuation)
     * @param array $options The default options
     */
    public static function setProcessorDefaults(string $processor, array $options): void
    {
        if (isset(self::$defaultOptions[$processor])) {
            self::$defaultOptions[$processor] = array_merge(
                self::$defaultOptions[$processor],
                $options
            );
        }
    }

    /**
     * Get default options for a processor
     *
     * @param string $processor The processor name
     * @return array The default options
     */
    public static function getProcessorDefaults(string $processor): array
    {
        return self::$defaultOptions[$processor] ?? [];
    }

    /**
     * Reset all configuration to defaults
     */
    public static function reset(): void
    {
        self::$htmlPurifierCachePath = null;
        self::$allowedHtmlTags = [];
        self::$defaultOptions = [
            'unicode' => [
                'removeInvisible' => true,
                'removeControl' => true,
                'removeSoftHyphens' => true,
                'normalizeNFC' => true,
            ],
            'html' => [
                'convertH1ToH2' => true,
                'removeEmptyTags' => true,
                'enforceSecureLinks' => true,
            ],
            'office' => [
                'removeMsoStyles' => true,
                'removeConditionalComments' => true,
                'fixMojibake' => true,
                'removeHexMarkers' => true,
            ],
            'punctuation' => [
                'flattenSmartQuotes' => true,
                'normalizeDashes' => true,
                'normalizeEllipsis' => true,
                'normalizeBullets' => true,
                'normalizeLigatures' => true,
            ],
        ];
    }

    /**
     * Load configuration from array (useful for Laravel/Magento config files)
     *
     * @param array $config Configuration array
     */
    public static function loadFromArray(array $config): void
    {
        if (isset($config['cache_path'])) {
            self::setHtmlPurifierCachePath($config['cache_path']);
        }

        if (isset($config['allowed_tags'])) {
            self::setAllowedHtmlTags($config['allowed_tags']);
        }

        if (isset($config['processors'])) {
            foreach ($config['processors'] as $processor => $options) {
                self::setProcessorDefaults($processor, $options);
            }
        }
    }

    /**
     * Export current configuration as array
     *
     * @return array Current configuration
     */
    public static function toArray(): array
    {
        return [
            'cache_path' => self::$htmlPurifierCachePath,
            'allowed_tags' => self::$allowedHtmlTags,
            'processors' => self::$defaultOptions,
        ];
    }
}