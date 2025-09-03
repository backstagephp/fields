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
     * Clean content - handles both string (HTML) and array (Filament v4 RichEditor) formats
     */
    public static function cleanContent($content, array $options = []): mixed
    {
        if (empty($content)) {
            return $content;
        }

        // Handle array format (Filament v4 RichEditor)
        if (is_array($content)) {
            return self::cleanRichEditorArray($content, $options);
        }

        // Handle string format (legacy HTML)
        if (is_string($content)) {
            return self::cleanHtmlContent($content, $options);
        }

        return $content;
    }

    /**
     * Clean RichEditor array content by removing figcaption and unwrapping images
     */
    public static function cleanRichEditorArray(array $content, array $options = []): array
    {
        $defaultOptions = [
            'removeFigcaption' => true,
            'unwrapImages' => true,
            'removeEmptyFigures' => true,
            'preserveCustomCaptions' => false,
        ];

        $options = array_merge($defaultOptions, $options);

        // Recursively clean the content array
        return self::cleanArrayRecursively($content, $options);
    }

    /**
     * Recursively clean array content
     */
    private static function cleanArrayRecursively(array $node, array $options): array
    {
        // Clean the current node
        $node = self::cleanNode($node, $options);

        // Recursively clean child content
        if (isset($node['content']) && is_array($node['content'])) {
            $cleanedContent = [];
            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    $cleanedChild = self::cleanArrayRecursively($child, $options);
                    // Only add non-empty children
                    if (! empty($cleanedChild) && $cleanedChild !== self::getEmptyNode()) {
                        $cleanedContent[] = $cleanedChild;
                    }
                } else {
                    $cleanedContent[] = $child;
                }
            }
            $node['content'] = $cleanedContent;
        }

        return $node;
    }

    /**
     * Clean a single node
     */
    private static function cleanNode(array $node, array $options): array
    {
        $type = $node['type'] ?? '';

        // Remove figcaption nodes
        if ($options['removeFigcaption'] && $type === 'figcaption') {
            return self::getEmptyNode();
        }

        // Handle image nodes - unwrap from links if needed
        if ($type === 'image' && $options['unwrapImages']) {
            // Remove any link wrapping by ensuring the image is not inside a link
            // This is handled at the parent level, so we just return the image as-is
            return $node;
        }

        // Handle figure nodes
        if ($type === 'figure' && $options['removeEmptyFigures']) {
            // If figure only contains an image, unwrap it
            if (isset($node['content']) && is_array($node['content']) && count($node['content']) === 1) {
                $child = $node['content'][0];
                if (is_array($child) && ($child['type'] ?? '') === 'image') {
                    return $child;
                }
            }
        }

        return $node;
    }

    /**
     * Get an empty node placeholder
     */
    private static function getEmptyNode(): array
    {
        return ['type' => 'empty'];
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
            // Handle complex nested structures where img is inside a link inside a figure
            // This pattern matches: <figure...><a...><img...></a></figure> and extracts just the img
            $content = preg_replace('/<figure[^>]*>\s*<a[^>]*>\s*(<img[^>]*>)\s*.*?<\/a>\s*<\/figure>/is', '$1', $content);

            // Handle cases where img is wrapped in a link but not in a figure
            $content = preg_replace('/<a[^>]*>\s*(<img[^>]*>)\s*.*?<\/a>/is', '$1', $content);

            // Handle cases where there might be other content in the link besides the img
            $content = preg_replace('/<a[^>]*>.*?(<img[^>]*>).*?<\/a>/is', '$1', $content);
        }

        if ($options['removeEmptyFigures']) {
            // Clean up any empty figure tags that might be left
            $content = preg_replace('/<figure[^>]*>\s*<\/figure>/is', '', $content);

            // Clean up any figure tags that only contain img
            $content = preg_replace('/<figure[^>]*>\s*(<img[^>]*>)\s*<\/figure>/is', '$1', $content);
        }

        // Clean up any extra whitespace that might be left
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        return $content;
    }
}
