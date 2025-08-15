<?php

namespace Backstage\Fields\Fields\FormSchemas;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class VisibilityRulesSchema
{
    public static function make(): array
    {
        return [
            Section::make('Visibility rules')
                ->collapsible()
                ->collapsed(false)
                ->compact(true)
                ->description(__('Show or hide this field based on the value of another field'))
                ->schema([
                    Repeater::make('config.visibilityRules')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('logic')
                                ->label(__('Logic'))
                                ->options([
                                    'AND' => __('All conditions must be true (AND)'),
                                    'OR' => __('Any condition can be true (OR)'),
                                ])
                                ->default('AND')
                                ->required(),
                            Repeater::make('conditions')
                                ->hiddenLabel()
                                ->schema([
                                    Select::make('field')
                                        ->label(__('Field'))
                                        ->placeholder(__('Select a field'))
                                        ->searchable()
                                        ->live()
                                        ->options(function ($livewire) {
                                            $excludeUlid = null;
                                            if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                                $record = $livewire->getMountedTableActionRecord();
                                                if ($record && isset($record->ulid)) {
                                                    $excludeUlid = $record->ulid;
                                                }
                                            }

                                            return FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);
                                        })
                                        ->required(),
                                    Select::make('operator')
                                        ->label(__('Condition'))
                                        ->live()
                                        ->options([
                                            'equals' => __('Equals'),
                                            'not_equals' => __('Does not equal'),
                                            'contains' => __('Contains'),
                                            'not_contains' => __('Does not contain'),
                                            'starts_with' => __('Starts with'),
                                            'ends_with' => __('Ends with'),
                                            'is_empty' => __('Is empty'),
                                            'is_not_empty' => __('Is not empty'),
                                            'greater_than' => __('Greater than'),
                                            'less_than' => __('Less than'),
                                            'greater_than_or_equal' => __('Greater than or equal'),
                                            'less_than_or_equal' => __('Less than or equal'),
                                            'in' => __('In list'),
                                            'not_in' => __('Not in list'),
                                        ])
                                        ->required(),
                                    TextInput::make('value')
                                        ->label(__('Value'))
                                        ->visible(fn (Get $get): bool => ! in_array($get('operator'), ['is_empty', 'is_not_empty'])),
                                ])
                                ->collapsible()
                                ->itemLabel(function (array $state): ?string {

                                    if (isset($state['field'])) {
                                        $field = Field::find($state['field']);

                                        return $field->name ?? null;
                                    }

                                    return null;
                                })
                                ->defaultItems(1)
                                ->columns(3)
                                ->reorderableWithButtons()
                                ->columnSpanFull(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => __('Visibility Rule'))
                        ->defaultItems(0)
                        ->maxItems(1)
                        ->reorderableWithButtons()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
