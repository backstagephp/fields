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
 *
 * This trait provides functionality to:
 * - Map database field configurations to form input components
 * - Mutate form data before filling (loading from database)
 * - Mutate form data before saving (processing user input)
 * - Handle nested fields and builder blocks
 * - Resolve custom field types and configurations
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

    /**
     * Mutate form data before filling the form with existing values.
     *
     * This method processes the record's field values and applies any custom
     * transformation logic defined in field classes before populating the form.
     *
     * @param  array  $data  The form data array
     * @return array The mutated form data
     */
    protected function mutateBeforeFill(array $data): array
    {
        if (! $this->hasValidRecordWithFields()) {
            return $data;
        }

        // Extract builder blocks from record values
        $builderBlocks = $this->extractBuilderBlocksFromRecord();
        $allFields = $this->getAllFieldsIncludingBuilderFields($builderBlocks);

        return $this->mutateFormData($data, $allFields, function ($field, $fieldConfig, $fieldInstance, $data) use ($builderBlocks) {
            return $this->applyFieldFillMutation($field, $fieldConfig, $fieldInstance, $data, $builderBlocks);
        });
    }

    /**
     * Mutate form data before saving to the database.
     *
     * This method processes user input and applies any custom transformation logic
     * defined in field classes. It also handles special cases for builder blocks
     * and nested fields.
     *
     * @param  array  $data  The form data array
     * @return array The mutated form data ready for saving
     */
    protected function mutateBeforeSave(array $data): array
    {
        if (! $this->hasValidRecord()) {
            return $data;
        }

        $values = $this->extractFormValues($data);
        if (empty($values)) {
            return $data;
        }

        $builderBlocks = $this->extractBuilderBlocks($values);

        $allFields = $this->getAllFieldsIncludingBuilderFields($builderBlocks);

        return $this->mutateFormData($data, $allFields, function ($field, $fieldConfig, $fieldInstance, $data) use ($builderBlocks) {
            return $this->applyFieldSaveMutation($field, $fieldConfig, $fieldInstance, $data, $builderBlocks);
        });
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

    /**
     * Extract builder blocks from form values.
     *
     * Builder blocks are special field types that contain nested fields.
     * This method identifies and extracts them for special processing.
     *
     * @param  array  $values  The form values
     * @return array The builder blocks
     */
    private function extractBuilderBlocks(array $values): array
    {
        $builderFieldUlids = ModelsField::whereIn('ulid', array_keys($values))
            ->where('field_type', 'builder')
            ->pluck('ulid')
            ->toArray();

        return collect($values)
            ->filter(fn ($value, $key) => in_array($key, $builderFieldUlids))
            ->toArray();
    }

    /**
     * Get all fields including those from builder blocks.
     *
     * @param  array  $builderBlocks  The builder blocks
     * @return Collection All fields to process
     */
    private function getAllFieldsIncludingBuilderFields(array $builderBlocks): Collection
    {
        return $this->record->fields->merge(
            $this->getFieldsFromBlocks($builderBlocks)
        );
    }

    /**
     * Apply field-specific mutation logic for form filling.
     *
     * @param  Model  $field  The field model
     * @param  array  $fieldConfig  The field configuration
     * @param  object  $fieldInstance  The field instance
     * @param  array  $data  The form data
     * @param  array  $builderBlocks  The builder blocks
     * @return array The mutated data
     */
    private function applyFieldFillMutation(Model $field, array $fieldConfig, object $fieldInstance, array $data, array $builderBlocks): array
    {
        if (! empty($fieldConfig['methods']['mutateFormDataCallback'])) {
            $fieldLocation = $this->determineFieldLocation($field, $builderBlocks);

            if ($fieldLocation['isInBuilder']) {
                return $this->processBuilderFieldFillMutation($field, $fieldInstance, $data, $fieldLocation['builderData'], $builderBlocks);
            }

            return $fieldInstance->mutateFormDataCallback($this->record, $field, $data);
        }

        // Default behavior: copy value from record to form data
        $data[$this->record->valueColumn][$field->ulid] = $this->record->values[$field->ulid] ?? null;

        return $data;
    }

    /**
     * Extract builder blocks from record values.
     *
     * @return array The builder blocks
     */
    private function extractBuilderBlocksFromRecord(): array
    {
        if (! isset($this->record->values) || ! is_array($this->record->values)) {
            return [];
        }

        $builderFieldUlids = ModelsField::whereIn('ulid', array_keys($this->record->values))
            ->where('field_type', 'builder')
            ->pluck('ulid')
            ->toArray();

        return collect($this->record->values)
            ->filter(fn ($value, $key) => in_array($key, $builderFieldUlids))
            ->toArray();
    }

    /**
     * Process fill mutation for fields inside builder blocks.
     *
     * @param  Model  $field  The field model
     * @param  object  $fieldInstance  The field instance
     * @param  array  $data  The form data
     * @param  array  $builderData  The builder block data
     * @param  array  $builderBlocks  All builder blocks
     * @return array The updated form data
     */
    private function processBuilderFieldFillMutation(Model $field, object $fieldInstance, array $data, array $builderData, array $builderBlocks): array
    {
        // Create a mock record with the builder data for the callback
        $mockRecord = $this->createMockRecordForBuilder($builderData);

        // Create a temporary data structure for the callback
        $tempData = [$this->record->valueColumn => $builderData];
        $tempData = $fieldInstance->mutateFormDataCallback($mockRecord, $field, $tempData);

        // Update the original data structure with the mutated values
        $this->updateBuilderBlocksWithMutatedData($builderBlocks, $field, $tempData);

        // Update the main data structure
        $data[$this->record->valueColumn] = array_merge($data[$this->record->valueColumn], $builderBlocks);

        return $data;
    }

    /**
     * Create a mock record for builder field processing.
     *
     * @param  array  $builderData  The builder block data
     * @return object The mock record
     */
    private function createMockRecordForBuilder(array $builderData): object
    {
        $mockRecord = clone $this->record;
        $mockRecord->values = $builderData;

        return $mockRecord;
    }

    /**
     * Update builder blocks with mutated field data.
     *
     * @param  array  $builderBlocks  The builder blocks to update
     * @param  Model  $field  The field being processed
     * @param  array  $tempData  The temporary data containing mutated values
     */
    private function updateBuilderBlocksWithMutatedData(array &$builderBlocks, Model $field, array $tempData): void
    {
        foreach ($builderBlocks as $builderUlid => &$builderBlocks) {
            if (is_array($builderBlocks)) {
                foreach ($builderBlocks as &$block) {
                    if (isset($block['data']) && is_array($block['data']) && isset($block['data'][$field->ulid])) {
                        $block['data'][$field->ulid] = $tempData[$this->record->valueColumn][$field->ulid] ?? $block['data'][$field->ulid];
                    }
                }
            }
        }
    }

    /**
     * Resolve field configuration and create an instance.
     *
     * This method determines whether to use a custom field implementation
     * or fall back to the default field type mapping.
     *
     * @param  Model  $field  The field model
     * @return array Array containing 'config' and 'instance' keys
     */
    private function resolveFieldConfigAndInstance(Model $field): array
    {
        // Try to resolve from custom fields first
        $fieldConfig = Fields::resolveField($field->field_type) ?
            $this->fieldInspector->initializeCustomField($field->field_type) :
            $this->fieldInspector->initializeDefaultField($field->field_type);

        return [
            'config' => $fieldConfig,
            'instance' => new $fieldConfig['class'],
        ];
    }

    /**
     * Extract field models from builder blocks.
     *
     * Builder blocks contain nested fields that need to be processed.
     * This method extracts those field models for processing.
     *
     * @param  array  $blocks  The builder blocks
     * @return Collection The field models from blocks
     */
    protected function getFieldsFromBlocks(array $blocks): Collection
    {
        $processedFields = collect();

        collect($blocks)->map(function ($block) use (&$processedFields) {
            foreach ($block as $key => $values) {
                if (! is_array($values) || ! isset($values['data'])) {
                    continue;
                }

                $fields = $values['data'];
                $fields = ModelsField::whereIn('ulid', array_keys($fields))->get();

                $processedFields = $processedFields->merge($fields);
            }
        });

        return $processedFields;
    }

    /**
     * Apply mutation strategy to all fields recursively.
     *
     * This method processes each field and its nested children using the provided
     * mutation strategy. It handles the hierarchical nature of fields.
     *
     * @param  array  $data  The form data
     * @param  Collection  $fields  The fields to process
     * @param  callable  $mutationStrategy  The strategy to apply to each field
     * @return array The mutated form data
     */
    protected function mutateFormData(array $data, Collection $fields, callable $mutationStrategy): array
    {
        foreach ($fields as $field) {
            $field->load('children');

            ['config' => $fieldConfig, 'instance' => $fieldInstance] = $this->resolveFieldConfigAndInstance($field);
            $data = $mutationStrategy($field, $fieldConfig, $fieldInstance, $data);

            $data = $this->processNestedFields($field, $data, $mutationStrategy);
        }

        return $data;
    }

    /**
     * Process nested fields (children) of a parent field.
     *
     * @param  Model  $field  The parent field
     * @param  array  $data  The form data
     * @param  callable  $mutationStrategy  The mutation strategy
     * @return array The updated form data
     */
    private function processNestedFields(Model $field, array $data, callable $mutationStrategy): array
    {
        if (empty($field->children)) {
            return $data;
        }

        foreach ($field->children as $nestedField) {
            ['config' => $nestedFieldConfig, 'instance' => $nestedFieldInstance] = $this->resolveFieldConfigAndInstance($nestedField);
            $data = $mutationStrategy($nestedField, $nestedFieldConfig, $nestedFieldInstance, $data);
        }

        return $data;
    }

    /**
     * Resolve form field inputs for rendering.
     *
     * This method converts field models into form input components
     * that can be rendered in the UI.
     *
     * @param  mixed  $record  The record containing fields
     * @param  bool  $isNested  Whether this is a nested field
     * @return array Array of form input components
     */
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

    /**
     * Resolve a single field input component.
     *
     * This method creates the appropriate form input component for a field,
     * prioritizing custom field implementations over default ones.
     *
     * @param  Model  $field  The field model
     * @param  Collection  $customFields  Available custom fields
     * @param  mixed  $record  The record
     * @param  bool  $isNested  Whether this is a nested field
     * @return object|null The form input component or null if not found
     */
    private function resolveFieldInput(Model $field, Collection $customFields, mixed $record = null, bool $isNested = false): ?object
    {
        $record = $record ?? $this->record;

        $inputName = $this->generateInputName($field, $record, $isNested);

        // Try to resolve from custom fields first (giving them priority)
        if ($customField = $customFields->get($field->field_type)) {
            return $customField::make($inputName, $field);
        }

        // Fall back to standard field type map if no custom field found
        if ($fieldClass = self::FIELD_TYPE_MAP[$field->field_type] ?? null) {
            return $fieldClass::make(name: $inputName, field: $field);
        }

        return null;
    }

    private function generateInputName(Model $field, mixed $record, bool $isNested): string
    {
        return $isNested ? "{$field->ulid}" : "{$record->valueColumn}.{$field->ulid}";
    }

    /**
     * Apply field-specific mutation logic for form saving.
     *
     * This method handles both regular fields and fields within builder blocks.
     * Builder blocks require special processing because they contain nested data structures.
     *
     * @param  Model  $field  The field model
     * @param  array  $fieldConfig  The field configuration
     * @param  object  $fieldInstance  The field instance
     * @param  array  $data  The form data
     * @param  array  $builderBlocks  The builder blocks
     * @return array The mutated data
     */
    private function applyFieldSaveMutation(Model $field, array $fieldConfig, object $fieldInstance, array $data, array $builderBlocks): array
    {
        if (empty($fieldConfig['methods']['mutateBeforeSaveCallback'])) {
            return $data;
        }

        $fieldLocation = $this->determineFieldLocation($field, $builderBlocks);

        if ($fieldLocation['isInBuilder']) {
            return $this->processBuilderFieldMutation($field, $fieldInstance, $data, $fieldLocation['builderData'], $builderBlocks);
        }

        // Regular field processing
        return $fieldInstance->mutateBeforeSaveCallback($this->record, $field, $data);
    }

    /**
     * Determine if a field is inside a builder block and extract its data.
     *
     * @param  Model  $field  The field to check
     * @param  array  $builderBlocks  The builder blocks
     * @return array Location information with 'isInBuilder' and 'builderData' keys
     */
    private function determineFieldLocation(Model $field, array $builderBlocks): array
    {
        foreach ($builderBlocks as $builderUlid => $builderBlocks) {
            if (is_array($builderBlocks)) {
                foreach ($builderBlocks as $block) {
                    if (isset($block['data']) && is_array($block['data']) && isset($block['data'][$field->ulid])) {
                        return [
                            'isInBuilder' => true,
                            'builderData' => $block['data'],
                            'builderUlid' => $builderUlid,
                            'blockIndex' => array_search($block, $builderBlocks),
                        ];
                    }
                }
            }
        }

        return [
            'isInBuilder' => false,
            'builderData' => null,
            'builderUlid' => null,
            'blockIndex' => null,
        ];
    }

    /**
     * Process mutation for fields inside builder blocks.
     *
     * Builder fields require special handling because they're nested within
     * a complex data structure that needs to be updated in place.
     *
     * @param  Model  $field  The field model
     * @param  object  $fieldInstance  The field instance
     * @param  array  $data  The form data
     * @param  array  $builderData  The builder block data
     * @param  array  $builderBlocks  All builder blocks
     * @return array The updated form data
     */
    private function processBuilderFieldMutation(Model $field, object $fieldInstance, array $data, array $builderData, array $builderBlocks): array
    {
        foreach ($builderBlocks as $builderUlid => &$blocks) {
            if (is_array($blocks)) {
                foreach ($blocks as &$block) {
                    if (isset($block['data']) && is_array($block['data']) && isset($block['data'][$field->ulid])) {
                        // Create a mock record with the block data for the callback
                        $mockRecord = $this->createMockRecordForBuilder($block['data']);

                        // Create a temporary data structure for the callback
                        $tempData = [$this->record->valueColumn => $block['data']];
                        $tempData = $fieldInstance->mutateBeforeSaveCallback($mockRecord, $field, $tempData);

                        if (isset($tempData[$this->record->valueColumn][$field->ulid])) {
                            $block['data'][$field->ulid] = $tempData[$this->record->valueColumn][$field->ulid];
                        }
                    }
                }
            }
        }

        $data[$this->record->valueColumn] = array_merge($data[$this->record->valueColumn], $builderBlocks);

        return $data;
    }
}
