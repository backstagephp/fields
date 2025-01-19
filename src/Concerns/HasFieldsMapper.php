<?php

namespace Vormkracht10\Fields\Concerns;

use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Vormkracht10\Fields\Contracts\FieldInspector;
use Vormkracht10\Fields\Enums\Field;
use Vormkracht10\Fields\Fields;
use Vormkracht10\Fields\Fields\Checkbox;
use Vormkracht10\Fields\Fields\CheckboxList;
use Vormkracht10\Fields\Fields\Color;
use Vormkracht10\Fields\Fields\DateTime;
use Vormkracht10\Fields\Fields\KeyValue;
use Vormkracht10\Fields\Fields\Radio;
use Vormkracht10\Fields\Fields\Repeater;
use Vormkracht10\Fields\Fields\RichEditor;
use Vormkracht10\Fields\Fields\Select;
use Vormkracht10\Fields\Fields\Tags;
use Vormkracht10\Fields\Fields\Text;
use Vormkracht10\Fields\Fields\Textarea;
use Vormkracht10\Fields\Fields\Toggle;
use Vormkracht10\Fields\Models\Field as Model;

trait HasFieldsMapper
{
    private FieldInspector $fieldInspector;

    private const FIELD_TYPE_MAP = [
        'text' => Text::class,
        'textarea' => Textarea::class,
        'rich-editor' => RichEditor::class,
        // 'repeater' => Repeater::class, WIP
        'select' => Select::class,
        'checkbox' => Checkbox::class,
        'checkbox-list' => CheckboxList::class,
        'key-value' => KeyValue::class,
        'radio' => Radio::class,
        'toggle' => Toggle::class,
        'color' => Color::class,
        'datetime' => DateTime::class,
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
        if ($this->record->fields->isEmpty()) {
            return $data;
        }

        return $this->mutateFormData($data, function ($field, $fieldConfig, $fieldInstance, $data) {
            if (! empty($fieldConfig['methods']['mutateFormDataCallback'])) {
                return $fieldInstance->mutateFormDataCallback($this->record, $field, $data);
            }

            $data['values'][$field->slug] = $this->record->values[$field->slug] ?? null;

            return $data;
        });
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->mutateFormData($data, function ($field, $fieldConfig, $fieldInstance, $data) {
            if (! empty($fieldConfig['methods']['mutateBeforeSaveCallback'])) {
                return $fieldInstance->mutateBeforeSaveCallback($this->record, $field, $data);
            }

            return $data;
        });

        return $data;
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

    private function resolveFormFields(): array
    {
        if ($this->record->fields->isEmpty()) {
            return [];
        }

        $customFields = $this->resolveCustomFields();

        return $this->record->fields
            ->map(fn($field) => $this->resolveFieldInput($field, $customFields))
            ->filter()
            ->values()
            ->all();
    }

    private function resolveCustomFields(): Collection
    {
        return collect(Fields::getFields())
            ->map(fn($fieldClass) => new $fieldClass);
    }

    private function resolveFieldInput(Model $field, Collection $customFields): ?object
    {
        $inputName = "values.{$field->slug}";

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