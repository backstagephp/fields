<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\CheckboxList as Input;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class CheckboxList extends Base implements FieldContract
{
    use HasOptions;

    public function getFieldType(): ?string
    {
        return 'checkbox-list';
    }

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
        $input = self::applyDefaultSettings(Input::make($name), $field);

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
                            Grid::make(3)
                                ->schema([
                                    Toggle::make('config.searchable')
                                        ->label(__('Searchable'))
                                        ->live(debounce: 250),
                                    Toggle::make('config.allowHtml')
                                        ->label(__('Allow HTML')),
                                    Toggle::make('config.bulkToggleable')
                                        ->label(__('Bulk toggle')),
                                ]),
                            self::optionFormFields(),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('config.columns')
                                        ->numeric()
                                        ->minValue(1)
                                        ->label(__('Columns')),
                                    Select::make('config.gridDirection')
                                        ->options([
                                            'row' => __('Row'),
                                            'column' => __('Column'),
                                        ])
                                        ->label(__('Grid direction')),
                                    //
                                    TextInput::make('config.noSearchResultsMessage')
                                        ->label(__('No search results message'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.searchPrompt')
                                        ->label(__('Search prompt'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.searchDebounce')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(100)
                                        ->label(__('Search debounce'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                ]),
                        ]),
                    Tab::make('Rules')
                        ->label(__('Rules'))
                        ->schema([
                            ...parent::getRulesForm(),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
