<?php

namespace Backstage\Fields\Fields\Helpers;

use Backstage\Fields\Models\Field;

class FieldOptionsHelper
{
    public static function getFieldOptions(mixed $livewire, ?string $excludeUlid = null): array
    {
        $currentField = self::getCurrentField($excludeUlid);
        $modelContext = self::resolveModelContext($livewire, $currentField);

        if (! $modelContext) {
            return [];
        }

        $query = self::buildFieldQuery($currentField, $modelContext['modelType'], $modelContext['modelKey']);

        return self::formatAndFilterResults($query, $excludeUlid);
    }

    protected static function getCurrentField(?string $excludeUlid): ?Field
    {
        return $excludeUlid ? Field::find($excludeUlid) : null;
    }

    protected static function resolveModelContext(mixed $livewire, ?Field $currentField): ?array
    {
        $modelType = null;
        $modelKey = null;

        // Context 1: FieldsRelationManager
        if ($livewire && method_exists($livewire, 'getOwnerRecord')) {
            $ownerRecord = $livewire->getOwnerRecord();
            if ($ownerRecord) {
                return [
                    'modelType' => get_class($ownerRecord),
                    'modelKey' => $ownerRecord->getKey(),
                ];
            }
        }

        // Context 2: Child field in repeater - get from parent field
        if ($currentField && $currentField->parent_ulid) {
            $parentField = Field::find($currentField->parent_ulid);
            if ($parentField) {
                return [
                    'modelType' => $parentField->model_type,
                    'modelKey' => $parentField->model_key,
                ];
            }
        }

        // Context 3: EditRecord page - get from current field
        if ($currentField && ! $currentField->parent_ulid) {
            return [
                'modelType' => $currentField->model_type,
                'modelKey' => $currentField->model_key,
            ];
        }

        // Context 4: Fallback - try to get from livewire record property
        if ($livewire && property_exists($livewire, 'record')) {
            $record = $livewire->record;
            if ($record) {
                return [
                    'modelType' => get_class($record),
                    'modelKey' => $record->getKey(),
                ];
            }
        }

        return null;
    }

    protected static function buildFieldQuery(?Field $currentField, string $modelType, string $modelKey)
    {
        $shortModelType = strtolower(class_basename($modelType));

        // Child field: show main fields + siblings (exclude parent repeater)
        if ($currentField && $currentField->parent_ulid) {
            return Field::where(function ($q) use ($currentField, $modelType, $shortModelType, $modelKey) {
                // Main fields with matching model_type/model_key (but not the parent)
                $q->where(function ($mainQuery) use ($currentField, $modelType, $shortModelType, $modelKey) {
                    $mainQuery->where('model_key', $modelKey)
                        ->whereNull('parent_ulid')
                        ->where('ulid', '!=', $currentField->parent_ulid) // Exclude parent repeater
                        ->where(function ($typeQuery) use ($modelType, $shortModelType) {
                            $typeQuery->where('model_type', $modelType)
                                ->orWhere('model_type', $shortModelType);
                        });
                })
                // OR siblings (same parent_ulid)
                ->orWhere('parent_ulid', $currentField->parent_ulid);
            });
        }

        // Main field: show only main fields
        return Field::where('model_key', $modelKey)
            ->where(function ($q) use ($modelType, $shortModelType) {
                $q->where('model_type', $modelType)
                    ->orWhere('model_type', $shortModelType);
            })
            ->whereNull('parent_ulid');
    }

    protected static function formatAndFilterResults($query, ?string $excludeUlid): array
    {
        $fields = $query->get(['ulid', 'name', 'field_type'])
            ->filter(function ($field) {
                // Exclude container fields that don't have checkable values
                return ! in_array($field->field_type, ['repeater', 'group']);
            })
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('name', 'ulid')
            ->toArray();

        if ($excludeUlid && isset($fields[$excludeUlid])) {
            unset($fields[$excludeUlid]);
        }

        return $fields;
    }

    public static function getFieldNameFromUlid(string $ulid, Field $currentField): ?string
    {
        $conditionalField = Field::find($ulid);

        if (! $conditionalField) {
            return null;
        }

        if (! $currentField->relationLoaded('model')) {
            $currentField->load('model');
        }

        $record = $currentField->model;

        if (! $record || ! isset($record->valueColumn)) {
            return null;
        }

        return "{$record->valueColumn}.{$ulid}";
    }

    public static function getFieldNamesFromUlids(array $ulids, Field $currentField): array
    {
        $fieldNames = [];

        foreach ($ulids as $ulid) {
            $fieldName = self::getFieldNameFromUlid($ulid, $currentField);
            if ($fieldName) {
                $fieldNames[] = $fieldName;
            }
        }

        return $fieldNames;
    }

    public static function getModelAttributeOptions(mixed $livewire): array
    {
        return ModelAttributeHelper::getModelAttributes($livewire);
    }

    public static function getFieldProperties(string $fieldUlid, ?Field $currentField = null): array
    {
        $field = Field::find($fieldUlid);

        if (! $field) {
            return [];
        }

        // Only allow accessing child fields if we're in the same repeater context
        // This prevents main fields from trying to check nested repeater children
        if ($currentField) {
            // If current field is a main field (no parent), don't show repeater children
            if (! $currentField->parent_ulid) {
                return [];
            }

            // If current field is in a different repeater, don't show these children
            if ($currentField->parent_ulid !== $fieldUlid) {
                return [];
            }
        }

        // Get child fields (for repeaters, groups, etc.)
        $properties = Field::where('parent_ulid', $fieldUlid)
            ->orderBy('position')
            ->get(['ulid', 'name'])
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->pluck('name', 'ulid')
            ->toArray();

        return $properties;
    }
}
