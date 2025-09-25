<?php
declare(strict_types=1);

namespace OdinDev\CharWash;

use OdinDev\CharWash\Processors\HtmlProcessor;
use OdinDev\CharWash\Processors\UnicodeProcessor;
use OdinDev\CharWash\Processors\OfficeProcessor;
use OdinDev\CharWash\Processors\PunctuationProcessor;

/**
 * CharWash - Comprehensive text sanitization package
 *
 * Provides robust Unicode normalization, HTML purification, and Office/email paste cleanup
 * for Laravel and Magento applications.
 */
class CharWash
{
    /**
     * Complete sanitization - runs all processors
     *
     * @param string $text The text to sanitize
     * @return string Sanitized text
     */
    public static function sanitize(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Process in optimal order
        $text = (new OfficeProcessor())->process($text);
        $text = (new UnicodeProcessor())->process($text);
        $text = (new PunctuationProcessor())->process($text);
        $text = (new HtmlProcessor())->process($text);

        return $text;
    }

    /**
     * HTML-specific sanitization
     * - Purifies HTML using HTMLPurifier
     * - Removes empty tags
     * - Enforces rel="noopener noreferrer"
     * - Converts H1 to H2
     *
     * @param string $text The HTML text to sanitize
     * @return string Sanitized HTML
     */
    public static function sanitizeHtml(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        return (new HtmlProcessor())->process($text);
    }

    /**
     * Unicode normalization and cleanup
     * - Applies NFC normalization
     * - Removes BOM, ZWSP, ZWNJ, ZWJ
     * - Strips soft hyphens
     *
     * @param string $text The text to normalize
     * @return string Normalized text
     */
    public static function sanitizeUnicode(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        return (new UnicodeProcessor())->process($text);
    }

    /**
     * Office/email paste cleanup
     * - Removes mso-* styles
     * - Strips conditional comments
     * - Removes *x000D* markers
     * - Fixes CP1252/mojibake issues
     *
     * @param string $text The text to clean
     * @return string Cleaned text
     */
    public static function sanitizeOffice(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        return (new OfficeProcessor())->process($text);
    }

    /**
     * Full sanitization with all processors
     * Alias for sanitize() for clarity
     *
     * @param string $text The text to sanitize
     * @return string Sanitized text
     */
    public static function sanitizeComplete(string $text): string
    {
        return self::sanitize($text);
    }

    /**
     * Punctuation normalization
     * - Flattens smart quotes to straight quotes
     * - Converts em/en dashes to standard dashes
     * - Replaces ellipsis characters with three dots
     * - Normalizes ligatures to standard characters
     *
     * @param string $text The text to normalize
     * @return string Normalized text
     */
    public static function sanitizePunctuation(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        return (new PunctuationProcessor())->process($text);
    }
}