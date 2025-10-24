<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\KeyValue as Input;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class KeyValue extends Base implements FieldContract
{
    public function getFieldType(): ?string
    {
        return 'key-value';
    }

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
                                TextInput::make('config.addActionLabel')
                                    ->label(__('Add action label')),
                                TextInput::make('config.keyLabel')
                                    ->label(__('Key label')),
                                TextInput::make('config.valueLabel')
                                    ->label(__('Value label')),
                                Toggle::make('config.reorderable')
                                    ->label(__('Reorderable')),
                            ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
