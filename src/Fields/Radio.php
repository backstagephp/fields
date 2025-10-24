<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Radio as Input;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class Radio extends Base implements FieldContract
{
    use HasOptions;

    public function getFieldType(): ?string
    {
        return 'radio';
    }

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
        $input = self::applyDefaultSettings(Input::make($name), $field);

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
                            Toggle::make('config.inline')
                                ->label(__('Inline')),
                            Toggle::make('config.inlineLabel')
                                ->label(__('Inline label')),
                            Toggle::make('config.boolean')
                                ->label(__('Boolean')),
                            self::optionFormFields(),
                        ])->columns(3),
                    Tab::make('Rules')
                        ->label(__('Rules'))
                        ->schema([
                            ...parent::getRulesForm(),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
