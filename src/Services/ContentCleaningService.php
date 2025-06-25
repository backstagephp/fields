<?php

namespace Backstage\Fields\Services;

class ContentCleaningService
{
    /**
     * Clean HTML content by removing figcaption elements and unwrapping img tags from anchor links
     */
    public static function cleanRichEditorContent(?string $content): ?string
    {
        if (empty($content)) {
            return $content;
        }

        // Remove figcaption elements completely
        $content = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/is', '', $content);
        
        // Unwrap img tags from anchor links, keeping only the img tag
        $content = preg_replace('/<a[^>]*>(<img[^>]*>).*?<\/a>/is', '$1', $content);
        
        // Clean up any empty figure tags that might be left
        $content = preg_replace('/<figure[^>]*>\s*<\/figure>/is', '', $content);
        
        // Clean up any empty figure tags that only contain img
        $content = preg_replace('/<figure[^>]*>(<img[^>]*>)<\/figure>/is', '$1', $content);

        return $content;
    }

    /**
     * Clean HTML content with more specific options
     */
    public static function cleanHtmlContent(?string $content, array $options = []): ?string
    {
        if (empty($content)) {
            return $content;
        }

        $defaultOptions = [
            'removeFigcaption' => true,
            'unwrapImages' => true,
            'removeEmptyFigures' => true,
            'preserveCustomCaptions' => false, // If true, only remove default captions
        ];

        $options = array_merge($defaultOptions, $options);

        if ($options['removeFigcaption']) {
            if ($options['preserveCustomCaptions']) {
                // Only remove figcaption if it contains default content (filename and size)
                $content = preg_replace('/<figcaption[^>]*>\s*<span[^>]*class="[^"]*attachment__name[^"]*"[^>]*>.*?<\/span>\s*<span[^>]*class="[^"]*attachment__size[^"]*"[^>]*>.*?<\/span>\s*<\/figcaption>/is', '', $content);
            } else {
                // Remove all figcaption elements
                $content = preg_replace('/<figcaption[^>]*>.*?<\/figcaption>/is', '', $content);
            }
        }

        if ($options['unwrapImages']) {
            // Unwrap img tags from anchor links, keeping only the img tag
            $content = preg_replace('/<a[^>]*>(<img[^>]*>).*?<\/a>/is', '$1', $content);
        }

        if ($options['removeEmptyFigures']) {
            // Clean up any empty figure tags that might be left
            $content = preg_replace('/<figure[^>]*>\s*<\/figure>/is', '', $content);
            
            // Clean up any figure tags that only contain img
            $content = preg_replace('/<figure[^>]*>(<img[^>]*>)<\/figure>/is', '$1', $content);
        }

        return $content;
    }
} 