<?php

namespace Backstage\Fields\Schemas;

use Backstage\Fields\Contracts\SchemaContract;
use Backstage\Fields\Models\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid as FilamentGrid;
use Filament\Schemas\Components\Utilities\Get;

class Grid extends Base implements SchemaContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'columns' => 1,
            'responsive' => false,
            'columnsSm' => null,
            'columnsMd' => null,
            'columnsLg' => null,
            'columnsXl' => null,
            'columns2xl' => null,
            'gap' => null,
        ];
    }

    public static function make(string $name, Schema $schema): FilamentGrid
    {
        $columns = $schema->config['columns'] ?? self::getDefaultConfig()['columns'];

        if ($schema->config['responsive'] ?? self::getDefaultConfig()['responsive']) {
            $responsiveColumns = [];

            if (isset($schema->config['columnsSm'])) {
                $responsiveColumns['sm'] = $schema->config['columnsSm'];
            }
            if (isset($schema->config['columnsMd'])) {
                $responsiveColumns['md'] = $schema->config['columnsMd'];
            }
            if (isset($schema->config['columnsLg'])) {
                $responsiveColumns['lg'] = $schema->config['columnsLg'];
            }
            if (isset($schema->config['columnsXl'])) {
                $responsiveColumns['xl'] = $schema->config['columnsXl'];
            }
            if (isset($schema->config['columns2xl'])) {
                $responsiveColumns['2xl'] = $schema->config['columns2xl'];
            }

            if (! empty($responsiveColumns)) {
                $responsiveColumns['default'] = $columns;
                $columns = $responsiveColumns;
            }
        }

        $grid = FilamentGrid::make($columns);

        if (isset($schema->config['gap'])) {
            $grid->gap($schema->config['gap']);
        }

        return $grid;
    }

    public function getForm(): array
    {
        return [
            FilamentGrid::make(2)
                ->schema([
                    TextInput::make('config.columns')
                        ->label(__('Columns'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->default(1)
                        ->live(onBlur: true),
                    Toggle::make('config.responsive')
                        ->label(__('Responsive'))
                        ->inline(false)
                        ->live(),
                ]),
            FilamentGrid::make(2)
                ->schema([
                    TextInput::make('config.columnsSm')
                        ->label(__('Columns (SM)'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->visible(fn (Get $get): bool => $get('config.responsive')),
                    TextInput::make('config.columnsMd')
                        ->label(__('Columns (MD)'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->visible(fn (Get $get): bool => $get('config.responsive')),
                    TextInput::make('config.columnsLg')
                        ->label(__('Columns (LG)'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->visible(fn (Get $get): bool => $get('config.responsive')),
                    TextInput::make('config.columnsXl')
                        ->label(__('Columns (XL)'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->visible(fn (Get $get): bool => $get('config.responsive')),
                    TextInput::make('config.columns2xl')
                        ->label(__('Columns (2XL)'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12)
                        ->visible(fn (Get $get): bool => $get('config.responsive')),
                ]),
            TextInput::make('config.gap')
                ->label(__('Gap'))
                ->placeholder('4')
                ->helperText(__('Spacing between grid items (e.g., 4, 6, 8)')),
        ];
    }
}
