<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList as Input;

class CheckboxList extends Base implements FieldContract
{
    use HasOptions;

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            ...self::getOptionsConfig(),
            'searchable' => false,
            'allowHtml' => false,
            'columns' => 1,
            'gridDirection' => 'row',
            'bulkToggleable' => false,
            'noSearchResultsMessage' => null,
            'searchPrompt' => null,
            'searchDebounce' => null,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(input: Input::make($field->ulid ?? $name), field: $field);

        $input = $input->label($field->name ?? null)
            ->searchable($field->config['searchable'] ?? self::getDefaultConfig()['searchable'])
            ->allowHtml($field->config['allowHtml'] ?? self::getDefaultConfig()['allowHtml'])
            ->options($field->config['options'] ?? self::getDefaultConfig()['options'])
            ->descriptions($field->config['descriptions'] ?? self::getDefaultConfig()['descriptions'])
            ->columns($field->config['columns'] ?? self::getDefaultConfig()['columns'])
            ->gridDirection($field->config['gridDirection'] ?? self::getDefaultConfig()['gridDirection'])
            ->bulkToggleable($field->config['bulkToggleable'] ?? self::getDefaultConfig()['bulkToggleable'])
            ->noSearchResultsMessage($field->config['noSearchResultsMessage'] ?? self::getDefaultConfig()['noSearchResultsMessage'])
            ->searchPrompt($field->config['searchPrompt'] ?? self::getDefaultConfig()['searchPrompt']);

        if (isset($field->config['searchDebounce'])) {
            $input->searchDebounce($field->config['searchDebounce']);
        }

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
                            Forms\Components\Grid::make(3)
                                ->schema([
                                    Forms\Components\Toggle::make('config.searchable')
                                        ->label(__('Searchable'))
                                        ->live(debounce: 250)
                                        ->inline(false),
                                    Forms\Components\Toggle::make('config.allowHtml')
                                        ->label(__('Allow HTML'))
                                        ->inline(false),
                                    Forms\Components\Toggle::make('config.bulkToggleable')
                                        ->label(__('Bulk toggle'))
                                        ->inline(false),
                                ]),
                            self::optionFormFields(),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('config.columns')
                                        ->numeric()
                                        ->minValue(1)
                                        ->label(__('Columns')),
                                    Forms\Components\Select::make('config.gridDirection')
                                        ->options([
                                            'row' => __('Row'),
                                            'column' => __('Column'),
                                        ])
                                        ->label(__('Grid direction')),
                                    //
                                    Forms\Components\TextInput::make('config.noSearchResultsMessage')
                                        ->label(__('No search results message'))
                                        ->visible(fn (Forms\Get $get): bool => $get('config.searchable')),
                                    Forms\Components\TextInput::make('config.searchPrompt')
                                        ->label(__('Search prompt'))
                                        ->visible(fn (Forms\Get $get): bool => $get('config.searchable')),
                                    Forms\Components\TextInput::make('config.searchDebounce')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(100)
                                        ->label(__('Search debounce'))
                                        ->visible(fn (Forms\Get $get): bool => $get('config.searchable')),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
