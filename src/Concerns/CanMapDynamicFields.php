<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Contracts\FieldInspector;
use Backstage\Fields\Fields;
use Backstage\Fields\Fields\Checkbox;
use Backstage\Fields\Fields\CheckboxList;
use Backstage\Fields\Fields\Color;
use Backstage\Fields\Fields\DateTime;
use Backstage\Fields\Fields\FileUpload;
use Backstage\Fields\Fields\KeyValue;
use Backstage\Fields\Fields\MarkdownEditor;
use Backstage\Fields\Fields\Radio;
use Backstage\Fields\Fields\Repeater;
use Backstage\Fields\Fields\RichEditor;
use Backstage\Fields\Fields\Select;
use Backstage\Fields\Fields\Tags;
use Backstage\Fields\Fields\Text;
use Backstage\Fields\Fields\Textarea;
use Backstage\Fields\Fields\Toggle;
use Backstage\Fields\Models\Field as Model;
use Backstage\Fields\Models\Field as ModelsField;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

/**
 * Trait for handling dynamic field mapping and data mutation in forms.
 */
trait CanMapDynamicFields
{
    private FieldInspector $fieldInspector;

    private const FIELD_TYPE_MAP = [
        'text' => Text::class,
        'textarea' => Textarea::class,
        'rich-editor' => RichEditor::class,
        'markdown-editor' => MarkdownEditor::class,
        'repeater' => Repeater::class,
        'select' => Select::class,
        'checkbox' => Checkbox::class,
        'checkbox-list' => CheckboxList::class,
        'file-upload' => FileUpload::class,
        'key-value' => KeyValue::class,
        'radio' => Radio::class,
        'toggle' => Toggle::class,
        'color' => Color::class,
        'date-time' => DateTime::class,
        'tags' => Tags::class,
    ];

    public function boot(): void
    {
        $this->fieldInspector = app(FieldInspector::class);
    }

    #[On('refreshFields')]
    public function refresh(): void
    {
        //
    }

    protected function mutateBeforeFill(array $data): array
    {
        if (! $this->hasValidRecordWithFields()) {
            return $data;
        }

        $containerData = $this->extractContainerDataFromRecord();
        $allFields = $this->getAllFieldsIncludingNested($containerData);

        return $this->mutateFormData($data, $allFields, function ($field, $fieldConfig, $fieldInstance, $data) use ($containerData) {
            return $this->applyFieldFillMutation($field, $fieldConfig, $fieldInstance, $data, $containerData);
        });

        return $mutatedData;
    }

    protected function mutateBeforeSave(array $data): array
    {
        if (! $this->hasValidRecord()) {
            return $data;
        }

        $values = $this->extractFormValues($data);
        if (empty($values)) {
            return $data;
        }

        $containerData = $this->extractContainerData($values);
        $allFields = $this->getAllFieldsIncludingNested($containerData);

        return $this->mutateFormData($data, $allFields, function ($field, $fieldConfig, $fieldInstance, $data) {
            return $this->applyFieldSaveMutation($field, $fieldConfig, $fieldInstance, $data);
        });

        return $mutatedData;
    }

    private function hasValidRecordWithFields(): bool
    {
        return isset($this->record) && ! $this->record->fields->isEmpty();
    }

    private function hasValidRecord(): bool
    {
        return isset($this->record);
    }

    private function extractFormValues(array $data): array
    {
        return isset($data[$this->record?->valueColumn]) ? $data[$this->record?->valueColumn] : [];
    }

    private function extractContainerData(array $values): array
    {
        $containerFieldUlids = ModelsField::whereIn('ulid', array_keys($values))
            ->whereIn('field_type', ['builder', 'repeater'])
            ->pluck('ulid')
            ->toArray();

        return collect($values)
            ->filter(fn ($value, $key) => in_array($key, $containerFieldUlids))
            ->toArray();
    }

    private function getAllFieldsIncludingNested(array $containerData): Collection
    {
        return $this->record->fields->merge(
            $this->getNestedFieldsFromContainerData($containerData)
        )->unique('ulid');
    }

    private function applyFieldFillMutation(Model $field, array $fieldConfig, object $fieldInstance, array $data, array $containerData): array
    {
        if (! empty($fieldConfig['methods']['mutateFormDataCallback'])) {
            $fieldLocation = $this->determineFieldLocation($field, $containerData);

            if ($fieldLocation['isInContainer']) {
                return $this->processContainerFieldFillMutation($field, $fieldInstance, $data, $fieldLocation);
            }

            return $fieldInstance->mutateFormDataCallback($this->record, $field, $data);
        }

        $data[$this->record->valueColumn][$field->ulid] = $fieldInstance->getFieldValueFromRecord($this->record, $field);

        return $data;
    }

    private function extractContainerDataFromRecord(): array
    {
        if (! isset($this->record->values) || ! is_array($this->record->values)) {
            return [];
        }

        $containerFieldUlids = ModelsField::whereIn('ulid', array_keys($this->record->values))
            ->whereIn('field_type', ['builder', 'repeater'])
            ->pluck('ulid')
            ->toArray();

        return collect($this->record->values)
            ->filter(fn ($value, $key) => in_array($key, $containerFieldUlids))
            ->toArray();
    }

    private function processContainerFieldFillMutation(Model $field, object $fieldInstance, array $data, array $fieldLocation): array
    {
        $mockRecord = $this->createMockRecordForBuilder($fieldLocation['containerData']);
        $tempData = [$this->record->valueColumn => $fieldLocation['containerData']];
        $tempData = $fieldInstance->mutateFormDataCallback($mockRecord, $field, $tempData);

        // Check for both ULID and slug keys (nested fields use slug)
        $mutatedValue = $tempData[$this->record->valueColumn][$field->ulid] ?? $tempData[$this->record->valueColumn][$field->slug] ?? null;

        if ($mutatedValue !== null || isset($tempData[$this->record->valueColumn][$field->ulid]) || isset($tempData[$this->record->valueColumn][$field->slug])) {
            $this->updateDataAtPath($data[$this->record->valueColumn], $fieldLocation['fullPath'], $fieldLocation['fieldKey'], $mutatedValue);
        }

        return $data;
    }

    private function createMockRecordForBuilder(array $builderData): object
    {
        $mockRecord = clone $this->record;
        $mockRecord->values = $builderData;

        return $mockRecord;
    }

    private function resolveFieldConfigAndInstance(Model $field): array
    {
        $fieldConfig = Fields::resolveField($field->field_type) ?
            $this->fieldInspector->initializeCustomField($field->field_type) :
            $this->fieldInspector->initializeDefaultField($field->field_type);

        return [
            'config' => $fieldConfig,
            'instance' => new $fieldConfig['class'],
        ];
    }

    protected function getNestedFieldsFromContainerData(array $containerData): Collection
    {
        $processedFields = collect();

        foreach ($containerData as $rows) {
            if (! is_array($rows)) {
                continue;
            }
            foreach ($rows as $item) {
                $itemData = isset($item['data']) ? $item['data'] : $item;

                if (is_array($itemData)) {
                    $fields = ModelsField::whereIn('ulid', array_keys($itemData))
                        ->orWhereIn('slug', array_keys($itemData))
                        ->get();

                    $processedFields = $processedFields->merge($fields);

                    // Recursive search
                    $nestedContainers = $this->extractContainerData($itemData);
                    if (! empty($nestedContainers)) {
                        $processedFields = $processedFields->merge($this->getNestedFieldsFromContainerData($nestedContainers));
                    }
                }
            }
        }

        return $processedFields->unique('ulid');
    }

    protected function mutateFormData(array $data, Collection $fields, callable $mutationStrategy): array
    {
        foreach ($fields as $field) {
            ['config' => $fieldConfig, 'instance' => $fieldInstance] = $this->resolveFieldConfigAndInstance($field);

            $valueColumn = $this->record->valueColumn ?? 'values';
            $oldValue = $data[$valueColumn][$field->ulid] ?? $data[$valueColumn][$field->slug] ?? 'NOT_SET';

            $data = $mutationStrategy($field, $fieldConfig, $fieldInstance, $data);

            $newValue = $data[$valueColumn][$field->ulid] ?? $data[$valueColumn][$field->slug] ?? 'NOT_SET';

            if ($newValue === true) {
                \Log::warning("Field {$field->ulid} (slug: {$field->slug}, type: {$field->field_type}) mutated to TRUE", [
                    'old_value' => $oldValue,
                    'instance_class' => get_class($fieldInstance),
                ]);
            }
        }

        return $data;
    }

    private function resolveFormFields(mixed $record = null, bool $isNested = false): array
    {
        $record = $record ?? $this->record;

        if (! isset($record->fields) || $record->fields->isEmpty()) {
            return [];
        }

        $customFields = $this->resolveCustomFields();

        return $record->fields
            ->map(fn ($field) => $this->resolveFieldInput($field, $customFields, $record, $isNested))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveCustomFields(): Collection
    {
        return collect(Fields::getFields())
            ->map(fn ($fieldClass) => new $fieldClass);
    }

    private function resolveFieldInput(Model $field, Collection $customFields, mixed $record = null, bool $isNested = false): ?object
    {
        $record = $record ?? $this->record;
        $inputName = $this->generateInputName($field, $record, $isNested);

        if ($customField = $customFields->get($field->field_type)) {
            return $customField::make($inputName, $field);
        }

        if ($fieldClass = self::FIELD_TYPE_MAP[$field->field_type] ?? null) {
            return $fieldClass::make(name: $inputName, field: $field);
        }

        return null;
    }

    private function generateInputName(Model $field, mixed $record, bool $isNested): string
    {
        return $isNested ? "{$field->ulid}" : "{$record->valueColumn}.{$field->ulid}";
    }

    private function applyFieldSaveMutation(Model $field, array $fieldConfig, object $fieldInstance, array $data): array
    {
        if (empty($fieldConfig['methods']['mutateBeforeSaveCallback'])) {
            return $data;
        }

        $values = $this->extractFormValues($data);
        $containerData = $this->extractContainerData($values);
        $fieldLocation = $this->determineFieldLocation($field, $containerData);

        if ($fieldLocation['isInContainer']) {
            return $this->processContainerFieldMutation($field, $fieldInstance, $data, $fieldLocation);
        }

        return $fieldInstance->mutateBeforeSaveCallback($this->record, $field, $data);
    }

    private function determineFieldLocation(Model $field, array $containers, array $path = []): array
    {
        foreach ($containers as $containerUlid => $rows) {
            if (is_array($rows)) {
                foreach ($rows as $index => $item) {
                    $itemData = isset($item['data']) ? $item['data'] : $item;

                    if (is_array($itemData)) {
                        if (isset($itemData[$field->ulid]) || isset($itemData[$field->slug])) {
                            return [
                                'isInContainer' => true,
                                'containerData' => $itemData,
                                'fieldKey' => isset($itemData[$field->ulid]) ? $field->ulid : $field->slug,
                                'containerUlid' => $containerUlid,
                                'rowIndex' => $index,
                                'fullPath' => array_merge($path, [$containerUlid, $index]),
                            ];
                        }

                        $nestedContainers = $this->extractContainerData($itemData);
                        if (! empty($nestedContainers)) {
                            $result = $this->determineFieldLocation($field, $nestedContainers, array_merge($path, [$containerUlid, $index]));
                            if ($result['isInContainer']) {
                                return $result;
                            }
                        }
                    }
                }
            }
        }

        return [
            'isInContainer' => false,
            'containerData' => null,
            'containerUlid' => null,
            'rowIndex' => null,
            'fullPath' => [],
        ];
    }

    private function processContainerFieldMutation(Model $field, object $fieldInstance, array $data, array $fieldLocation): array
    {
        $mockRecord = $this->createMockRecordForBuilder($fieldLocation['containerData']);
        $tempData = [$this->record->valueColumn => $fieldLocation['containerData']];
        $tempData = $fieldInstance->mutateBeforeSaveCallback($mockRecord, $field, $tempData);

        if (isset($tempData[$this->record->valueColumn][$field->ulid])) {
            $mutatedValue = $tempData[$this->record->valueColumn][$field->ulid];
            $this->updateDataAtPath($data[$this->record->valueColumn], $fieldLocation['fullPath'], $fieldLocation['fieldKey'], $mutatedValue);
        }

        return $data;
    }

    private function updateDataAtPath(array &$data, array $path, string $fieldKey, mixed $value): void
    {
        $current = &$data;
        foreach ($path as $key) {
            if (is_array($current) && isset($current[$key])) {
                $current = &$current[$key];
            } else {
                return;
            }
        }

        // If 'data' key exists, it's a builder block row
        if (is_array($current) && isset($current['data'])) {
            $current['data'][$fieldKey] = $value;
        } elseif (is_array($current)) {
            $current[$fieldKey] = $value;
        }
    }
}
