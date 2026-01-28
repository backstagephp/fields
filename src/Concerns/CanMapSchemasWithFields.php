<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Models\Field;
use Backstage\Fields\Models\Schema;

trait CanMapSchemasWithFields
{
    use CanMapDynamicFields;

    protected function loadAllFieldsIntoRecord(): void
    {
        $allFields = $this->record->fields;

        foreach ($this->record->schemas as $schema) {
            $schemaFields = Field::where('schema_id', $schema->ulid)->get();
            $allFields = $allFields->merge($schemaFields);
        }

        $this->record->setRelation('fields', $allFields);
    }

    protected function loadDefaultValuesIntoRecord(): void
    {
        $defaultValues = [];
        $allFields = $this->record->fields;

        foreach ($allFields as $field) {
            $defaultValue = $field->config['defaultValue'] ?? null;

            if ($field->field_type === 'select' && $defaultValue === null) {
                continue;
            }

            $defaultValues[$field->ulid] = $defaultValue;
        }

        $this->record->setAttribute('values', $defaultValues);
    }

    protected function getFieldsFromSchema(Schema $schema): \Illuminate\Support\Collection
    {
        $fields = collect();
        $schemaFields = Field::where('schema_id', $schema->ulid)->get();
        $fields = $fields->merge($schemaFields);

        $childSchemas = $schema->children()->get();
        foreach ($childSchemas as $childSchema) {
            $fields = $fields->merge($this->getFieldsFromSchema($childSchema));
        }

        return $fields;
    }

    protected function getAllSchemaFields(): \Illuminate\Support\Collection
    {
        $allFields = collect();
        $rootSchemas = $this->record->schemas()
            ->whereNull('parent_ulid')
            ->orderBy('position')
            ->get();

        foreach ($rootSchemas as $schema) {
            $allFields = $allFields->merge($this->getFieldsFromSchema($schema));
        }

        return $allFields;
    }

    protected function initializeFormData(): void
    {
        $this->loadAllFieldsIntoRecord();
        $this->loadDefaultValuesIntoRecord();
        $this->data = $this->mutateBeforeFill($this->data);
    }

    protected function mutateBeforeFill(array $data): array
    {
        if (! $this->hasValidRecordWithFields()) {
            return $data;
        }

        $containerData = $this->extractContainerDataFromRecord();
        $allFields = $this->getAllFieldsIncludingNested($containerData);

        if (! isset($data[$this->record->valueColumn])) {
            $data[$this->record->valueColumn] = [];
        }

        return $this->mutateFormData($data, $allFields, function ($field, $fieldConfig, $fieldInstance, $data) use ($containerData) {
            if ($field->field_type === 'select') {
                if (isset($this->record->values[$field->ulid])) {
                    $data[$this->record->valueColumn][$field->ulid] = $this->record->values[$field->ulid];
                }

                return $data;
            }

            return $this->applyFieldFillMutation($field, $fieldConfig, $fieldInstance, $data, $containerData);
        });
    }
}
