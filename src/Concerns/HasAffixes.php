<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms;
use Filament\Forms\Components\Fieldset;
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
            $input->prefixIconColor(Color::hex($field->config['prefixIconColor']));
        }

        if (isset($field->config['suffixIconColor']) && $field->config['suffixIconColor']) {
            $input->suffixIconColor(Color::hex($field->config['suffixIconColor']));
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
        return Forms\Components\Fieldset::make('Affixes')
            ->columnSpanFull()
            ->label(__('Affixes'))
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\TextInput::make('config.prefix')
                            ->label(__('Prefix')),
                        Forms\Components\TextInput::make('config.prefixIcon')
                            ->placeholder('heroicon-m-')
                            ->label(__('Prefix icon')),
                        Forms\Components\ColorPicker::make('config.prefixIconColor')
                            ->label(__('Prefix color')),
                        Forms\Components\TextInput::make('config.suffix')
                            ->label(__('Suffix')),
                        Forms\Components\TextInput::make('config.suffixIcon')
                            ->placeholder('heroicon-m-')
                            ->label(__('Suffix icon')),
                        Forms\Components\ColorPicker::make('config.suffixIconColor')
                            ->label(__('Suffix color')),
                    ]),
            ]);
    }
}
