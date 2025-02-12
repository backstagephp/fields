<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Forms\Components\Radio as Input;

class Radio extends Base implements FieldContract
{
    use HasOptions;

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            ...self::getOptionsConfig(),
            'inline' => false,
            'inlineLabel' => false,
            'boolean' => false,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(input: Input::make($name), field: $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->inline($field->config['inline'] ?? self::getDefaultConfig()['inline'])
            ->inlineLabel($field->config['inlineLabel'] ?? self::getDefaultConfig()['inlineLabel'])
            ->boolean($field->config['boolean'] ?? self::getDefaultConfig()['boolean']);

        $input = self::addOptionsToInput($input, $field);

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
                            Forms\Components\Toggle::make('config.inline')
                                ->label(__('Inline'))
                                ->inline(false),
                            Forms\Components\Toggle::make('config.inlineLabel')
                                ->label(__('Inline label'))
                                ->inline(false),
                            Forms\Components\Toggle::make('config.boolean')
                                ->label(__('Boolean'))
                                ->inline(false),
                            self::optionFormFields(),
                        ])->columns(3),
                ])->columnSpanFull(),
        ];
    }
}
