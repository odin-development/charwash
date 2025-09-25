<?php

declare(strict_types=1);

namespace OdinDev\CharWash\Processors;

/**
 * OfficeProcessor - Handles cleanup of Microsoft Office and email paste artifacts
 */
class OfficeProcessor
{
    /**
     * Common CP1252/UTF-8 mojibake patterns
     */
    private const MOJIBAKE_MAP = [
        'Ã¢â‚¬â„¢' => "'",  // '
        'Ã¢â‚¬Å"' => '"',  // "
        'Ã¢â‚¬Â' => '"',   // " (left quote)
        'Ã¢â‚¬ï¿½' => '"',  // " (right quote variant)
        'Ã¢â‚¬â€œ' => '-', // –
        'Ã¢â‚¬â' => '-',   // —
        'Ã¢â‚¬â€' => '-',  // — (variant)
        'Ã¢â‚¬Â¦' => '...', // …
        'â€™' => "'",
        'â€˜' => "'",
        'â€œ' => '"',
        'â€' => '"',
        'â€"' => '--',     // em/en dash
        'â€¦' => '...',
        'Â ' => ' ',        // NBSP
        'Ã‚Â ' => ' ',      // Double-encoded NBSP
        'Ã‚' => '',         // Common artifact
        'Â' => '',          // Standalone artifact
    ];

    /**
     * Process text to remove Office/email artifacts
     *
     * @param string $text The text to process
     * @return string Processed text
     */
    public function process(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Fix encoding issues first
        $text = $this->fixEncoding($text);

        // Remove Word-specific artifacts
        $text = $this->removeWordArtifacts($text);

        // Remove Outlook/email artifacts
        $text = $this->removeEmailArtifacts($text);

        // Clean up MSO styles
        $text = $this->removeMsoStyles($text);

        // Remove conditional comments
        $text = $this->removeConditionalComments($text);

        // Fix mojibake issues
        $text = $this->fixMojibake($text);

        // Remove *x000D* markers
        $text = $this->removeHexMarkers($text);

        return $text;
    }

    /**
     * Fix common encoding issues
     *
     * @param string $text The text to fix
     * @return string Fixed text
     */
    private function fixEncoding(string $text): string
    {
        // If not valid UTF-8, try to fix it
        if (!mb_check_encoding($text, 'UTF-8')) {
            // Try to convert from Windows-1252 to UTF-8
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            } else {
                // Fallback: remove invalid UTF-8 sequences
                $text = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: $text;
            }
        }

        return $text;
    }

    /**
     * Remove Word-specific artifacts
     *
     * @param string $text The text to process
     * @return string Cleaned text
     */
    private function removeWordArtifacts(string $text): string
    {
        // Remove Word paragraph markers
        $text = str_replace(['¶', '§'], '', $text);

        // Remove Word's non-breaking spaces
        $text = str_replace(["\xC2\xA0", "\xA0", chr(160)], ' ', $text);

        // Remove Word's special quotes (if they somehow survived encoding)
        $text = str_replace(["\u{201C}", "\u{201D}", "\u{2018}", "\u{2019}"], ['"', '"', "'", "'"], $text);

        // Remove Word's special dashes
        $text = str_replace(["\u{2013}", "\u{2014}"], '-', $text);

        // Remove Word list markers
        $text = preg_replace('/^[\s]*[\x{00B7}\x{2022}\x{25E6}\x{25AA}\x{25AB}\x{25D8}\x{25CB}\x{25CF}]\s*/mu', '', $text);

        // Remove Word's soft line breaks
        $text = str_replace("\x0B", "\n", $text);

        return $text;
    }

    /**
     * Remove Outlook/email-specific artifacts
     *
     * @param string $text The text to process
     * @return string Cleaned text
     */
    private function removeEmailArtifacts(string $text): string
    {
        // Remove Outlook's line prefixes
        $text = preg_replace('/^[>|\s]+/m', '', $text);

        // Remove email quote markers
        $text = preg_replace('/^-{2,}\s*Original Message\s*-{2,}.*$/mi', '', $text);
        $text = preg_replace('/^From:\s*.*$/mi', '', $text);
        $text = preg_replace('/^Sent:\s*.*$/mi', '', $text);
        $text = preg_replace('/^To:\s*.*$/mi', '', $text);
        $text = preg_replace('/^Subject:\s*.*$/mi', '', $text);

        // Remove email signature separators
        $text = preg_replace('/^--\s*$/m', '', $text);
        $text = preg_replace('/^_{3,}$/m', '', $text);

        return $text;
    }

    /**
     * Remove MSO (Microsoft Office) styles
     *
     * @param string $text The text to process
     * @return string Text without MSO styles
     */
    private function removeMsoStyles(string $text): string
    {
        // Remove mso- CSS properties
        $text = preg_replace('/mso-[^:;}"\']+:[^;}"\']+;?/i', '', $text);

        // Remove style attributes that only contain mso properties or are empty
        $text = preg_replace('/\s*style\s*=\s*["\']["\']/', '', $text);
        $text = preg_replace('/\s*style\s*=\s*["\']\s*["\']/', '', $text);

        // Remove MSO classes
        $text = preg_replace('/\s*class\s*=\s*["\']Mso[^"\']*["\']/', '', $text);

        // Remove v:* tags (Vector Markup Language)
        $text = preg_replace('/<v:[^>]+>/i', '', $text);
        $text = preg_replace('/<\/v:[^>]+>/i', '', $text);

        // Remove o:* tags (Office namespace)
        $text = preg_replace('/<o:[^>]+>/i', '', $text);
        $text = preg_replace('/<\/o:[^>]+>/i', '', $text);

        // Remove w:* tags (Word namespace)
        $text = preg_replace('/<w:[^>]+>/i', '', $text);
        $text = preg_replace('/<\/w:[^>]+>/i', '', $text);

        return $text;
    }

    /**
     * Remove conditional comments (IE/Word specific)
     *
     * @param string $text The text to process
     * @return string Text without conditional comments
     */
    private function removeConditionalComments(string $text): string
    {
        // Remove IE conditional comments
        $text = preg_replace('/<!--\[if[^\]]*\]>.*?<!\[endif\]-->/is', '', $text);

        // Remove Word conditional comments
        $text = preg_replace('/<!--\[if\s+mso[^\]]*\]>.*?<!\[endif\]-->/is', '', $text);

        // Remove other conditional comments
        $text = preg_replace('/<!--\[if[^\]]*\]><!-->/is', '', $text);
        $text = preg_replace('/<!--<!\[endif\]-->/is', '', $text);

        return $text;
    }

    /**
     * Fix mojibake (character encoding issues)
     *
     * @param string $text The text to fix
     * @return string Fixed text
     */
    private function fixMojibake(string $text): string
    {
        // Apply the mojibake map
        $text = strtr($text, self::MOJIBAKE_MAP);

        // Fix double-encoded UTF-8
        // This pattern catches UTF-8 bytes that have been incorrectly re-encoded
        $text = preg_replace_callback(
            '/[\xC2-\xDF][\x80-\xBF]/',
            function (array $matches): string {
                // Use mb_convert_encoding instead of deprecated utf8_decode
                $decoded = mb_convert_encoding($matches[0], 'ISO-8859-1', 'UTF-8');
                if (mb_check_encoding($decoded, 'UTF-8')) {
                    return $decoded;
                }
                return $matches[0];
            },
            $text
        ) ?? $text;

        return $text;
    }

    /**
     * Remove hexadecimal markers like *x000D*
     *
     * @param string $text The text to process
     * @return string Text without hex markers
     */
    private function removeHexMarkers(string $text): string
    {
        // Remove *x000D* style markers (carriage return)
        $text = preg_replace('/\*x000[dD]\*/i', '', $text);

        // Remove other common hex markers
        $text = preg_replace('/\*x[0-9a-fA-F]{4}\*/i', '', $text);

        // Remove _x000D_ style markers
        $text = preg_replace('/_x000[dD]_/i', '', $text);
        $text = preg_replace('/_x[0-9a-fA-F]{4}_/i', '', $text);

        // Remove &#x000D; style HTML entities
        $text = preg_replace('/&#x000[dD];/i', '', $text);
        $text = preg_replace('/&#x[0-9a-fA-F]{4};/i', '', $text);

        return $text;
    }
}
