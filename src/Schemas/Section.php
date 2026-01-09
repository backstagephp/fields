<?php

namespace Backstage\Fields\Schemas;

use Backstage\Fields\Contracts\SchemaContract;
use Backstage\Fields\Models\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section as FilamentSection;
use Filament\Schemas\Components\Utilities\Get;

class Section extends Base implements SchemaContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'heading' => null,
            'description' => null,
            'icon' => null,
            'collapsible' => false,
            'collapsed' => false,
            'compact' => false,
            'aside' => false,
        ];
    }

    public static function make(string $name, Schema $schema): FilamentSection
    {
        $section = FilamentSection::make($schema->name ?? self::getDefaultConfig()['heading'])
            ->description($schema->config['description'] ?? self::getDefaultConfig()['description'])
            ->icon($schema->config['icon'] ?? self::getDefaultConfig()['icon'])
            ->collapsible($schema->config['collapsible'] ?? self::getDefaultConfig()['collapsible'])
            ->collapsed($schema->config['collapsed'] ?? self::getDefaultConfig()['collapsed'])
            ->compact($schema->config['compact'] ?? self::getDefaultConfig()['compact'])
            ->aside($schema->config['aside'] ?? self::getDefaultConfig()['aside']);

        return $section;
    }

    public function getForm(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('config.heading')
                        ->label(__('Heading'))
                        ->live(onBlur: true),
                    TextInput::make('config.description')
                        ->label(__('Description'))
                        ->live(onBlur: true),
                    TextInput::make('config.icon')
                        ->label(__('Icon'))
                        ->placeholder('heroicon-m-')
                        ->live(onBlur: true),
                ]),
            Grid::make(2)
                ->schema([
                    Toggle::make('config.collapsible')
                        ->label(__('Collapsible'))
                        ->inline(false),
                    Toggle::make('config.collapsed')
                        ->label(__('Collapsed'))
                        ->inline(false)
                        ->visible(fn (Get $get): bool => $get('config.collapsible')),
                    Toggle::make('config.compact')
                        ->label(__('Compact'))
                        ->inline(false),
                    Toggle::make('config.aside')
                        ->label(__('Aside'))
                        ->inline(false),
                ]),
        ];
    }
}
