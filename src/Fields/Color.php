<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ColorFormat;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\ColorPicker as Input;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

/**
 * For validation regex patterns, check the Filament documentation.
 *
 * @see https://filamentphp.com/docs/3.x/forms/fields/color-picker#color-picker-validation
 */
class Color extends Base implements FieldContract
{
    public function getFieldType(): ?string
    {
        return 'color';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'color' => ColorFormat::HEX->value,
            'regex' => null,
        ];
    }

    public static function make(string $name, Field $field): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->regex($field->config['regex'] ?? self::getDefaultConfig()['regex']);

        if ($field->config['color'] ?? self::getDefaultConfig()['color']) {
            $input->{$field->config['color']}(true);
        }

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
                            Grid::make(2)->schema([
                                Select::make('config.color')
                                    ->label(__('Color format'))
                                    ->options(ColorFormat::array()),
                                TextInput::make('config.regex')
                                    ->label(__('Regex'))
                                    ->placeholder(__('Enter a regex pattern')),
                            ]),
                        ]),
                    Tab::make('Rules')
                        ->label(__('Rules'))
                        ->schema([
                            ...parent::getRulesForm(),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
