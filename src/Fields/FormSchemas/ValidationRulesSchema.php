<?php

namespace Backstage\Fields\Fields\FormSchemas;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\Str;

class ValidationRulesSchema
{
    public static function make(?string $fieldType = null): array
    {
        $validationOptions = self::getValidationOptionsForFieldType($fieldType);

        return [
            Section::make('Validation rules')
                ->collapsible()
                ->columnSpanFull()
                ->collapsed(false)
                ->compact(true)
                ->description(__('Validate the value of this field based on the rules below'))
                ->schema([
                    Repeater::make('config.validationRules')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('type')
                                ->label(__('Rule type'))
                                ->searchable()
                                ->preload()
                                ->options($validationOptions)
                                ->reactive()
                                ->required()
                                ->columnSpanFull(),
                            TextInput::make('parameters.value')
                                ->label(__('Value'))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['min', 'max', 'min_length', 'max_length', 'decimal', 'multiple_of', 'prohibited_if', 'prohibited_unless', 'required_if', 'required_unless']))
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['min', 'max', 'min_length', 'max_length', 'decimal', 'multiple_of', 'prohibited_if', 'prohibited_unless', 'required_if', 'required_unless'])),
                            TextInput::make('parameters.pattern')
                                ->label(__('Pattern'))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['regex', 'not_regex']))
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['regex', 'not_regex'])),
                            TextInput::make('parameters.values')
                                ->label(__('Values (comma-separated)'))
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['starts_with', 'ends_with', 'doesnt_start_with', 'doesnt_end_with', 'in', 'not_in'])),
                            TextInput::make('parameters.table')
                                ->label(__('Table'))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['exists', 'unique']))
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['exists', 'unique'])),
                            TextInput::make('parameters.column')
                                ->label(__('Column'))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['exists', 'unique']))
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['exists', 'unique'])),
                            Select::make('parameters.field')
                                ->label(__('Field name'))
                                ->placeholder(__('Select a field'))
                                ->searchable()
                                ->required(fn (Get $get): bool => in_array($get('type'), ['required_with', 'required_with_all', 'required_without', 'required_without_all']))
                                ->options(function ($livewire) {
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    return FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);
                                })
                                ->disabled(function ($livewire) {
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    $options = FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);

                                    return empty($options);
                                })
                                ->helperText(function ($livewire) {
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    $options = FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);

                                    if (empty($options)) {
                                        return __('No other fields available to depend on. Please create other fields first.');
                                    }

                                    return null;
                                })
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['different', 'same', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required_if', 'required_unless', 'required_if_accepted', 'greater_than', 'greater_than_or_equal', 'less_than', 'less_than_or_equal'])),
                            Select::make('parameters.fields')
                                ->label(__('Field names'))
                                ->placeholder(__('Select fields'))
                                ->multiple()
                                ->searchable()
                                ->required(fn (Get $get): bool => in_array($get('type'), ['required_with', 'required_with_all', 'required_without', 'required_without_all']))
                                ->options(function ($livewire) {
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    return FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);
                                })
                                ->disabled(function ($livewire) {
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    $options = FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);

                                    return empty($options);
                                })
                                ->helperText(function ($livewire) {
                                    $excludeUlid = null;
                                    if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                        $record = $livewire->getMountedTableActionRecord();
                                        if ($record && isset($record->ulid)) {
                                            $excludeUlid = $record->ulid;
                                        }
                                    }

                                    $options = FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);

                                    if (empty($options)) {
                                        return __('No other fields available to depend on. Please create other fields first.');
                                    }

                                    return null;
                                })
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['required_with', 'required_with_all', 'required_without', 'required_without_all'])),
                            TextInput::make('parameters.date')
                                ->label(__('Date'))
                                ->required(fn (Get $get): bool => in_array($get('type'), ['after', 'after_or_equal', 'before', 'before_or_equal', 'date_equals']))
                                ->visible(fn (Get $get): bool => in_array($get('type'), ['after', 'after_or_equal', 'before', 'before_or_equal', 'date_equals'])),
                            TextInput::make('parameters.format')
                                ->label(__('Format'))
                                ->required(fn (Get $get): bool => $get('type') === 'date_format')
                                ->visible(fn (Get $get): bool => $get('type') === 'date_format'),
                            TextInput::make('parameters.places')
                                ->label(__('Decimal places'))
                                ->required(fn (Get $get): bool => $get('type') === 'decimal')
                                ->visible(fn (Get $get): bool => $get('type') === 'decimal'),
                            TextInput::make('parameters.enum')
                                ->label(__('Enum class'))
                                ->required(fn (Get $get): bool => $get('type') === 'enum')
                                ->visible(fn (Get $get): bool => $get('type') === 'enum'),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['type'] ? str_replace('_', ' ', Str::title($state['type'])) : null)
                        ->defaultItems(0)
                        ->columns(3)
                        ->reorderableWithButtons()
                        ->columnSpanFull(),
                ]),
        ];
    }

    private static function getValidationOptionsForFieldType(?string $fieldType): array
    {
        $allOptions = [
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
            'date' => __('Date'),
            'date_equals' => __('Date equals'),
            'date_format' => __('Date format'),
            'decimal' => __('Decimal'),
            'different' => __('Different from field'),
            'doesnt_start_with' => __('Doesn\'t start with'),
            'doesnt_end_with' => __('Doesn\'t end with'),
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
            'required' => __('Required'),
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
        ];

        if (! $fieldType) {
            return $allOptions;
        }

        $fieldTypeOptions = [
            'text' => [
                'active_url', 'alpha', 'alpha_dash', 'alpha_num', 'ascii', 'confirmed', 'different', 'doesnt_start_with', 'doesnt_end_with', 'email', 'ends_with', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'max_length', 'min_length', 'not_in', 'nullable', 'numeric', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'regex', 'not_regex', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'starts_with', 'string', 'unique', 'url',
            ],
            'textarea' => [
                'alpha', 'alpha_dash', 'alpha_num', 'ascii', 'different', 'doesnt_start_with', 'doesnt_end_with', 'ends_with', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'max_length', 'min_length', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'regex', 'not_regex', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'starts_with', 'string', 'unique',
            ],
            'rich-editor' => [
                'different', 'doesnt_start_with', 'doesnt_end_with', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'max_length', 'min_length', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'string', 'unique',
            ],
            'markdown-editor' => [
                'different', 'doesnt_start_with', 'doesnt_end_with', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'max_length', 'min_length', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'string', 'unique',
            ],
            'select' => [
                'different', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'radio' => [
                'different', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'checkbox' => [
                'different', 'filled', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same',
            ],
            'checkbox-list' => [
                'different', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'toggle' => [
                'different', 'filled', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same',
            ],
            'color' => [
                'different', 'filled', 'hex_color', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'date-time' => [
                'after', 'after_or_equal', 'before', 'before_or_equal', 'date', 'date_equals', 'date_format', 'different', 'filled', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'key-value' => [
                'different', 'filled', 'json', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'tags' => [
                'different', 'filled', 'greater_than', 'greater_than_or_equal', 'in', 'less_than', 'less_than_or_equal', 'not_in', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same', 'unique',
            ],
            'repeater' => [
                'different', 'filled', 'nullable', 'prohibited', 'prohibited_if', 'prohibited_unless', 'prohibits', 'required', 'required_if', 'required_if_accepted', 'required_unless', 'required_with', 'required_with_all', 'required_without', 'required_without_all', 'same',
            ],
        ];

        $allowedRules = $fieldTypeOptions[$fieldType] ?? array_keys($allOptions);

        return array_intersect_key($allOptions, array_flip($allowedRules));
    }
}
