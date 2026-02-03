<?php

namespace App\Helpers;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    /**
     * Sanitize HTML content to prevent XSS attacks.
     *
     * @param  string|null  $html  The HTML content to sanitize
     * @param  string  $profile  The sanitization profile to use (default, strict, minimal)
     * @return string The sanitized HTML
     */
    public static function sanitize(?string $html, string $profile = 'default'): string
    {
        if (empty($html)) {
            return '';
        }

        $config = HTMLPurifier_Config::createDefault();

        // Configure based on profile
        switch ($profile) {
            case 'strict':
                // Very restrictive - only basic formatting
                $config->set('HTML.Allowed', 'p,br,strong,em,u');
                break;

            case 'minimal':
                // Minimal formatting with links
                $config->set('HTML.Allowed', 'p,br,strong,em,u,a[href|title|target],ul,ol,li');
                $config->set('HTML.TargetBlank', true);
                $config->set('Attr.AllowedFrameTargets', ['_blank']);
                break;

            case 'rich':
                // Rich content - for trusted admin content
                $config->set('HTML.Allowed', 'p,br,strong,em,u,i,b,a[href|title|target],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,code,pre,img[src|alt|width|height],table,thead,tbody,tr,th,td,div[class],span[class]');
                $config->set('HTML.TargetBlank', true);
                $config->set('Attr.AllowedFrameTargets', ['_blank']);
                break;

            case 'default':
            default:
                // Default - balanced security and functionality
                $config->set('HTML.Allowed', 'p,br,strong,em,u,i,b,a[href|title|target],ul,ol,li,h1,h2,h3,h4,h5,h6,blockquote,code,pre');
                $config->set('HTML.TargetBlank', true);
                $config->set('Attr.AllowedFrameTargets', ['_blank']);
                break;
        }

        // Additional security settings
        $config->set('HTML.Nofollow', true); // Add rel="nofollow" to links
        $config->set('URI.DisableExternalResources', true); // Prevent loading external resources
        $config->set('URI.DisableResources', false); // Allow internal resources
        $config->set('AutoFormat.RemoveEmpty', true); // Remove empty tags
        $config->set('AutoFormat.AutoParagraph', false); // Don't auto-wrap in paragraphs

        // Cache directory for better performance
        $cacheDir = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $config->set('Cache.SerializerPath', $cacheDir);

        $purifier = new HTMLPurifier($config);

        return $purifier->purify($html);
    }

    /**
     * Sanitize HTML for display in admin areas (more permissive).
     *
     * @param  string|null  $html
     * @return string
     */
    public static function sanitizeAdmin(?string $html): string
    {
        return self::sanitize($html, 'rich');
    }

    /**
     * Sanitize HTML for client-facing content (more restrictive).
     *
     * @param  string|null  $html
     * @return string
     */
    public static function sanitizeClient(?string $html): string
    {
        return self::sanitize($html, 'default');
    }

    /**
     * Sanitize AI-generated content (balanced approach).
     *
     * @param  string|null  $html
     * @return string
     */
    public static function sanitizeAI(?string $html): string
    {
        return self::sanitize($html, 'default');
    }

    /**
     * Strip all HTML tags (for plain text).
     *
     * @param  string|null  $html
     * @return string
     */
    public static function stripAll(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        return strip_tags($html);
    }
}
