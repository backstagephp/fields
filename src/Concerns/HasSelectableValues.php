<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait HasSelectableValues
{
    protected static function resolveResourceModel(string $tableName): ?object
    {
        $resources = config('backstage.fields.selectable_resources');
        $resourceClass = collect($resources)->first(function ($resource) use ($tableName) {
            $res = new $resource;
            $model = $res->getModel();
            $model = new $model;

            return $model->getTable() === $tableName;
        });

        if (! $resourceClass) {
            return null;
        }

        $resource = new $resourceClass;
        $model = $resource->getModel();

        return new $model;
    }

    protected static function addValuesToInput(mixed $input, mixed $field, string $type, string $method): mixed
    {
        if (isset($field->config[$type]) && $field->config[$type] === 'relationship') {
            $options = [];

            foreach ($field->config['relations'] as $relation) {
                if (! isset($relation['resource'])) {
                    continue;
                }

                $model = static::resolveResourceModel($relation['resource']);

                if (! $model) {
                    continue;
                }

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
                $input->$method($options);
            }
        }

        if (isset($field->config[$type]) && $field->config[$type] === 'array') {
            $input->$method($field->config['options']);
        }

        return $input;
    }

    protected static function getSelectableValuesConfig(): array
    {
        return [
            'options' => [],
            'descriptions' => [],
            'relations' => [],
            'contentType' => null,
            'relationKey' => null,
            'relationValue' => null,
        ];
    }

    protected function selectableValuesFormFields(string $type, string $label, string $arrayComponent): Fieldset
    {
        return Fieldset::make($label)
            ->columnSpanFull()
            ->label(__($label))
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make("config.{$type}")
                            ->options([
                                'array' => __('Array'),
                                'relationship' => __('Relationship'),
                            ])
                            ->searchable()
                            ->live(onBlur: true)
                            ->reactive()
                            ->label(__('Type')),
                        // Array options
                        $arrayComponent::make('config.options')
                            ->label(__('Options'))
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => $get("config.{$type}") == 'array')
                            ->required(fn (Get $get): bool => $get("config.{$type}") == 'array'),
                        // Relationship options
                        Repeater::make('config.relations')
                            ->label(__('Relations'))
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        Select::make('resource')
                                            ->label(__('Resource'))
                                            ->searchable()
                                            ->preload()
                                            ->columnSpanFull()
                                            ->live(debounce: 250)
                                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                                $model = static::resolveResourceModel($state);

                                                if (! $model) {
                                                    return;
                                                }

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
                                                $resources = config('backstage.fields.selectable_resources');

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
                                            ->required(fn (Get $get): bool => $get("config.{$type}") == 'relationship'),
                                        Select::make('relationValue')
                                            ->label(__('Column'))
                                            ->helperText(__('The column to use as name for the options'))
                                            ->options(fn (Get $get) => $get('relationValue_options') ?? [])
                                            ->searchable()
                                            ->visible(fn (Get $get): bool => ! empty($get('resource')))
                                            ->required(fn (Get $get): bool => ! empty($get('resource'))),
                                        Hidden::make('relationKey')
                                            ->default('ulid')
                                            ->label(__('Key'))
                                            ->required(fn (Get $get): bool => $get("config.{$type}") == 'relationship'),
                                        Repeater::make('relationValue_filters')
                                            ->label(__('Filters'))
                                            ->visible(fn (Get $get): bool => ! empty($get('resource')))
                                            ->schema([
                                                Grid::make(3)
                                                    ->schema([
                                                        Select::make('column')
                                                            ->options(fn (Get $get) => $get('../../relationValue_options') ?? [
                                                                'slug' => __('Slug'),
                                                                'name' => __('Name'),
                                                            ])
                                                            ->live()
                                                            ->label(__('Column')),
                                                        Select::make('operator')
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
                                                        TextInput::make('value')
                                                            ->datalist(function (Get $get) {
                                                                $resource = $get('../../resource');
                                                                $column = $get('column');

                                                                if (! $resource || ! $column) {
                                                                    return [];
                                                                }

                                                                $model = static::resolveResourceModel($resource);

                                                                if (! $model) {
                                                                    return [];
                                                                }

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
                            ->visible(fn (Get $get): bool => $get("config.{$type}") == 'relationship')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
