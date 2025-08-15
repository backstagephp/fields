<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;

abstract class Base implements FieldContract
{
    public function getForm(): array
    {
        return [
            Grid::make(3)
                ->schema([
                    Toggle::make('config.required')
                        ->label(__('Required'))
                        ->inline(false),
                    Toggle::make('config.disabled')
                        ->label(__('Disabled'))
                        ->inline(false),
                    Toggle::make('config.hidden')
                        ->label(__('Hidden'))
                        ->inline(false),
                    TextInput::make('config.defaultValue')
                        ->label(__('Default value'))
                        ->helperText(__('This value will be used when creating new records.')),
                ]),
            Grid::make(2)
                ->schema([
                    TextInput::make('config.helperText')
                        ->live(onBlur: true)
                        ->label(__('Helper text')),
                    TextInput::make('config.hint')
                        ->live(onBlur: true)
                        ->label(__('Hint')),
                    ColorPicker::make('config.hintColor')
                        ->label(__('Hint color'))
                        ->visible(function (Get $get): bool {
                            $hint = $get('config.hint');

                            return ! empty(trim($hint));
                        }),
                    TextInput::make('config.hintIcon')
                        ->label(__('Hint icon'))
                        ->placeholder('heroicon-m-')
                        ->visible(function (Get $get): bool {
                            $hint = $get('config.hint');

                            return ! empty(trim($hint));
                        }),
                ]),
            Section::make(__('Validation Rules'))
                ->schema([
                    Select::make('config.required_if_type')
                        ->label(__('Required If Type'))
                        ->options([
                            'required_if' => __('Required If'),
                            'required_unless' => __('Required Unless'),
                        ])
                        ->placeholder(__('Select validation rule'))
                        ->live()
                        ->afterStateUpdated(function (Get $get, $set) {
                            // Clear other validation fields when type changes
                            $set('config.required_if_field', null);
                            $set('config.required_if_values', []);
                            $set('config.required_unless_values', []);
                        }),

                    Select::make('config.required_if_field')
                        ->label(__('Target Field'))
                        ->options(function (Get $get) {
                            $currentFieldId = $get('id');
                            $modelKey = $get('model_key');

                            return Field::where('model_key', $modelKey)
                                ->where('id', '!=', $currentFieldId)
                                ->pluck('name', 'slug')
                                ->toArray();
                        })
                        ->searchable()
                        ->visible(fn (Get $get) => filled($get('config.required_if_type'))),

                    Repeater::make('config.required_if_values')
                        ->label(__('Required Values'))
                        ->schema([
                            TextInput::make('value')
                                ->label(__('Value'))
                                ->required(),
                        ])
                        ->addActionLabel(__('Add Value'))
                        ->visible(fn (Get $get) => in_array($get('config.required_if_type'), ['required_if', 'required_unless']))
                        ->columns(1),

                    Repeater::make('config.required_unless_values')
                        ->label(__('Required Unless Values'))
                        ->schema([
                            TextInput::make('value')
                                ->label(__('Value'))
                                ->required(),
                        ])
                        ->addActionLabel(__('Add Value'))
                        ->visible(fn (Get $get) => $get('config.required_if_type') === 'required_unless')
                        ->columns(1),
                ])
                ->collapsible()
                ->collapsed(),

        ];

        return $this->filterExcludedFields($schema);
    }

    protected function excludeFromBaseSchema(): array
    {
        return [];
    }

    private function filterExcludedFields(array $schema): array
    {
        $excluded = $this->excludeFromBaseSchema();

        if (empty($excluded)) {
            return $schema;
        }

        return array_filter($schema, function ($field) use ($excluded) {
            foreach ($excluded as $excludedField) {
                if ($this->fieldContainsConfigKey($field, $excludedField)) {
                    return false;
                }
            }

            return true;
        });
    }

    private function fieldContainsConfigKey($field, string $configKey): bool
    {
        $reflection = new \ReflectionObject($field);
        $propertiesToCheck = ['name', 'statePath'];

        foreach ($propertiesToCheck as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                $value = $property->getValue($field);

                if (str_contains($value, "config.{$configKey}")) {
                    return true;
                }
            }
        }

        return false;
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
            'required_if_type' => null,
            'required_if_field' => null,
            'required_if_values' => [],
            'required_unless_values' => [],
            'defaultValue' => null,
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
            $input->hintColor($field->config['hintColor']);
        }

        // Apply validation rules
        $requiredIfType = $field->config['required_if_type'] ?? null;
        $requiredIfField = $field->config['required_if_field'] ?? null;

        if ($requiredIfType && $requiredIfField) {
            switch ($requiredIfType) {
                case 'required_if':
                    $values = collect($field->config['required_if_values'] ?? [])
                        ->pluck('value')
                        ->filter()
                        ->toArray();

                    if (! empty($values)) {
                        $input->requiredIf($requiredIfField, ...$values);
                    }

                    break;

                case 'required_unless':
                    $values = collect($field->config['required_unless_values'] ?? [])
                        ->pluck('value')
                        ->filter()
                        ->toArray();

                    if (! empty($values)) {
                        $input->requiredUnless($requiredIfField, ...$values);
                    }

                    break;
            }
            $input->hintColor(Color::generateV3Palette($field->config['hintColor']));
        }

        if (isset($field->config['defaultValue'])) {
            $input->default($field->config['defaultValue']);
        }

        return $input;
    }

    protected static function ensureArray($value, string $delimiter = ','): array
    {
        if (is_array($value)) {
            return $value;
        }

        return ! empty($value) ? explode($delimiter, $value) : [];
    }
}
