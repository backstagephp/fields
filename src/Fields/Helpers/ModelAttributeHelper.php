<?php

namespace Backstage\Fields\Fields\Helpers;

use Backstage\Fields\Models\Field;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ModelAttributeHelper
{
    /**
     * Get all available models for visibility rules.
     */
    public static function getAvailableModels(): array
    {
        // Try different config paths
        $models = Config::get('fields.visibility_models', []);

        // If empty, try the backstage config path
        if (empty($models)) {
            $models = Config::get('backstage.fields.visibility_models', []);
        }

        $modelOptions = [];

        foreach ($models as $modelClass) {
            if (class_exists($modelClass)) {
                $modelName = class_basename($modelClass);
                $modelOptions[$modelClass] = $modelName;
            }
        }

        return $modelOptions;
    }

    /**
     * Get model attributes for a given livewire context.
     */
    public static function getModelAttributes(mixed $livewire): array
    {
        if (! $livewire || ! method_exists($livewire, 'getOwnerRecord')) {
            return [];
        }

        $ownerRecord = $livewire->getOwnerRecord();

        if (! $ownerRecord) {
            return [];
        }

        $modelClass = get_class($ownerRecord);
        $tableName = $ownerRecord->getTable();

        return self::getModelAttributesFromTable($tableName, $modelClass);
    }

    /**
     * Get model attributes from a specific table and model class.
     */
    public static function getModelAttributesFromTable(string $tableName, string $modelClass): array
    {
        $attributes = [];

        try {
            $columns = Schema::getColumns($tableName);

            foreach ($columns as $column) {
                $columnName = $column['name'];

                // Skip certain columns that shouldn't be used for visibility rules
                if (in_array($columnName, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                // Format the column name for display
                $displayName = self::formatColumnNameForDisplay($columnName);

                $attributes[$columnName] = $displayName;
            }
        } catch (\Exception $e) {
            // Fallback: try to get attributes from model's fillable or common attributes
            $attributes = self::getFallbackModelAttributes($modelClass);
        }

        return $attributes;
    }

    /**
     * Get fallback model attributes when schema introspection fails.
     */
    protected static function getFallbackModelAttributes(string $modelClass): array
    {
        $commonAttributes = [
            'name' => 'Name',
            'slug' => 'Slug',
            'title' => 'Title',
            'description' => 'Description',
            'content' => 'Content',
            'type_slug' => 'Type Slug',
            'language_code' => 'Language Code',
            'public' => 'Public',
            'published' => 'Published',
            'status' => 'Status',
            'active' => 'Active',
            'enabled' => 'Enabled',
            'visible' => 'Visible',
            'featured' => 'Featured',
            'pinned' => 'Pinned',
            'locked' => 'Locked',
            'path' => 'Path',
            'url' => 'URL',
            'meta_title' => 'Meta Title',
            'meta_description' => 'Meta Description',
            'meta_keywords' => 'Meta Keywords',
            'published_at' => 'Published At',
            'expired_at' => 'Expired At',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];

        // Try to get fillable attributes from the model
        try {
            if (class_exists($modelClass)) {
                $model = new $modelClass;
                if (method_exists($model, 'getFillable')) {
                    $fillable = $model->getFillable();
                    $attributes = [];

                    foreach ($fillable as $attribute) {
                        if (! in_array($attribute, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                            $attributes[$attribute] = self::formatColumnNameForDisplay($attribute);
                        }
                    }

                    return $attributes;
                }
            }
        } catch (\Exception $e) {
            // Continue with common attributes
        }

        return $commonAttributes;
    }

    /**
     * Get the model class from a Field record.
     */
    public static function getModelClass(Field $field): ?string
    {
        if (! $field->relationLoaded('model')) {
            $field->load('model');
        }

        $record = $field->model;

        return $record ? get_class($record) : null;
    }

    /**
     * Get the table name from a Field record.
     */
    public static function getTableName(Field $field): ?string
    {
        if (! $field->relationLoaded('model')) {
            $field->load('model');
        }

        $record = $field->model;

        return $record ? $record->getTable() : null;
    }

    /**
     * Safely retrieve an attribute value from a model instance.
     */
    public static function getAttributeValue(mixed $modelInstance, string $attribute): mixed
    {
        if (! $modelInstance || ! method_exists($modelInstance, 'getAttribute')) {
            return null;
        }

        try {
            return $modelInstance->getAttribute($attribute);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Format a column name for display in the UI.
     */
    protected static function formatColumnNameForDisplay(string $columnName): string
    {
        // Convert snake_case to Title Case
        $formatted = Str::title(str_replace('_', ' ', $columnName));

        // Handle common abbreviations
        $formatted = str_replace([' Ulid', ' Id', ' Url'], [' ULID', ' ID', ' URL'], $formatted);

        return $formatted;
    }

    /**
     * Get model attributes for a specific field.
     */
    public static function getModelAttributesForField(Field $field): array
    {
        $tableName = self::getTableName($field);
        $modelClass = self::getModelClass($field);

        if (! $tableName || ! $modelClass) {
            return [];
        }

        return self::getModelAttributesFromTable($tableName, $modelClass);
    }

    /**
     * Get model attributes for a specific model class.
     */
    public static function getModelAttributesForModel(string $modelClass): array
    {
        if (! class_exists($modelClass)) {
            return [];
        }

        try {
            $model = new $modelClass;
            $tableName = $model->getTable();

            return self::getModelAttributesFromTable($tableName, $modelClass);
        } catch (\Exception $e) {
            // If we can't create the model instance, try to get table name from class
            $reflection = new \ReflectionClass($modelClass);
            $tableName = Str::snake(Str::pluralStudly(class_basename($modelClass)));

            return self::getModelAttributesFromTable($tableName, $modelClass);
        }
    }
}
