<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput as Input;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class Tags extends Base implements FieldContract
{
    public function getFieldType(): ?string
    {
        return 'tags';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'reorderable' => true,
            'tagPrefix' => null,
            'tagSuffix' => null,
            'color' => 'primary',
            'splitKeys' => ['Enter'],
            'nestedRecursiveRules' => [],
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->reorderable($field->config['reorderable'] ?? self::getDefaultConfig()['reorderable'])
            ->color($field->config['color'] ?? self::getDefaultConfig()['color'])
            ->tagPrefix($field->config['tagPrefix'] ?? self::getDefaultConfig()['tagPrefix'])
            ->tagSuffix($field->config['tagSuffix'] ?? self::getDefaultConfig()['tagSuffix']);

        $splitKeys = self::ensureArray($field->config['splitKeys'] ?? self::getDefaultConfig()['splitKeys']);
        $nestedRecursiveRules = self::ensureArray($field->config['nestedRecursiveRules'] ?? self::getDefaultConfig()['nestedRecursiveRules']);

        $input->splitKeys($splitKeys);
        $input->nestedRecursiveRules($nestedRecursiveRules);

        return $input;
    }

    public function getForm(): array
    {
        return [
            Tabs::make()
                ->schema([
                    Tab::make('General')
                        ->label(__('General'))
                        ->schema([
                            ...parent::getForm(),
                        ]),
                    Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Toggle::make('config.reorderable')
                                ->label(__('Reorderable')),
                            Select::make('config.color')
                                ->label(__('Color'))
                                ->options([
                                    'primary' => __('Primary'),
                                    'success' => __('Success'),
                                    'danger' => __('Danger'),
                                    'warning' => __('Warning'),
                                    'info' => __('Info'),
                                    'gray' => __('Gray'),
                                ]),
                            TextInput::make('config.tagPrefix')
                                ->label(__('Tag prefix')),
                            TextInput::make('config.tagSuffix')
                                ->label(__('Tag suffix')),
                            Input::make('config.nestedRecursiveRules')
                                ->label(__('Nested recursive rules'))
                                ->formatStateUsing(function ($state) {
                                    return explode(',', $state);
                                })
                                ->separator(','),
                            Input::make('config.splitKeys')
                                ->label(__('Split keys'))
                                ->suggestions(['Tab', 'Enter'])
                                ->formatStateUsing(function ($state) {
                                    return explode(',', $state);
                                })
                                ->separator(','),
                        ])->columns(2),
                ])->columnSpanFull(),
        ];
    }

    protected static function ensureArray(array | string $value, string $delimiter = ','): array
    {
        if (is_array($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? explode($delimiter, $trimmed) : [];
    }
}
