<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Support\Colors\Color;

trait HasAffixes
{
    public static function addAffixesToInput(mixed $input, mixed $field): mixed
    {
        $input = $input->prefix($field->config['prefix'] ?? self::getDefaultConfig()['prefix'])
            ->prefixIcon($field->config['prefixIcon'] ?? self::getDefaultConfig()['prefixIcon'])
            ->suffix($field->config['suffix'] ?? self::getDefaultConfig()['suffix'])
            ->suffixIcon($field->config['suffixIcon'] ?? self::getDefaultConfig()['suffixIcon']);

        if (isset($field->config['prefixIconColor']) && $field->config['prefixIconColor']) {
            $input->prefixIconColor(Color::generateV3Palette($field->config['prefixIconColor']));
        }

        if (isset($field->config['suffixIconColor']) && $field->config['suffixIconColor']) {
            $input->suffixIconColor(Color::generateV3Palette($field->config['suffixIconColor']));
        }

        return $input;
    }

    public static function getAffixesConfig(): array
    {
        return [
            'prefix' => null,
            'prefixIcon' => null,
            'prefixIconColor' => null,
            'suffix' => null,
            'suffixIcon' => null,
            'suffixIconColor' => null,
        ];
    }

    public function affixFormFields(): Fieldset
    {
        return Fieldset::make('Affixes')
            ->columnSpanFull()
            ->label(__('Affixes'))
            ->schema([
                Grid::make(3)
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('config.prefix')
                            ->label(__('Prefix')),
                        TextInput::make('config.prefixIcon')
                            ->placeholder('heroicon-m-')
                            ->label(__('Prefix icon')),
                        ColorPicker::make('config.prefixIconColor')
                            ->label(__('Prefix color')),
                        TextInput::make('config.suffix')
                            ->label(__('Suffix')),
                        TextInput::make('config.suffixIcon')
                            ->placeholder('heroicon-m-')
                            ->label(__('Suffix icon')),
                        ColorPicker::make('config.suffixIconColor')
                            ->label(__('Suffix color')),
                    ]),
            ]);
    }
}
