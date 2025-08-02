<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Forms\Components\Textarea as Input;

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
                            Forms\Components\Toggle::make('config.readOnly')
                                ->label(__('Read only'))
                                ->inline(false),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('config.placeholder')
                                        ->label(__('Placeholder')),
                                    Forms\Components\TextInput::make('config.minLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Minimum length')),
                                    Forms\Components\TextInput::make('config.maxLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Maximum length')),
                                    Forms\Components\TextInput::make('config.rows')
                                        ->numeric()
                                        ->minValue(1)
                                        ->default(3)
                                        ->label(__('Rows')),
                                    Forms\Components\TextInput::make('config.cols')
                                        ->numeric()
                                        ->minValue(1)
                                        ->label(__('Columns')),
                                    Forms\Components\Toggle::make('config.autosize')
                                        ->label(__('Auto-size'))
                                        ->inline(false),
                                    Forms\Components\Toggle::make('config.autofocus')
                                        ->label(__('Auto-focus'))
                                        ->inline(false),
                                    Forms\Components\Toggle::make('config.spellcheck')
                                        ->label(__('Spell check'))
                                        ->inline(false),
                                    Forms\Components\Toggle::make('config.wrap')
                                        ->label(__('Wrap text'))
                                        ->inline(false),
                                ]),
                        ]),
                    Forms\Components\Tabs\Tab::make('Rules')
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
