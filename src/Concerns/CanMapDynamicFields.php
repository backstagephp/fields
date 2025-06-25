<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Contracts\FieldInspector;
use Backstage\Fields\Enums\Field;
use Backstage\Fields\Fields;
use Backstage\Fields\Fields\Checkbox;
use Backstage\Fields\Fields\CheckboxList;
use Backstage\Fields\Fields\Color;
use Backstage\Fields\Fields\DateTime;
use Backstage\Fields\Fields\KeyValue;
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

trait CanMapDynamicFields
{
    private FieldInspector $fieldInspector;

    private const FIELD_TYPE_MAP = [
        'text' => Text::class,
        'textarea' => Textarea::class,
        'rich-editor' => RichEditor::class,
        'repeater' => Repeater::class,
        'select' => Select::class,
        'checkbox' => Checkbox::class,
        'checkbox-list' => CheckboxList::class,
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
        if (! isset($this->record) || $this->record->fields->isEmpty()) {
            return $data;
        }

        $fields = $this->record->fields;

        return $this->mutateFormData($data, $fields, function ($field, $fieldConfig, $fieldInstance, $data) {
            if (! empty($fieldConfig['methods']['mutateFormDataCallback'])) {
                return $fieldInstance->mutateFormDataCallback($this->record, $field, $data);
            }

            $data[$this->record->valueColumn][$field->ulid] = $this->record->values[$field->ulid] ?? null;

            return $data;
        });
    }

    protected function mutateBeforeSave(array $data): array
    {
        if (! isset($this->record)) {
            return $data;
        }

        $values = isset($data[$this->record?->valueColumn]) ? $data[$this->record?->valueColumn] : [];

        if (empty($values)) {
            return $data;
        }

        $fieldsFromValues = array_keys($values);

        $blocks = ModelsField::whereIn('ulid', $fieldsFromValues)->where('field_type', 'builder')->pluck('ulid')->toArray();
        $blocks = collect($values)->filter(fn ($value, $key) => in_array($key, $blocks))->toArray();

        $fields = $this->record->fields->merge(
            $this->getFieldsFromBlocks($blocks)
        );

        return $this->mutateFormData($data, $fields, function ($field, $fieldConfig, $fieldInstance, $data) {
            if (! empty($fieldConfig['methods']['mutateBeforeSaveCallback'])) {
                return $fieldInstance->mutateBeforeSaveCallback($this->record, $field, $data);
            }

            return $data;
        });
    }

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

    protected function getFieldsFromBlocks(array $blocks): Collection
    {
        $processedFields = collect();

        collect($blocks)->map(function ($block) use (&$processedFields) {
            foreach ($block as $key => $values) {
                $fields = $values['data'];
                $fields = ModelsField::whereIn('ulid', array_keys($fields))->get();

                $processedFields = $processedFields->merge($fields);
            }
        });

        return $processedFields;
    }

    protected function mutateFormData(array $data, Collection $fields, callable $mutationStrategy): array
    {
        foreach ($fields as $field) {
            $field->load('children');

            ['config' => $fieldConfig, 'instance' => $fieldInstance] = $this->resolveFieldConfigAndInstance($field);
            $data = $mutationStrategy($field, $fieldConfig, $fieldInstance, $data);

            if (! empty($field->children)) {
                foreach ($field->children as $nestedField) {
                    ['config' => $nestedFieldConfig, 'instance' => $nestedFieldInstance] = $this->resolveFieldConfigAndInstance($nestedField);
                    $data = $mutationStrategy($nestedField, $nestedFieldConfig, $nestedFieldInstance, $data);
                }
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

        $inputName = $isNested ? "{$field->ulid}" : "{$record->valueColumn}.{$field->ulid}";

        // Try to resolve from custom fields first (giving them priority)
        if ($customField = $customFields->get($field->field_type)) {
            return $customField::make($inputName, $field);
        }

        // // Fall back to standard field type map if no custom field found
        if ($fieldClass = self::FIELD_TYPE_MAP[$field->field_type] ?? null) {
            return $fieldClass::make(name: $inputName, field: $field);
        }

        return null;
    }
}
