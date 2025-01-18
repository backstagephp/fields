<?php

namespace Vormkracht10\Fields\Fields;

use Filament\Forms;
use Filament\Forms\Components\TagsInput as Input;
use Vormkracht10\Fields\Contracts\FieldContract;
use Vormkracht10\Fields\Models\Field;

class Tags extends Base implements FieldContract
{
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
            Forms\Components\Tabs::make()
                ->schema([
                    Forms\Components\Tabs\Tab::make('General')
                        ->label(__('General'))
                        ->schema([
                            ...parent::getForm(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Forms\Components\Toggle::make('config.reorderable')
                                ->label(__('Reorderable'))
                                ->inline(false),
                            Forms\Components\Select::make('config.color')
                                ->label(__('Color'))
                                ->options([
                                    'primary' => __('Primary'),
                                    'success' => __('Success'),
                                    'danger' => __('Danger'),
                                    'warning' => __('Warning'),
                                    'info' => __('Info'),
                                    'gray' => __('Gray'),
                                ]),
                            Forms\Components\TextInput::make('config.tagPrefix')
                                ->label(__('Tag prefix')),
                            Forms\Components\TextInput::make('config.tagSuffix')
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
}
