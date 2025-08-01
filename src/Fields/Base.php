<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Support\Colors\Color;

abstract class Base implements FieldContract
{
    public function getForm(): array
    {
        return [
            Forms\Components\Grid::make(3)
                ->schema([
                    Forms\Components\Toggle::make('config.required')
                        ->label(__('Required'))
                        ->inline(false),
                    Forms\Components\Toggle::make('config.disabled')
                        ->label(__('Disabled'))
                        ->inline(false),
                    Forms\Components\Toggle::make('config.hidden')
                        ->label(__('Hidden'))
                        ->inline(false),
                ]),
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('config.helperText')
                        ->live(onBlur: true)
                        ->label(__('Helper text')),
                    Forms\Components\TextInput::make('config.hint')
                        ->live(onBlur: true)
                        ->label(__('Hint')),
                    Forms\Components\ColorPicker::make('config.hintColor')
                        ->label(__('Hint color'))
                        ->visible(function (Forms\Get $get): bool {
                            $hint = $get('config.hint');

                            return ! empty(trim($hint));
                        }),
                    Forms\Components\TextInput::make('config.hintIcon')
                        ->label(__('Hint icon'))
                        ->placeholder('heroicon-m-')
                        ->visible(function (Forms\Get $get): bool {
                            $hint = $get('config.hint');

                            return ! empty(trim($hint));
                        }),
                ]),
        ];
    }

    /**
     * Get the Rules form schema for conditional logic
     */
    public function getRulesForm(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Fieldset::make('Conditional logic')
                        ->schema([

                            Forms\Components\Select::make('config.conditionalField')
                                ->label(__('Show/Hide based on field'))
                                ->placeholder(__('Select a field'))
                                ->searchable()
                                ->live()
                                ->options(function ($livewire) {
                                    // The $livewire parameter is actually the FieldsRelationManager
                                    if (! $livewire || ! method_exists($livewire, 'getOwnerRecord')) {
                                        return [];
                                    }

                                    $ownerRecord = $livewire->getOwnerRecord();

                                    if (! $ownerRecord) {
                                        return [];
                                    }

                                    // Get all existing fields for this owner record
                                    $fields = Field::where('model_type', 'setting')
                                        ->where('model_key', $ownerRecord->getKey())
                                        ->pluck('name', 'ulid')
                                        ->toArray();

                                    return $fields;
                                }),
                            Forms\Components\Select::make('config.conditionalOperator')
                                ->label(__('Condition'))
                                ->options([
                                    'equals' => __('Equals'),
                                    'not_equals' => __('Does not equal'),
                                    'contains' => __('Contains'),
                                    'not_contains' => __('Does not contain'),
                                    'starts_with' => __('Starts with'),
                                    'ends_with' => __('Ends with'),
                                    'is_empty' => __('Is empty'),
                                    'is_not_empty' => __('Is not empty'),
                                ])
                                ->visible(fn (Forms\Get $get): bool => filled($get('config.conditionalField'))),
                            Forms\Components\TextInput::make('config.conditionalValue')
                                ->label(__('Value'))
                                ->visible(
                                    fn (Forms\Get $get): bool => filled($get('config.conditionalField')) &&
                                        ! in_array($get('config.conditionalOperator'), ['is_empty', 'is_not_empty'])
                                ),
                            Forms\Components\Select::make('config.conditionalAction')
                                ->label(__('Action'))
                                ->options([
                                    'show' => __('Show field'),
                                    'hide' => __('Hide field'),
                                    'required' => __('Make required'),
                                    'not_required' => __('Make not required'),
                                ])
                                ->visible(fn (Forms\Get $get): bool => filled($get('config.conditionalField'))),
                        ]),
                ]),
        ];
    }

    public static function getDefaultConfig(): array
    {
        return [
            'required' => false,
            'disabled' => false,
            'hidden' => false,
            'helperText' => null,
            'hint' => null,
            'hintColor' => null,
            'hintIcon' => null,
            'conditionalField' => null,
            'conditionalOperator' => null,
            'conditionalValue' => null,
            'conditionalAction' => null,
        ];
    }

    public static function applyDefaultSettings($input, ?Field $field = null)
    {
        $input
            ->required($field->config['required'] ?? self::getDefaultConfig()['required'])
            ->disabled($field->config['disabled'] ?? self::getDefaultConfig()['disabled'])
            ->hidden($field->config['hidden'] ?? self::getDefaultConfig()['hidden'])
            ->helperText($field->config['helperText'] ?? self::getDefaultConfig()['helperText'])
            ->hint($field->config['hint'] ?? self::getDefaultConfig()['hint'])
            ->hintIcon($field->config['hintIcon'] ?? self::getDefaultConfig()['hintIcon']);

        if (isset($field->config['hintColor']) && $field->config['hintColor']) {
            $input->hintColor(Color::hex($field->config['hintColor']));
        }

        // Apply conditional logic
        $input = self::applyConditionalLogic($input, $field);

        // Apply conditional validation rules
        $input = self::applyConditionalValidation($input, $field);

        return $input;
    }

    /**
     * Apply conditional visibility and required logic to the input
     */
    protected static function applyConditionalLogic($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['conditionalField']) || empty($field->config['conditionalAction'])) {
            return $input;
        }

        $conditionalField = $field->config['conditionalField'];
        $operator = $field->config['conditionalOperator'] ?? 'equals';
        $value = $field->config['conditionalValue'] ?? null;
        $action = $field->config['conditionalAction'];

        // Get the field name for the conditional field
        $conditionalFieldName = self::getFieldNameFromUlid($conditionalField, $field);

        if (! $conditionalFieldName) {
            return $input;
        }

        switch ($action) {
            case 'show':
                $input->visible(
                    fn (Forms\Get $get): bool => self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;

            case 'hide':
                $input->visible(
                    fn (Forms\Get $get): bool => ! self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;

            case 'required':
                $input->required(
                    fn (Forms\Get $get): bool => self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;

            case 'not_required':
                $input->required(
                    fn (Forms\Get $get): bool => ! self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;
        }

        return $input;
    }

    /**
     * Apply conditional validation rules
     */
    protected static function applyConditionalValidation($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['conditionalField']) || empty($field->config['conditionalAction'])) {
            return $input;
        }

        $conditionalField = $field->config['conditionalField'];
        $operator = $field->config['conditionalOperator'] ?? 'equals';
        $value = $field->config['conditionalValue'] ?? null;
        $action = $field->config['conditionalAction'];

        // Get the field name for the conditional field
        $conditionalFieldName = self::getFieldNameFromUlid($conditionalField, $field);

        if (! $conditionalFieldName) {
            return $input;
        }

        // Apply Filament validation rules based on conditional logic
        switch ($action) {
            case 'required':
                if ($operator === 'equals' && $value !== null) {
                    $input->requiredIf($conditionalFieldName, $value);
                } elseif ($operator === 'not_equals' && $value !== null) {
                    $input->requiredUnless($conditionalFieldName, $value);
                } elseif ($operator === 'is_empty') {
                    $input->requiredUnless($conditionalFieldName, '');
                } elseif ($operator === 'is_not_empty') {
                    $input->requiredIf($conditionalFieldName, '');
                }

                break;

            case 'not_required':
                // For not_required, we don't apply validation rules as the field is optional
                break;
        }

        return $input;
    }

    /**
     * Evaluate the conditional logic
     */
    protected static function evaluateCondition($fieldValue, string $operator, $expectedValue): bool
    {
        switch ($operator) {
            case 'equals':
                return $fieldValue == $expectedValue;

            case 'not_equals':
                return $fieldValue != $expectedValue;

            case 'contains':
                return is_string($fieldValue) && str_contains($fieldValue, $expectedValue);

            case 'not_contains':
                return is_string($fieldValue) && ! str_contains($fieldValue, $expectedValue);

            case 'starts_with':
                return is_string($fieldValue) && str_starts_with($fieldValue, $expectedValue);

            case 'ends_with':
                return is_string($fieldValue) && str_ends_with($fieldValue, $expectedValue);

            case 'is_empty':
                return empty($fieldValue);

            case 'is_not_empty':
                return ! empty($fieldValue);

            default:
                return false;
        }
    }

    /**
     * Get the field name from ULID for form access
     */
    protected static function getFieldNameFromUlid(string $ulid, Field $currentField): ?string
    {
        // Find the conditional field
        $conditionalField = Field::find($ulid);

        if (! $conditionalField) {
            return null;
        }

        // Get the record that owns these fields
        // Load the model relationship if it's not already loaded
        if (! $currentField->relationLoaded('model')) {
            $currentField->load('model');
        }

        $record = $currentField->model;

        if (! $record || ! isset($record->valueColumn)) {
            return null;
        }

        // Return the field name in the format used by the form
        return "{$record->valueColumn}.{$ulid}";
    }

    protected static function ensureArray($value, string $delimiter = ','): array
    {
        if (is_array($value)) {
            return $value;
        }

        return ! empty($value) ? explode($delimiter, $value) : [];
    }
}
