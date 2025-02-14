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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (! isset($this->record) || $this->record->fields->isEmpty()) {
            return $data;
        }

        return $this->mutateFormData($data, function ($field, $fieldConfig, $fieldInstance, $data) {
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

        return $this->mutateFormData($data, function ($field, $fieldConfig, $fieldInstance, $data) {
            if (! empty($fieldConfig['methods']['mutateBeforeSaveCallback'])) {
                return $fieldInstance->mutateBeforeSaveCallback($this->record, $field, $data);
            }

            return $data;
        });
    }

    protected function mutateFormData(array $data, callable $mutationStrategy): array
    {
        foreach ($this->record->fields as $field) {
            $fieldConfig = Field::tryFrom($field->field_type)
                ? $this->fieldInspector->initializeDefaultField($field->field_type)
                : $this->fieldInspector->initializeCustomField($field->field_type);

            $fieldInstance = new $fieldConfig['class'];
            $data = $mutationStrategy($field, $fieldConfig, $fieldInstance, $data);
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
            ->map(fn($field) => $this->resolveFieldInput($field, $customFields, $record, $isNested))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveCustomFields(): Collection
    {
        return collect(Fields::getFields())
            ->map(fn($fieldClass) => new $fieldClass);
    }

    private function resolveFieldInput(Model $field, Collection $customFields, mixed $record = null, bool $isNested = false): ?object
    {
        $record = $record ?? $this->record;

        $inputName = $isNested ? "{$field->ulid}" : "{$record->valueColumn}.{$field->ulid}";

        // Try to resolve from standard field type map
        if ($fieldClass = self::FIELD_TYPE_MAP[$field->field_type] ?? null) {
            return $fieldClass::make(name: $inputName, field: $field);
        }

        // Try to resolve from custom fields
        if ($customField = $customFields->get($field->field_type)) {
            return $customField::make($inputName, $field);
        }

        return null;
    }
}
