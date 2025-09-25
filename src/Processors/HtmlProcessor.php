<?php

declare(strict_types=1);

namespace OdinDev\CharWash\Processors;

use HTMLPurifier;
use HTMLPurifier_Config;
use OdinDev\CharWash\Config\CharWashConfig;

/**
 * HtmlProcessor - Handles HTML purification and security hardening
 */
class HtmlProcessor
{
    /**
     * HTMLPurifier instance
     */
    private HTMLPurifier $purifier;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->purifier = $this->buildPurifier();
    }

    /**
     * Process HTML content for purification and security
     *
     * @param string $text The HTML text to process
     * @return string Processed HTML
     */
    public function process(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Convert H1 to H2 for SEO discipline (before purification)
        $text = $this->convertH1ToH2($text);

        // Purify HTML
        $text = $this->purifier->purify($text);

        // Remove empty tags
        $text = $this->removeEmptyTags($text);

        // Ensure all external links have noopener noreferrer
        $text = $this->enforceSecureLinks($text);

        return $text;
    }

    /**
     * Build and configure HTMLPurifier instance
     *
     * @return HTMLPurifier Configured purifier instance
     */
    private function buildPurifier(): HTMLPurifier
    {
        $config = HTMLPurifier_Config::createDefault();

        // Basic configuration
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

        // Cache configuration
        if (CharWashConfig::hasCustomCachePath()) {
            $config->set('Cache.SerializerPath', CharWashConfig::getHtmlPurifierCachePath());
        }

        // Allowed schemes
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
            'tel' => true,
        ]);

        // Security: add rel="noopener noreferrer" to target="_blank"
        $config->set('HTML.TargetBlank', true);

        // Allow all classes for flexibility
        $config->set('Attr.AllowedClasses', null);

        // Get allowed tags from config or use defaults
        $allowedTags = CharWashConfig::getAllowedHtmlTags();
        if (!empty($allowedTags)) {
            $allowed = $this->buildAllowedTagsString($allowedTags);
        } else {
            // Default allowed tags
            $allowed = 'p,br,strong,b,em,i,u,kbd,code,sub,sup,s,span,' .
                       'h2[class],h3[class],h4[class],h5[class],h6[class],' .
                       'ul[class],ol[class],li,' .
                       'a[href|target|title|rel],' .
                       'img[src|alt|width|height],' .
                       'div[class],blockquote,pre,hr,' .
                       'table,thead,tbody,tfoot,tr,td,th,caption,colgroup,col';
        }
        $config->set('HTML.Allowed', $allowed);

        // Keep output compact
        $config->set('Output.TidyFormat', false);
        $config->set('HTML.TidyLevel', 'none');

        return new HTMLPurifier($config);
    }

    /**
     * Build allowed tags string from array
     *
     * @param array<string> $tags Array of allowed tags
     * @return string Formatted allowed tags string
     */
    private function buildAllowedTagsString(array $tags): string
    {
        $allowed = [];
        foreach ($tags as $tag) {
            // Handle tags with attributes
            if (in_array($tag, ['a', 'img', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'div'])) {
                switch ($tag) {
                    case 'a':
                        $allowed[] = 'a[href|target|title|rel]';
                        break;
                    case 'img':
                        $allowed[] = 'img[src|alt|width|height]';
                        break;
                    default:
                        $allowed[] = $tag . '[class]';
                        break;
                }
            } else {
                $allowed[] = $tag;
            }
        }
        return implode(',', $allowed);
    }

    /**
     * Convert H1 tags to H2 for SEO discipline
     *
     * @param string $html The HTML to process
     * @return string HTML with H1 converted to H2
     */
    private function convertH1ToH2(string $html): string
    {
        // Replace opening H1 tags
        $html = preg_replace('/<h1(\s+[^>]*)?>/i', '<h2$1>', $html);
        // Replace closing H1 tags
        $html = preg_replace('/<\/h1>/i', '</h2>', $html);

        return $html;
    }

    /**
     * Remove empty HTML tags
     *
     * @param string $html The HTML to process
     * @return string HTML without empty tags
     */
    private function removeEmptyTags(string $html): string
    {
        // Pattern to match empty tags (including those with only whitespace)
        $patterns = [
            '/<p[^>]*>[\s\&nbsp;]*<\/p>/i',
            '/<div[^>]*>[\s\&nbsp;]*<\/div>/i',
            '/<span[^>]*>[\s\&nbsp;]*<\/span>/i',
            '/<strong[^>]*>[\s\&nbsp;]*<\/strong>/i',
            '/<em[^>]*>[\s\&nbsp;]*<\/em>/i',
            '/<b[^>]*>[\s\&nbsp;]*<\/b>/i',
            '/<i[^>]*>[\s\&nbsp;]*<\/i>/i',
            '/<u[^>]*>[\s\&nbsp;]*<\/u>/i',
        ];

        foreach ($patterns as $pattern) {
            $html = preg_replace($pattern, '', $html);
        }

        // Remove multiple consecutive <br> tags (keep max 2)
        $html = preg_replace('/(<br\s*\/?>\s*){3,}/i', '<br><br>', $html);

        return $html;
    }

    /**
     * Ensure all external links have proper security attributes
     *
     * @param string $html The HTML to process
     * @return string HTML with secure links
     */
    private function enforceSecureLinks(string $html): string
    {
        // This is already handled by HTMLPurifier with HTML.TargetBlank setting
        // But we can add additional processing if needed

        // Ensure all external links open in new tab with security
        $html = preg_replace_callback(
            '/<a\s+([^>]*href=["\']https?:\/\/[^"\']+["\'][^>]*)>/i',
            function (array $matches): string {
                $tag = $matches[0];

                // Check if target="_blank" exists
                if (!preg_match('/target\s*=\s*["\']_blank["\']/i', $tag)) {
                    $tag = str_replace('<a ', '<a target="_blank" ', $tag);
                }

                // Check if rel contains noopener noreferrer
                if (!preg_match('/rel\s*=\s*["\']/i', $tag)) {
                    $tag = str_replace('<a ', '<a rel="noopener noreferrer" ', $tag);
                } elseif (!preg_match('/noopener/i', $tag) || !preg_match('/noreferrer/i', $tag)) {
                    // Add missing rel values
                    $tag = preg_replace_callback(
                        '/rel\s*=\s*["\']([^"\']*)["\']/',
                        function (array $relMatch): string {
                            $relValue = $relMatch[1];
                            if (!str_contains($relValue, 'noopener')) {
                                $relValue .= ' noopener';
                            }
                            if (!str_contains($relValue, 'noreferrer')) {
                                $relValue .= ' noreferrer';
                            }
                            return 'rel="' . trim($relValue) . '"';
                        },
                        $tag
                    ) ?? $tag;
                }

                return $tag;
            },
            $html
        );

        return $html;
    }
}
