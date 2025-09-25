<?php
declare(strict_types=1);

namespace OdinDev\CharWash\Processors;

/**
 * PunctuationProcessor - Handles normalization of typography and punctuation
 */
class PunctuationProcessor
{
    /**
     * Smart single quotes regex
     */
    private const SMART_SINGLE_QUOTES_REGEX = '/[\x{2018}\x{2019}\x{201A}\x{201B}\x{2032}\x{2035}]/u';

    /**
     * Smart double quotes regex
     */
    private const SMART_DOUBLE_QUOTES_REGEX = '/[\x{201C}\x{201D}\x{201E}\x{201F}\x{2033}\x{2036}\x{00AB}\x{00BB}]/u';

    /**
     * Unicode dashes regex
     */
    private const UNICODE_DASHES_REGEX = '/[\x{2012}\x{2013}\x{2014}\x{2015}\x{2212}]/u';

    /**
     * Ellipsis character regex
     */
    private const ELLIPSIS_REGEX = '/\x{2026}/u';

    /**
     * Bullet points regex
     */
    private const BULLETS_REGEX = '/[\x{2022}\x{00B7}]/u';

    /**
     * Full-width ASCII punctuation regex
     */
    private const FULLWIDTH_PUNCTUATION_REGEX = '/[\x{FF01}-\x{FF5E}]/u';

    /**
     * Common ligatures map
     */
    private const LIGATURES_MAP = [
        "\u{00C6}" => 'AE',  // Æ
        "\u{00E6}" => 'ae',  // æ
        "\u{0152}" => 'OE',  // Œ
        "\u{0153}" => 'oe',  // œ
        "\u{FB00}" => 'ff',  // ﬀ
        "\u{FB01}" => 'fi',  // ﬁ
        "\u{FB02}" => 'fl',  // ﬂ
        "\u{FB03}" => 'ffi', // ﬃ
        "\u{FB04}" => 'ffl', // ﬄ
        "\u{FB05}" => 'st',  // ﬅ
        "\u{FB06}" => 'st',  // ﬆ
    ];

    /**
     * Additional typography map
     */
    private const TYPOGRAPHY_MAP = [
        "\u{2039}" => "'",   // ‹ single left-pointing angle quote
        "\u{203A}" => "'",   // › single right-pointing angle quote
        "\u{00A9}" => '(c)', // © copyright
        "\u{00AE}" => '(R)', // ® registered
        "\u{2122}" => '(TM)', // ™ trademark
        "\u{00B0}" => 'deg', // ° degree
        "\u{00B9}" => '1',   // ¹ superscript 1
        "\u{00B2}" => '2',   // ² superscript 2
        "\u{00B3}" => '3',   // ³ superscript 3
        "\u{00BC}" => '1/4', // ¼
        "\u{00BD}" => '1/2', // ½
        "\u{00BE}" => '3/4', // ¾
        "\u{2153}" => '1/3', // ⅓
        "\u{2154}" => '2/3', // ⅔
        "\u{215B}" => '1/8', // ⅛
        "\u{215C}" => '3/8', // ⅜
        "\u{215D}" => '5/8', // ⅝
        "\u{215E}" => '7/8', // ⅞
    ];

    /**
     * Process text to normalize typography and punctuation
     *
     * @param string $text The text to process
     * @return string Processed text
     */
    public function process(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Flatten smart quotes to straight quotes
        $text = $this->flattenSmartQuotes($text);

        // Convert Unicode dashes to standard hyphens
        $text = $this->normalizeDashes($text);

        // Replace ellipsis character with three dots
        $text = $this->normalizeEllipsis($text);

        // Convert bullet points to asterisks
        $text = $this->normalizeBullets($text);

        // Normalize ligatures
        $text = $this->normalizeLigatures($text);

        // Normalize full-width ASCII
        $text = $this->normalizeFullWidthASCII($text);

        // Apply additional typography normalizations
        $text = $this->normalizeAdditionalTypography($text);

        return $text;
    }

    /**
     * Flatten smart quotes to straight quotes
     *
     * @param string $text The text to process
     * @return string Text with straight quotes
     */
    private function flattenSmartQuotes(string $text): string
    {
        // Single quotes
        $text = preg_replace(self::SMART_SINGLE_QUOTES_REGEX, "'", $text);

        // Double quotes
        $text = preg_replace(self::SMART_DOUBLE_QUOTES_REGEX, '"', $text);

        return $text;
    }

    /**
     * Normalize various Unicode dashes to standard hyphen
     *
     * @param string $text The text to process
     * @return string Text with normalized dashes
     */
    private function normalizeDashes(string $text): string
    {
        return preg_replace(self::UNICODE_DASHES_REGEX, '-', $text);
    }

    /**
     * Replace ellipsis character with three dots
     *
     * @param string $text The text to process
     * @return string Text with normalized ellipsis
     */
    private function normalizeEllipsis(string $text): string
    {
        return preg_replace(self::ELLIPSIS_REGEX, '...', $text);
    }

    /**
     * Convert bullet points to asterisks
     *
     * @param string $text The text to process
     * @return string Text with normalized bullets
     */
    private function normalizeBullets(string $text): string
    {
        return preg_replace(self::BULLETS_REGEX, '*', $text);
    }

    /**
     * Normalize ligatures to standard character combinations
     *
     * @param string $text The text to process
     * @return string Text with normalized ligatures
     */
    private function normalizeLigatures(string $text): string
    {
        return strtr($text, self::LIGATURES_MAP);
    }

    /**
     * Convert full-width ASCII characters to half-width
     *
     * @param string $text The text to process
     * @return string Text with normalized full-width characters
     */
    private function normalizeFullWidthASCII(string $text): string
    {
        return preg_replace_callback(
            self::FULLWIDTH_PUNCTUATION_REGEX,
            function ($matches) {
                // Convert full-width to half-width by subtracting 0xFEE0
                $char = $matches[0];
                $code = mb_ord($char, 'UTF-8');
                if ($code >= 0xFF01 && $code <= 0xFF5E) {
                    return chr($code - 0xFEE0);
                }
                return $char;
            },
            $text
        );
    }

    /**
     * Apply additional typography normalizations
     *
     * @param string $text The text to process
     * @return string Text with normalized typography
     */
    private function normalizeAdditionalTypography(string $text): string
    {
        return strtr($text, self::TYPOGRAPHY_MAP);
    }
}