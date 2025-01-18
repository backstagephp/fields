<?php

namespace Vormkracht10\FilamentFields\Fields;

use Filament\Forms;
use Filament\Support\Colors\Color;
use Vormkracht10\FilamentFields\Contracts\FieldContract;
use Vormkracht10\FilamentFields\Models\Field;

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
