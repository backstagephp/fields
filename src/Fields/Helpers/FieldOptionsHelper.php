<?php

namespace Backstage\Fields\Fields\Helpers;

use Backstage\Fields\Models\Field;

class FieldOptionsHelper
{
    public static function getFieldOptions(mixed $livewire, ?string $excludeUlid = null): array
    {
        // The $livewire parameter is actually the FieldsRelationManager
        if (! $livewire || ! method_exists($livewire, 'getOwnerRecord')) {
            return [];
        }

        $ownerRecord = $livewire->getOwnerRecord();

        if (! $ownerRecord) {
            return [];
        }

        $fields = Field::where('model_type', 'setting')
            ->where('model_key', $ownerRecord->getKey())
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
}
