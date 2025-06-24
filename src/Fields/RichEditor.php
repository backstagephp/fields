<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ToolbarButton;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\RichEditor as Input;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class RichEditor extends Base implements FieldContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'toolbarButtons' => ['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'],
            'disableToolbarButtons' => [],
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? null)
            ->toolbarButtons($field->config['toolbarButtons'] ?? self::getDefaultConfig()['toolbarButtons'])
            ->disableToolbarButtons($field->config['disableToolbarButtons'] ?? self::getDefaultConfig()['disableToolbarButtons']);

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
                            Grid::make(2)
                                ->schema([
                                    Select::make('config.toolbarButtons')
                                        ->label(__('Toolbar buttons'))
                                        ->default(['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'])
                                        ->default(ToolbarButton::array()) // Not working in Filament yet.
                                        ->multiple()
                                        ->options(ToolbarButton::array())
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
