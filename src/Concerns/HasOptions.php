<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasOptions
{
    public static function addOptionsToInput(mixed $input, mixed $field): mixed
    {
        if (isset($field->config['optionType']) && $field->config['optionType'] === 'relationship') {
            $options = [];

            foreach ($field->config['relations'] as $relation) {
                $resources = config('fields.selectable_resources');
                $resourceClass = collect($resources)->first(function ($resource) use ($relation) {
                    $res = new $resource;
                    $model = $res->getModel();
                    $model = new $model;

                    return $model->getTable() === $relation['resource'];
                });

                if (! $resourceClass) {
                    continue;
                }

                $resource = new $resourceClass;
                $model = $resource->getModel();
                $query = $model::query();

                // Apply filters if they exist
                if (isset($relation['relationValue_filters'])) {
                    foreach ($relation['relationValue_filters'] as $filter) {
                        if (isset($filter['column'], $filter['operator'], $filter['value'])) {
                            $query->where($filter['column'], $filter['operator'], $filter['value']);
                        }
                    }
                }

                $results = $query->get();

                if ($results->isEmpty()) {
                    continue;
                }

                $opts = $results->pluck($relation['relationValue'] ?? 'name', $relation['relationKey'])->toArray();

                if (count($opts) === 0) {
                    continue;
                }

                $options[] = $opts;
            }

            if (! empty($options)) {
                $options = array_merge(...$options);
                $input->options($options);
            }
        }

        if (isset($field->config['optionType']) && $field->config['optionType'] === 'array') {
            $input->options($field->config['options']);
        }

        return $input;
    }

    public static function getOptionsConfig(): array
    {
        return [
            'optionType' => null,
            'options' => [],
            'descriptions' => [],
            'relations' => [],
            'contentType' => null,
            'relationKey' => null,
            'relationValue' => null,
        ];
    }

    public function optionFormFields(): Fieldset
    {
        return Forms\Components\Fieldset::make('Options')
            ->columnSpanFull()
            ->label(__('Options'))
            ->schema([
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('config.optionType')
                            ->options([
                                'array' => __('Array'),
                                'relationship' => __('Relationship'),
                            ])
                            ->searchable()
                            ->live(onBlur: true)
                            ->reactive()
                            ->label(__('Type')),
                        // Array options
                        Forms\Components\KeyValue::make('config.options')
                            ->label(__('Options'))
                            ->columnSpanFull()
                            ->visible(fn (Forms\Get $get): bool => $get('config.optionType') == 'array')
                            ->required(fn (Forms\Get $get): bool => $get('config.optionType') == 'array'),
                        // Relationship options
                        Repeater::make('config.relations')
                            ->label(__('Relations'))
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        Forms\Components\Select::make('resource')
                                            ->label(__('Resource'))
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull()
                                            ->live(debounce: 250)
                                            ->afterStateUpdated(function (Forms\Set $set, ?string $state) {
                                                $resources = config('fields.selectable_resources');
                                                $resourceClass = collect($resources)->first(function ($resource) use ($state) {
                                                    $res = new $resource;
                                                    $model = $res->getModel();
                                                    $model = new $model;

                                                    return $model->getTable() === $state;
                                                });

                                                if (! $resourceClass) {
                                                    return;
                                                }

                                                $resource = new $resourceClass;
                                                $model = $resource->getModel();
                                                $model = new $model;

                                                // Get all column names from the table
                                                $columns = Schema::getColumnListing($model->getTable());

                                                // Create options array with column names
                                                $columnOptions = collect($columns)->mapWithKeys(function ($column) {
                                                    return [$column => Str::title($column)];
                                                })->toArray();

                                                $set('relationValue', null);
                                                $set('relationValue_options', $columnOptions);
                                            })
                                            ->options(function () {
                                                $resources = config('fields.selectable_resources');

                                                return collect($resources)->map(function ($resource) {
                                                    $res = new $resource;
                                                    $model = $res->getModel();
                                                    $model = new $model;

                                                    return [
                                                        $model->getTable() => Str::title($model->getTable()),
                                                    ];
                                                })
                                                    ->collapse()
                                                    ->toArray();
                                            })
                                            ->noSearchResultsMessage(__('No types found'))
                                            ->required(fn (Forms\Get $get): bool => $get('config.optionType') == 'relationship'),
                                        Forms\Components\Hidden::make('relationKey')
                                            ->default('ulid')
                                            ->label(__('Key'))
                                            ->required(fn (Forms\Get $get): bool => $get('config.optionType') == 'relationship'),
                                        Forms\Components\Repeater::make('relationValue_filters')
                                            ->label(__('Filters'))
                                            ->visible(fn (Forms\Get $get): bool => ! empty($get('resource')))
                                            ->schema([
                                                Forms\Components\Grid::make(3)
                                                    ->schema([
                                                        Forms\Components\Select::make('column')
                                                            ->options(fn (\Filament\Forms\Get $get) => $get('../../relationValue_options') ?? [
                                                                'slug' => __('Slug'),
                                                                'name' => __('Name'),
                                                            ])
                                                            ->live()
                                                            ->label(__('Column')),
                                                        Forms\Components\Select::make('operator')
                                                            ->options([
                                                                '=' => __('Equal'),
                                                                '!=' => __('Not equal'),
                                                                '>' => __('Greater than'),
                                                                '<' => __('Less than'),
                                                                '>=' => __('Greater than or equal to'),
                                                                '<=' => __('Less than or equal to'),
                                                                'LIKE' => __('Like'),
                                                                'NOT LIKE' => __('Not like'),
                                                            ])
                                                            ->label(__('Operator')),
                                                        Forms\Components\TextInput::make('value')
                                                            ->datalist(function (Forms\Get $get) {
                                                                $resource = $get('../../resource');
                                                                $column = $get('column');

                                                                if (! $resource || ! $column) {
                                                                    return [];
                                                                }

                                                                $resources = config('fields.selectable_resources');
                                                                $resourceClass = collect($resources)->first(function ($r) use ($resource) {
                                                                    $res = new $r;
                                                                    $model = $res->getModel();
                                                                    $model = new $model;

                                                                    return $model->getTable() === $resource;
                                                                });

                                                                if (! $resourceClass) {
                                                                    return [];
                                                                }

                                                                $resource = new $resourceClass;
                                                                $model = $resource->getModel();

                                                                return $model::query()
                                                                    ->select($column)
                                                                    ->distinct()
                                                                    ->pluck($column)
                                                                    ->toArray();
                                                            })
                                                            ->label(__('Value')),
                                                    ]),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                            ])
                            ->visible(fn (Forms\Get $get): bool => $get('config.optionType') == 'relationship')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
