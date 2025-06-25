<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Services\ContentCleaningService;

trait HasContentCleaning
{
    /**
     * Boot the trait and add model events
     */
    protected static function bootHasContentCleaning()
    {
        static::saving(function ($model) {
            $model->cleanRichEditorFields();
        });
    }

    /**
     * Clean RichEditor fields automatically
     */
    protected function cleanRichEditorFields(): void
    {
        $richEditorFields = $this->getRichEditorFields();
        
        foreach ($richEditorFields as $field) {
            if (isset($this->attributes[$field]) && !empty($this->attributes[$field])) {
                $this->attributes[$field] = ContentCleaningService::cleanRichEditorContent($this->attributes[$field]);
            }
        }
    }

    /**
     * Get the list of RichEditor fields to clean
     * Override this method in your model to specify which fields should be cleaned
     */
    protected function getRichEditorFields(): array
    {
        // Default implementation - override in your model
        return [];
    }

    /**
     * Clean a specific field manually
     */
    public function cleanField(string $fieldName): void
    {
        if (isset($this->attributes[$fieldName]) && !empty($this->attributes[$fieldName])) {
            $this->attributes[$fieldName] = ContentCleaningService::cleanRichEditorContent($this->attributes[$fieldName]);
        }
    }

    /**
     * Clean content with custom options
     */
    public function cleanFieldWithOptions(string $fieldName, array $options = []): void
    {
        if (isset($this->attributes[$fieldName]) && !empty($this->attributes[$fieldName])) {
            $this->attributes[$fieldName] = ContentCleaningService::cleanHtmlContent($this->attributes[$fieldName], $options);
        }
    }
} 