<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Textarea as Input;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class Textarea extends Base implements FieldContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'readOnly' => false,
            'autosize' => false,
            'rows' => null,
            'cols' => null,
            'minLength' => null,
            'maxLength' => null,
            'length' => null,
            'placeholder' => null,
        ];
    }

    public static function make(string $name, Field $field): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name)
            ->readOnly($field->config['readOnly'] ?? self::getDefaultConfig()['readOnly'])
            ->placeholder($field->config['placeholder'] ?? self::getDefaultConfig()['placeholder'])
            ->autosize($field->config['autosize'] ?? self::getDefaultConfig()['autosize'])
            ->rows($field->config['rows'] ?? self::getDefaultConfig()['rows'])
            ->cols($field->config['cols'] ?? self::getDefaultConfig()['cols'])
            ->minLength($field->config['minLength'] ?? self::getDefaultConfig()['minLength'])
            ->maxLength($field->config['maxLength'] ?? self::getDefaultConfig()['maxLength'])
            ->length($field->config['length'] ?? self::getDefaultConfig()['length']);

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
                            Toggle::make('config.readOnly')
                                ->label(__('Read only')),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('config.autosize')
                                        ->default(false)
                                        ->label(__('Auto size')),
                                    TextInput::make('config.rows')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Rows')),
                                    TextInput::make('config.cols')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Cols')),
                                    TextInput::make('config.minLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Minimum length')),
                                    TextInput::make('config.maxLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Maximum length')),
                                    TextInput::make('config.length')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Length')),
                                    TextInput::make('config.placeholder')
                                        ->label(__('Placeholder')),
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

    public function getFieldType(): ?string
    {
        return 'textarea';
    }
}
