<?php

namespace Vormkracht10\FilamentFields\Concerns;

use Livewire\Attributes\On;
use Vormkracht10\Fields\Fields;
use Illuminate\Support\Collection;
use Vormkracht10\FilamentFields\Enums\Field;
use Vormkracht10\FilamentFields\Fields\Tags;
use Vormkracht10\FilamentFields\Fields\Text;
use Vormkracht10\FilamentFields\Fields\Color;
use Vormkracht10\FilamentFields\Fields\Radio;
use Vormkracht10\FilamentFields\Fields\Select;
use Vormkracht10\FilamentFields\Fields\Toggle;
use Vormkracht10\FilamentFields\Fields\Checkbox;
use Vormkracht10\FilamentFields\Fields\DateTime;
use Vormkracht10\FilamentFields\Fields\KeyValue;
use Vormkracht10\FilamentFields\Fields\Repeater;
use Vormkracht10\FilamentFields\Fields\Textarea;
use Vormkracht10\FilamentFields\Fields\RichEditor;
use Vormkracht10\FilamentFields\Fields\CheckboxList;
use Vormkracht10\FilamentFields\Models\Field as Model;
use Vormkracht10\FilamentFields\Contracts\FieldInspector;

trait HasFieldsMapper
{
    private FieldInspector $fieldInspector;

    private const FIELD_TYPE_MAP = [
        'text' => Text::class,
        'textarea' => Textarea::class,
        'rich-editor' => RichEditor::class,
        'repeater' => Repeater::class,
        // 'select' => Select::class, WIP
        'checkbox' => Checkbox::class,
        // 'checkbox-list' => CheckboxList::class, WIP
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

            $data['setting'][$field->slug] = $this->record->values[$field->slug] ?? null;

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

        // TODO: Change this
        // Move settings to values
        $fields = $data['setting'] ?? [];
        unset($data['setting']);
        $data['values'] = $fields;

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
        // TODO: Setting. shoud be dynamic
        $inputName = "setting.{$field->slug}";

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