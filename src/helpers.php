<?php

use Backstage\Fields\Services\ContentCleaningService;

if (!function_exists('clean_rich_editor_content')) {
    /**
     * Clean RichEditor content by removing figcaption and unwrapping images from links
     */
    function clean_rich_editor_content(?string $content): ?string
    {
        return ContentCleaningService::cleanRichEditorContent($content);
    }
}

if (!function_exists('clean_html_content')) {
    /**
     * Clean HTML content with custom options
     */
    function clean_html_content(?string $content, array $options = []): ?string
    {
        return ContentCleaningService::cleanHtmlContent($content, $options);
    }
} 