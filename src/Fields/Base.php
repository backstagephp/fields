<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms;
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
                                ->label(__('Field'))
                                ->placeholder(__('Select a field'))
                                ->searchable()
                                ->live()
                                ->options(function ($livewire) {
                                    // Try to get the current field's ULID from the form state
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    return self::getFieldOptions($livewire, $excludeUlid);
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
                        ])->columns(3),
                    Forms\Components\Fieldset::make('Validation rules')
                        ->schema([
                            Forms\Components\Repeater::make('config.validationRules')
                                ->hiddenLabel()
                                ->schema([
                                    Forms\Components\Select::make('type')
                                        ->label(__('Rule type'))
                                        ->searchable()
                                        ->preload()
                                        ->options([
                                            'active_url' => __('Active URL'),
                                            'after' => __('After date'),
                                            'after_or_equal' => __('After or equal to date'),
                                            'alpha' => __('Alphabetic'),
                                            'alpha_dash' => __('Alphanumeric with dashes'),
                                            'alpha_num' => __('Alphanumeric'),
                                            'ascii' => __('ASCII'),
                                            'before' => __('Before date'),
                                            'before_or_equal' => __('Before or equal to date'),
                                            'confirmed' => __('Confirmed'),
                                            'custom' => __('Custom rule'),
                                            'date' => __('Date'),
                                            'date_equals' => __('Date equals'),
                                            'date_format' => __('Date format'),
                                            'decimal' => __('Decimal'),
                                            'different' => __('Different from field'),
                                            'doesnt_end_with' => __('Doesn\'t end with'),
                                            'doesnt_start_with' => __('Doesn\'t start with'),
                                            'email' => __('Email'),
                                            'ends_with' => __('Ends with'),
                                            'enum' => __('Enum'),
                                            'exists' => __('Exists in database'),
                                            'filled' => __('Filled'),
                                            'greater_than' => __('Greater than field'),
                                            'greater_than_or_equal' => __('Greater than or equal to field'),
                                            'hex_color' => __('Hex color'),
                                            'in' => __('In list'),
                                            'integer' => __('Integer'),
                                            'ip' => __('IP Address'),
                                            'ipv4' => __('IPv4 Address'),
                                            'ipv6' => __('IPv6 Address'),
                                            'json' => __('JSON'),
                                            'less_than' => __('Less than field'),
                                            'less_than_or_equal' => __('Less than or equal to field'),
                                            'mac_address' => __('MAC Address'),
                                            'max' => __('Maximum value'),
                                            'max_length' => __('Maximum length'),
                                            'min' => __('Minimum value'),
                                            'min_length' => __('Minimum length'),
                                            'multiple_of' => __('Multiple of'),
                                            'not_in' => __('Not in list'),
                                            'not_regex' => __('Not regex pattern'),
                                            'nullable' => __('Nullable'),
                                            'numeric' => __('Numeric'),
                                            'prohibited' => __('Prohibited'),
                                            'prohibited_if' => __('Prohibited if'),
                                            'prohibited_unless' => __('Prohibited unless'),
                                            'prohibits' => __('Prohibits'),
                                            'regex' => __('Regex pattern'),
                                            'required_if' => __('Required if'),
                                            'required_if_accepted' => __('Required if accepted'),
                                            'required_unless' => __('Required unless'),
                                            'required_with' => __('Required with'),
                                            'required_with_all' => __('Required with all'),
                                            'required_without' => __('Required without'),
                                            'required_without_all' => __('Required without all'),
                                            'same' => __('Same as field'),
                                            'starts_with' => __('Starts with'),
                                            'string' => __('String'),
                                            'ulid' => __('ULID'),
                                            'unique' => __('Unique in database'),
                                            'url' => __('URL'),
                                            'uuid' => __('UUID'),
                                        ])
                                        ->reactive()
                                        ->required(),
                                    Forms\Components\TextInput::make('parameters.value')
                                        ->label(__('Value'))
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['min', 'max', 'min_length', 'max_length', 'decimal', 'multiple_of', 'prohibited_if', 'prohibited_unless', 'required_if', 'required_unless']))
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['min', 'max', 'min_length', 'max_length', 'decimal', 'multiple_of', 'prohibited_if', 'prohibited_unless', 'required_if', 'required_unless'])),
                                    Forms\Components\TextInput::make('parameters.pattern')
                                        ->label(__('Pattern'))
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['regex', 'not_regex']))
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['regex', 'not_regex'])),
                                    Forms\Components\TextInput::make('parameters.values')
                                        ->label(__('Values (comma-separated)'))
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['starts_with', 'ends_with', 'doesnt_start_with', 'doesnt_end_with', 'in', 'not_in'])),
                                    Forms\Components\TextInput::make('parameters.table')
                                        ->label(__('Table'))
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['exists', 'unique']))
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['exists', 'unique'])),
                                    Forms\Components\TextInput::make('parameters.column')
                                        ->label(__('Column'))
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['exists', 'unique']))
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['exists', 'unique'])),
                                    Forms\Components\Select::make('parameters.field')
                                        ->label(__('Field name'))
                                        ->placeholder(__('Select a field'))
                                        ->searchable()
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['required_with', 'required_with_all', 'required_without', 'required_without_all']))
                                        ->options(function ($livewire) {
                                            // Try to get the current field's ULID from the form state
                                            $excludeUlid = null;
                                            if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                                $record = $livewire->getMountedTableActionRecord();
                                                if ($record && isset($record->ulid)) {
                                                    $excludeUlid = $record->ulid;
                                                }
                                            }

                                            return self::getFieldOptions($livewire, $excludeUlid);
                                        })
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['different', 'same', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required_if', 'required_unless', 'required_if_accepted', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal'])),
                                    Forms\Components\Select::make('parameters.fields')
                                        ->label(__('Field names'))
                                        ->placeholder(__('Select fields'))
                                        ->multiple()
                                        ->searchable()
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['required_with', 'required_with_all', 'required_without', 'required_without_all']))
                                        ->options(function ($livewire) {
                                            // Try to get the current field's ULID from the form state
                                            $excludeUlid = null;
                                            if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                                $record = $livewire->getMountedTableActionRecord();
                                                if ($record && isset($record->ulid)) {
                                                    $excludeUlid = $record->ulid;
                                                }
                                            }

                                            return self::getFieldOptions($livewire, $excludeUlid);
                                        })
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['required_with', 'required_with_all', 'required_without', 'required_without_all'])),
                                    Forms\Components\TextInput::make('parameters.date')
                                        ->label(__('Date'))
                                        ->required(fn (Forms\Get $get): bool => in_array($get('type'), ['after', 'after_or_equal', 'before', 'before_or_equal', 'date_equals']))
                                        ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['after', 'after_or_equal', 'before', 'before_or_equal', 'date_equals'])),
                                    Forms\Components\TextInput::make('parameters.format')
                                        ->label(__('Format'))
                                        ->required(fn (Forms\Get $get): bool => $get('type') === 'date_format')
                                        ->visible(fn (Forms\Get $get): bool => $get('type') === 'date_format'),
                                    Forms\Components\TextInput::make('parameters.places')
                                        ->label(__('Decimal places'))
                                        ->required(fn (Forms\Get $get): bool => $get('type') === 'decimal')
                                        ->visible(fn (Forms\Get $get): bool => $get('type') === 'decimal'),
                                    Forms\Components\TextInput::make('parameters.enum')
                                        ->label(__('Enum class'))
                                        ->required(fn (Forms\Get $get): bool => $get('type') === 'enum')
                                        ->visible(fn (Forms\Get $get): bool => $get('type') === 'enum'),
                                    Forms\Components\TextInput::make('parameters.rule')
                                        ->label(__('Custom rule'))
                                        ->required(fn (Forms\Get $get): bool => $get('type') === 'custom')
                                        ->visible(fn (Forms\Get $get): bool => $get('type') === 'custom'),
                                ])
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['type'] ?? null)
                                ->defaultItems(0)
                                ->reorderableWithButtons()
                                ->columnSpanFull(),
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
            'validationRules' => [],
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

        $input = self::applyConditionalLogic($input, $field);

        $input = self::applyConditionalValidation($input, $field);

        $input = self::applyAdditionalValidation($input, $field);

        return $input;
    }

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

    protected static function applyConditionalValidation($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['conditionalField']) || empty($field->config['conditionalAction'])) {
            return $input;
        }

        $conditionalField = $field->config['conditionalField'];
        $operator = $field->config['conditionalOperator'] ?? 'equals';
        $value = $field->config['conditionalValue'] ?? null;
        $action = $field->config['conditionalAction'];

        $conditionalFieldName = self::getFieldNameFromUlid($conditionalField, $field);

        if (! $conditionalFieldName) {
            return $input;
        }

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
                break;
        }

        return $input;
    }

    /**
     * Apply additional validation rules based on field configuration
     */
    protected static function applyAdditionalValidation($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['validationRules'])) {
            return $input;
        }

        $rules = $field->config['validationRules'];

        foreach ($rules as $rule) {
            $input = self::applyValidationRule($input, $rule, $field);
        }

        return $input;
    }

    protected static function applyValidationRule($input, array $rule, ?Field $field = null): mixed
    {
        $ruleType = $rule['type'] ?? '';
        $parameters = $rule['parameters'] ?? [];

        switch ($ruleType) {
            case 'min':
                $input->min($parameters['value'] ?? 0);

                break;

            case 'max':
                $input->max($parameters['value'] ?? 999999);

                break;

            case 'min_length':
                $input->minLength($parameters['value'] ?? 0);

                break;

            case 'max_length':
                $input->maxLength($parameters['value'] ?? 255);

                break;

            case 'numeric':
                $input->numeric();

                break;

            case 'integer':
                $input->integer();

                break;

            case 'decimal':
                $input->numeric();
                $input->rules(['decimal:' . ($parameters['places'] ?? 2)]);

                break;

            case 'email':
                $input->email();

                break;

            case 'url':
                $input->url();

                break;

            case 'active_url':
                $input->rules(['active_url']);

                break;

            case 'ip':
                $input->rules(['ip']);

                break;

            case 'ipv4':
                $input->rules(['ipv4']);

                break;

            case 'ipv6':
                $input->rules(['ipv6']);

                break;

            case 'mac_address':
                $input->rules(['mac_address']);

                break;

            case 'uuid':
                $input->uuid();

                break;

            case 'ulid':
                $input->ulid();

                break;

            case 'alpha':
                $input->rules(['alpha']);

                break;

            case 'alpha_dash':
                $input->rules(['alpha_dash']);

                break;

            case 'alpha_num':
                $input->rules(['alpha_num']);

                break;

            case 'ascii':
                $input->rules(['ascii']);

                break;

            case 'json':
                $input->rules(['json']);

                break;

            case 'regex':
                $input->regex($parameters['pattern'] ?? '/.*/');

                break;

            case 'not_regex':
                $input->rules(['not_regex:' . ($parameters['pattern'] ?? '/.*/')]);

                break;

            case 'starts_with':
                $input->startsWith(self::parseValidationValues($parameters['values'] ?? ''));

                break;

            case 'ends_with':
                $input->rules(['ends_with:' . ($parameters['values'] ?? '')]);

                break;

            case 'doesnt_start_with':
                $input->rules(['doesnt_start_with:' . ($parameters['values'] ?? '')]);

                break;

            case 'doesnt_end_with':
                $input->rules(['doesnt_end_with:' . ($parameters['values'] ?? '')]);

                break;

            case 'in':
                $input->rules(['in:' . ($parameters['values'] ?? '')]);

                break;

            case 'not_in':
                $input->rules(['not_in:' . ($parameters['values'] ?? '')]);

                break;

            case 'exists':
                $input->rules(['exists:' . ($parameters['table'] ?? '') . ',' . ($parameters['column'] ?? 'id')]);

                break;

            case 'unique':
                $input->unique(
                    table: $parameters['table'] ?? null,
                    column: $parameters['column'] ?? null,
                    ignorable: $parameters['ignorable'] ?? null,
                    ignoreRecord: $parameters['ignoreRecord'] ?? false
                );

                break;

            case 'different':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->different($fieldName ?? '');

                break;

            case 'same':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->same($fieldName ?? '');

                break;

            case 'confirmed':
                $input->confirmed();

                break;

            case 'after':
                $input->after($parameters['date'] ?? 'today');

                break;

            case 'after_or_equal':
                $input->afterOrEqual($parameters['date'] ?? 'today');

                break;

            case 'before':
                $input->before($parameters['date'] ?? 'today');

                break;

            case 'before_or_equal':
                $input->beforeOrEqual($parameters['date'] ?? 'today');

                break;

            case 'date':
                $input->date();

                break;

            case 'date_equals':
                $input->rules(['date_equals:' . ($parameters['date'] ?? 'today')]);

                break;

            case 'date_format':
                $input->rules(['date_format:' . ($parameters['format'] ?? 'Y-m-d')]);

                break;

            case 'multiple_of':
                $input->rules(['multiple_of:' . ($parameters['value'] ?? 1)]);

                break;

            case 'hex_color':
                $input->rules(['hex_color']);

                break;

            case 'filled':
                $input->rules(['filled']);

                break;

            case 'nullable':
                $input->nullable();

                break;

            case 'prohibited':
                $input->rules(['prohibited']);

                break;

            case 'prohibited_if':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->rules(['prohibited_if:' . $fieldName . ',' . $value]);
                }

                break;

            case 'prohibited_unless':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->rules(['prohibited_unless:' . $fieldName . ',' . $value]);
                }

                break;

            case 'prohibits':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->rules(['prohibits:' . ($fieldName ?? '')]);

                break;

            case 'required_with':
                $fieldNames = self::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->rules(['required_with:' . implode(',', $fieldNames)]);

                break;

            case 'required_with_all':
                $fieldNames = self::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->rules(['required_with_all:' . implode(',', $fieldNames)]);

                break;

            case 'required_without':
                $fieldNames = self::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->rules(['required_without:' . implode(',', $fieldNames)]);

                break;

            case 'required_without_all':
                $fieldNames = self::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->rules(['required_without_all:' . implode(',', $fieldNames)]);

                break;

            case 'required_if':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->rules(['required_if:' . $fieldName . ',' . $value]);
                }

                break;

            case 'required_unless':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->rules(['required_unless:' . $fieldName . ',' . $value]);
                }

                break;

            case 'required_if_accepted':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->requiredIfAccepted($fieldName ?? '');

                break;

            case 'greater_than':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->rules(['gt:' . ($fieldName ?? '')]);

                break;

            case 'greater_than_or_equal':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->rules(['gte:' . ($fieldName ?? '')]);

                break;

            case 'less_than':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->rules(['lt:' . ($fieldName ?? '')]);

                break;

            case 'less_than_or_equal':
                $fieldName = self::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $input->rules(['lte:' . ($fieldName ?? '')]);

                break;

            case 'enum':
                $input->rules(['enum:' . ($parameters['enum'] ?? '')]);

                break;

            case 'string':
                $input->string();

                break;

            case 'custom':
                $input->rules([$parameters['rule'] ?? '']);

                break;
        }

        return $input;
    }

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

    protected static function getFieldNameFromUlid(string $ulid, Field $currentField): ?string
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

    protected static function ensureArray($value, string $delimiter = ','): array
    {
        if (is_array($value)) {
            return $value;
        }

        return ! empty($value) ? explode($delimiter, $value) : [];
    }

    protected static function parseValidationValues(string $values): array
    {
        return array_map('trim', explode(',', $values));
    }

    protected static function getFieldNamesFromUlids(array $ulids, Field $currentField): array
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

    protected static function getFieldOptions($livewire, ?string $excludeUlid = null): array
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
}
