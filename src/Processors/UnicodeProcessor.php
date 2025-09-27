<?php

declare(strict_types=1);

namespace OdinDev\CharWash\Processors;

use Normalizer;

/**
 * UnicodeProcessor - Handles Unicode normalization and invisible character removal
 */
class UnicodeProcessor
{
    /**
     * Invisible Unicode characters that should be removed
     * Note: \x{00A0} (non-breaking space) removed from this list - it should be replaced, not removed
     */
    private const INVISIBLE_CHARS_REGEX =
    '/[' .
    '\x{2000}-\x{200D}' .
    '\x{202F}' .
    '\x{2060}' .
    '\x{3000}' .
    '\x{FEFF}' .
    '\x{200E}' .
    '\x{200F}' .
    '\x{202A}-\x{202E}' .
    ']+/u';

    /**
     * Control characters (except whitespace)
     */
    private const CONTROL_CHARS_REGEX = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F\x80-\x9F\x{200E}\x{200F}\x{202A}-\x{202E}]+/u';

    /**
     * Soft hyphens
     */
    private const SOFT_HYPHEN_REGEX = '/\x{00AD}/u';

    /**
     * Process text for Unicode normalization and cleanup
     *
     * @param string $text The text to process
     * @return string Processed text
     */
    public function process(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Apply NFC normalization for consistent byte representation
        $text = $this->normalizeToNFC($text);

        // Remove BOM (Byte Order Mark)
        $text = $this->removeBOM($text);

        // Replace non-breaking spaces with regular spaces
        $text = $this->replaceNonBreakingSpaces($text);

        // Replace line breaks with spaces
        $text = $this->replaceLineBreaks($text);

        // Remove invisible Unicode characters
        $text = $this->removeInvisibleCharacters($text);

        // Remove control characters
        $text = $this->removeControlCharacters($text);

        // Remove soft hyphens
        $text = $this->removeSoftHyphens($text);

        return $text;
    }

    /**
     * Normalize text to NFC (Canonical Decomposition, followed by Canonical Composition)
     *
     * @param string $text The text to normalize
     * @return string Normalized text
     */
    private function normalizeToNFC(string $text): string
    {
        if (class_exists(Normalizer::class)) {
            $normalized = Normalizer::normalize($text, Normalizer::FORM_C);
            if (is_string($normalized)) {
                return $normalized;
            }
        }
        return $text;
    }

    /**
     * Remove Byte Order Mark (BOM)
     *
     * @param string $text The text to process
     * @return string Text without BOM
     */
    private function removeBOM(string $text): string
    {
        // UTF-8 BOM
        if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
            $text = substr($text, 3);
        }

        // UTF-16 BE BOM
        if (substr($text, 0, 2) === "\xFE\xFF") {
            $text = substr($text, 2);
        }

        // UTF-16 LE BOM
        if (substr($text, 0, 2) === "\xFF\xFE") {
            $text = substr($text, 2);
        }

        // UTF-32 BE BOM
        if (substr($text, 0, 4) === "\x00\x00\xFE\xFF") {
            $text = substr($text, 4);
        }

        // UTF-32 LE BOM
        if (substr($text, 0, 4) === "\xFF\xFE\x00\x00") {
            $text = substr($text, 4);
        }

        // Also remove Unicode BOM character if present anywhere
        $text = str_replace("\u{FEFF}", '', $text);

        return $text;
    }

    /**
     * Remove invisible Unicode characters (ZWSP, ZWNJ, ZWJ, etc.)
     *
     * @param string $text The text to process
     * @return string Text without invisible characters
     */
    private function removeInvisibleCharacters(string $text): string
    {
        return preg_replace(self::INVISIBLE_CHARS_REGEX, '', $text) ?? $text;
    }

    /**
     * Remove control characters
     *
     * @param string $text The text to process
     * @return string Text without control characters
     */
    private function removeControlCharacters(string $text): string
    {
        return preg_replace(self::CONTROL_CHARS_REGEX, '', $text) ?? $text;
    }

    /**
     * Remove soft hyphens
     *
     * @param string $text The text to process
     * @return string Text without soft hyphens
     */
    private function removeSoftHyphens(string $text): string
    {
        return preg_replace(self::SOFT_HYPHEN_REGEX, '', $text) ?? $text;
    }

    /**
     * Replace non-breaking spaces with regular spaces
     *
     * @param string $text The text to process
     * @return string Text with non-breaking spaces replaced
     */
    private function replaceNonBreakingSpaces(string $text): string
    {
        // Replace various forms of non-breaking space with regular space
        $text = str_replace("\u{00A0}", ' ', $text); // Unicode non-breaking space
        $text = str_replace("\xC2\xA0", ' ', $text); // UTF-8 encoded NBSP
        $text = str_replace("\xA0", ' ', $text);     // Latin-1 NBSP
        $text = str_replace(chr(160), ' ', $text);    // ASCII 160

        return $text;
    }

    /**
     * Replace line breaks with spaces
     *
     * @param string $text The text to process
     * @return string Text with line breaks replaced by spaces
     */
    private function replaceLineBreaks(string $text): string
    {
        // Replace CRLF, LF, and CR with space
        $text = str_replace("\r\n", ' ', $text); // Windows line breaks (CRLF)
        $text = str_replace("\n", ' ', $text);   // Unix line breaks (LF)
        $text = str_replace("\r", ' ', $text);   // Mac line breaks (CR)

        return $text;
    }
}
