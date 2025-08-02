<?php

namespace Backstage\Fields\Fields\FormSchemas;

use Filament\Forms;

class BasicSettingsSchema
{
    public static function make(): array
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
} 