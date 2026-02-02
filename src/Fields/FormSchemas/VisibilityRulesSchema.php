<?php

namespace Backstage\Fields\Fields\FormSchemas;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Backstage\Fields\Fields\Helpers\ModelAttributeHelper;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;

class VisibilityRulesSchema
{
    public static function make(): array
    {
        return [
            Section::make('Show/Hide Rules')
                ->collapsible()
                ->collapsed(false)
                ->columnSpanFull()
                ->compact(true)
                ->description(__('Control when this field appears based on other field values or record properties'))
                ->schema([
                    Repeater::make('config.visibilityRules')
                        ->hiddenLabel()
                        ->schema([
                            Select::make('logic')
                                ->label(__('When to show this field'))
                                ->options([
                                    'AND' => __('All conditions must be met'),
                                    'OR' => __('Any condition can be met'),
                                ])
                                ->default('AND')
                                ->required(),
                            Repeater::make('conditions')
                                ->hiddenLabel()
                                ->schema([
                                    Grid::make(2)
                                        ->columnSpanFull()
                                        ->schema([
                                            Select::make('source')
                                                ->label(__('Check'))
                                                ->options([
                                                    'field' => __('Another field'),
                                                    'model_attribute' => __('Record property'),
                                                ])
                                                ->default('field')
                                                ->live()
                                                ->required()
                                                ->columnSpan(1),

                                            Select::make('field')
                                                ->label(__('Which field'))
                                                ->placeholder(__('Choose a field'))
                                                ->searchable()
                                                ->live()
                                                ->visible(fn (Get $get): bool => $get('source') === 'field')
                                                ->options(function ($livewire, $record) {
                                                    $excludeUlid = null;

                                                    // Try to get from current record (works in nested forms)
                                                    if ($record && isset($record->ulid)) {
                                                        $excludeUlid = $record->ulid;
                                                    }
                                                    // Fallback to table action record
                                                    elseif (method_exists($livewire, 'getMountedTableActionRecord')) {
                                                        $actionRecord = $livewire->getMountedTableActionRecord();
                                                        if ($actionRecord && isset($actionRecord->ulid)) {
                                                            $excludeUlid = $actionRecord->ulid;
                                                        }
                                                    }

                                                    return FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);
                                                })
                                                ->required(fn (Get $get): bool => $get('source') === 'field')
                                                ->columnSpan(1),

                                            Select::make('model')
                                                ->label(__('Record type'))
                                                ->placeholder(__('Choose record type'))
                                                ->searchable()
                                                ->visible(fn (Get $get): bool => $get('source') === 'model_attribute')
                                                ->options(function () {
                                                    return ModelAttributeHelper::getAvailableModels();
                                                })
                                                ->live()
                                                ->required(fn (Get $get): bool => $get('source') === 'model_attribute')
                                                ->columnSpan(1),

                                            Placeholder::make('no_models_warning')
                                                ->label('')
                                                ->content(__('⚠️ No record types configured. Add models to the visibility_models array in config/backstage/fields.php to use this feature.'))
                                                ->extraAttributes([
                                                    'class' => 'text-warning-600 dark:text-warning-400 text-sm',
                                                ])
                                                ->visible(function (Get $get): bool {
                                                    if ($get('source') !== 'model_attribute') {
                                                        return false;
                                                    }

                                                    $models = ModelAttributeHelper::getAvailableModels();
                                                    return empty($models);
                                                })
                                                ->columnSpanFull(),
                                        ]),

                                    Grid::make(3)
                                        ->columnSpanFull()
                                        ->schema([
                                            Select::make('property')
                                                ->label(__('Property'))
                                                ->placeholder(function (Get $get): string {
                                                    return $get('source') === 'field'
                                                        ? __('Choose a field property')
                                                        : __('Choose a property');
                                                })
                                                ->searchable()
                                                ->visible(function (Get $get, $record): bool {
                                                    if ($get('source') === 'model_attribute') {
                                                        return true;
                                                    }

                                                    if ($get('source') === 'field') {
                                                        $fieldUlid = $get('field');
                                                        if (! $fieldUlid) {
                                                            return false;
                                                        }

                                                        // Get current field context
                                                        $currentField = null;
                                                        if ($record && isset($record->ulid)) {
                                                            $currentField = $record;
                                                        }

                                                        // Check if the field has accessible children/properties
                                                        $properties = FieldOptionsHelper::getFieldProperties($fieldUlid, $currentField);
                                                        return ! empty($properties);
                                                    }

                                                    return false;
                                                })
                                                ->options(function (Get $get, $livewire, $record) {
                                                    if ($get('source') === 'field') {
                                                        $fieldUlid = $get('field');

                                                        if (! $fieldUlid) {
                                                            return [];
                                                        }

                                                        // Get current field context
                                                        $currentField = null;
                                                        if ($record && isset($record->ulid)) {
                                                            $currentField = $record;
                                                        }

                                                        return FieldOptionsHelper::getFieldProperties($fieldUlid, $currentField);
                                                    }

                                                    if ($get('source') === 'model_attribute') {
                                                        $modelClass = $get('model');
                                                        if (! $modelClass) {
                                                            return [];
                                                        }

                                                        return ModelAttributeHelper::getModelAttributesForModel($modelClass);
                                                    }

                                                    return [];
                                                })
                                                ->required(
                                                    fn (Get $get): bool => $get('source') === 'model_attribute' && $get('model')
                                                )
                                                ->columnSpan(1),

                                            Select::make('operator')
                                                ->label(__('Is'))
                                                ->live()
                                                ->options([
                                                    'equals' => __('equal to'),
                                                    'not_equals' => __('not equal to'),
                                                    'contains' => __('containing'),
                                                    'not_contains' => __('not containing'),
                                                    'starts_with' => __('starting with'),
                                                    'ends_with' => __('ending with'),
                                                    'is_empty' => __('empty'),
                                                    'is_not_empty' => __('not empty'),
                                                    'greater_than' => __('greater than'),
                                                    'less_than' => __('less than'),
                                                    'greater_than_or_equal' => __('greater than or equal to'),
                                                    'less_than_or_equal' => __('less than or equal to'),
                                                    'in' => __('one of'),
                                                    'not_in' => __('not one of'),
                                                ])
                                                ->required()
                                                ->columnSpan(1),

                                            TextInput::make('value')
                                                ->label(__('This value'))
                                                ->placeholder(__('Enter the value to check'))
                                                ->visible(fn (Get $get): bool => ! in_array($get('operator'), ['is_empty', 'is_not_empty']))
                                                ->columnSpan(1),
                                        ]),
                                ])
                                ->collapsible()
                                ->itemLabel(function (array $state): ?string {
                                    if (isset($state['source']) && $state['source'] === 'model_attribute') {
                                        if (isset($state['model']) && isset($state['property'])) {
                                            $modelName = class_basename($state['model']);
                                            $attributeName = ucfirst(str_replace('_', ' ', $state['property']));

                                            return "{$modelName} {$attributeName}";
                                        }

                                        return 'Record property';
                                    }

                                    if (isset($state['source']) && $state['source'] === 'field') {
                                        // Try property first (for fields with children), then fallback to field
                                        $fieldUlid = $state['property'] ?? $state['field'] ?? null;
                                        if ($fieldUlid) {
                                            $field = Field::find($fieldUlid);
                                            return $field->name ?? null;
                                        }
                                    }

                                    return null;
                                })
                                ->defaultItems(1)
                                ->columns(3)
                                ->reorderableWithButtons()
                                ->columnSpanFull(),
                        ])
                        ->collapsible()
                        ->itemLabel(fn (array $state): string => __('Show/Hide Rule'))
                        ->defaultItems(0)
                        ->maxItems(1)
                        ->reorderableWithButtons()
                        ->columnSpanFull(),
                ]),
        ];
    }
}
