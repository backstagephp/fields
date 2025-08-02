<?php

namespace Backstage\Fields\Fields\FormSchemas;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Filament\Forms;

class ConditionalLogicSchema
{
    public static function make(): array
    {
        return [
            Forms\Components\Fieldset::make('Conditional logic')
                ->schema([
                    Forms\Components\Select::make('config.conditionalField')
                        ->label(__('Field'))
                        ->placeholder(__('Select a field'))
                        ->searchable()
                        ->live()
                        ->options(function ($livewire) {
                            // Try to get the current field's ULID from the form state
                            $excludeUlid = null;
                            if (method_exists($livewire, 'getMountedTableActionRecord')) {
                                $record = $livewire->getMountedTableActionRecord();
                                if ($record && isset($record->ulid)) {
                                    $excludeUlid = $record->ulid;
                                }
                            }

                            return FieldOptionsHelper::getFieldOptions($livewire, $excludeUlid);
                        }),
                    Forms\Components\Select::make('config.conditionalOperator')
                        ->label(__('Condition'))
                        ->options([
                            'equals' => __('Equals'),
                            'not_equals' => __('Does not equal'),
                            'contains' => __('Contains'),
                            'not_contains' => __('Does not contain'),
                            'starts_with' => __('Starts with'),
                            'ends_with' => __('Ends with'),
                            'is_empty' => __('Is empty'),
                            'is_not_empty' => __('Is not empty'),
                        ])
                        ->visible(fn (Forms\Get $get): bool => filled($get('config.conditionalField'))),
                    Forms\Components\TextInput::make('config.conditionalValue')
                        ->label(__('Value'))
                        ->visible(
                            fn (Forms\Get $get): bool => filled($get('config.conditionalField')) &&
                                ! in_array($get('config.conditionalOperator'), ['is_empty', 'is_not_empty'])
                        ),
                    Forms\Components\Select::make('config.conditionalAction')
                        ->label(__('Action'))
                        ->options([
                            'show' => __('Show field'),
                            'hide' => __('Hide field'),
                            'required' => __('Make required'),
                            'not_required' => __('Make not required'),
                        ])
                        ->visible(fn (Forms\Get $get): bool => filled($get('config.conditionalField'))),
                ])->columns(3),
        ];
    }
} 