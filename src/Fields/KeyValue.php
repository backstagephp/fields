<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Forms\Components\KeyValue as Input;


class KeyValue extends Base implements FieldContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'addActionLabel' => __('Add row'),
            'keyLabel' => __('Key'),
            'valueLabel' => __('Value'),
            'reorderable' => false,
        ];
    }

    public static function make(string $name, Field $field): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->addActionLabel($field->config['addActionLabel'] ?? self::getDefaultConfig()['addActionLabel'])
            ->keyLabel($field->config['keyLabel'] ?? self::getDefaultConfig()['keyLabel'])
            ->valueLabel($field->config['valueLabel'] ?? self::getDefaultConfig()['valueLabel'])
            ->reorderable($field->config['reorderable'] ?? self::getDefaultConfig()['reorderable']);

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
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('config.addActionLabel')
                                    ->label(__('Add action label')),
                                Forms\Components\TextInput::make('config.keyLabel')
                                    ->label(__('Key label')),
                                Forms\Components\TextInput::make('config.valueLabel')
                                    ->label(__('Value label')),
                                Forms\Components\Toggle::make('config.reorderable')
                                    ->label(__('Reorderable'))
                                    ->inline(false),
                            ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
