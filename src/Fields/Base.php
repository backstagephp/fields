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
        return $this->getBaseFormSchema();
    }

    protected function getBaseFormSchema(): array
    {
        $schema = [
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
            Forms\Components\TextInput::make('config.defaultValue')
                ->label(__('Default value'))
                ->helperText(__('This value will be used when creating new records.')),
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
        
        if ($reflection->hasProperty('name')) {
            $nameProperty = $reflection->getProperty('name');
            $nameProperty->setAccessible(true);
            $name = $nameProperty->getValue($field);
            
            if (str_contains($name, "config.{$configKey}")) {
                return true;
            }
        }
        
        if ($reflection->hasProperty('statePath')) {
            $statePathProperty = $reflection->getProperty('statePath');
            $statePathProperty->setAccessible(true);
            $statePath = $statePathProperty->getValue($field);
            
            if (str_contains($statePath, "config.{$configKey}")) {
                return true;
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
            $input->hintColor(Color::hex($field->config['hintColor']));
        }

        if (isset($field->config['defaultValue']) && $field->config['defaultValue'] !== null) {
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
