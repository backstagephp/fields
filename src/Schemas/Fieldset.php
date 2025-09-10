<?php

namespace Backstage\Fields\Schemas;

use Backstage\Fields\Contracts\SchemaContract;
use Backstage\Fields\Models\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset as FilamentFieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;

class Fieldset extends Base implements SchemaContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'label' => null,
            'columns' => 1,
            'collapsible' => false,
            'collapsed' => false,
        ];
    }

    public static function make(string $name, Schema $schema): FilamentFieldset
    {
        $fieldset = FilamentFieldset::make($schema->config['label'] ?? self::getDefaultConfig()['label'])
            ->columns($schema->config['columns'] ?? self::getDefaultConfig()['columns'])
            ->collapsible($schema->config['collapsible'] ?? self::getDefaultConfig()['collapsible'])
            ->collapsed($schema->config['collapsed'] ?? self::getDefaultConfig()['collapsed']);

        return $fieldset;
    }

    public function getForm(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('config.label')
                        ->label(__('Label'))
                        ->live(onBlur: true),
                    TextInput::make('config.columns')
                        ->label(__('Columns'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->default(1)
                        ->live(onBlur: true),
                ]),
            Grid::make(2)
                ->schema([
                    Toggle::make('config.collapsible')
                        ->label(__('Collapsible'))
                        ->inline(false)
                        ->live(),
                    Toggle::make('config.collapsed')
                        ->label(__('Collapsed'))
                        ->inline(false)
                        ->visible(fn (Get $get): bool => $get('config.collapsible')),
                ]),
        ];
    }
}
