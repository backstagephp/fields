<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasAffixes;
use Backstage\Fields\Concerns\HasDatalist;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as Input;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class Text extends Base implements FieldContract
{
    use HasAffixes;
    use HasDatalist;

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            ...self::getAffixesConfig(),
            ...self::getDatalistConfig(),
            'readOnly' => false,
            'autocapitalize' => 'none',
            'autocomplete' => null,
            'placeholder' => null,
            'mask' => null,
            'minLength' => null,
            'maxLength' => null,
            'type' => 'text',
            'step' => null,
            'inputMode' => null,
            'telRegex' => null,
            'revealable' => false,
            'dynamic_mode' => 'none',
            'dynamic_source_field' => null,
            'dynamic_relation_column' => null,
            'dynamic_formula' => null,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->readOnly($field->config['readOnly'] ?? self::getDefaultConfig()['readOnly'])
            ->placeholder($field->config['placeholder'] ?? self::getDefaultConfig()['placeholder'])
            ->mask($field->config['mask'] ?? self::getDefaultConfig()['mask'])
            ->minLength($field->config['minLength'] ?? self::getDefaultConfig()['minLength'])
            ->maxLength($field->config['maxLength'] ?? self::getDefaultConfig()['maxLength'])
            ->type($field->config['type'] ?? self::getDefaultConfig()['type'])
            ->step($field->config['step'] ?? self::getDefaultConfig()['step'])
            ->inputMode($field->config['inputMode'] ?? self::getDefaultConfig()['inputMode'])
            ->telRegex($field->config['telRegex'] ?? self::getDefaultConfig()['telRegex'])
            ->revealable($field->config['revealable'] ?? self::getDefaultConfig()['revealable']);

        if ($field->config && $field->config['type'] === 'email') {
            $input->email();
        }

        if ($field->config && $field->config['type'] === 'tel') {
            $input->tel();
        }

        if ($field->config && $field->config['type'] === 'url') {
            $input->url();
        }

        if ($field->config && $field->config['type'] === 'password') {
            $input->password();
        }

        if ($field->config && $field->config['type'] === 'numeric') {
            $input->numeric();
        }

        if ($field->config && $field->config['type'] === 'integer') {
            $input->integer();
        }

        $input = self::applyDynamicSettings($input, $field);
        $input = self::addAffixesToInput($input, $field);
        $input = self::addDatalistToInput($input, $field);

        return $input;
    }

    public static function calculateDynamicValue(Field $field, $sourceValue, ?Get $get = null)
    {
        $mode = $field->config['dynamic_mode'] ?? 'none';

        if ($mode === 'relation') {
            if (empty($sourceValue)) {
                return null;
            }

            $sourceUlid = $field->config['dynamic_source_field'] ?? null;
            if (! $sourceUlid) {
                return null;
            }

            $sourceField = \Backstage\Fields\Models\Field::find($sourceUlid);
            if (! $sourceField) {
                return null;
            }

            $relations = $sourceField->config['relations'] ?? [];
            $relationConfig = reset($relations);
            if (! $relationConfig || empty($relationConfig['resource'])) {
                return null;
            }

            $modelInstance = \Backstage\Fields\Fields\Select::resolveResourceModel($relationConfig['resource']);
            if (! $modelInstance) {
                return null;
            }

            $relatedRecord = $modelInstance::find($sourceValue);
            if (! $relatedRecord) {
                return null;
            }

            $targetColumn = $field->config['dynamic_relation_column'] ?? null;
            if ($targetColumn && isset($relatedRecord->$targetColumn)) {
                return $relatedRecord->$targetColumn;
            }
        }

        if ($mode === 'calculation') {
            if (! $get) {
                return null;
            }

            $formula = $field->config['dynamic_formula'] ?? null;
            if (! $formula) {
                return null;
            }

            // Regex to find {ulid} patterns
            $parsedFormula = preg_replace_callback('/\{([a-zA-Z0-9-]+)\}/', function ($matches) use ($get) {
                $ulid = $matches[1];
                $val = $get("values.{$ulid}");

                // Ensure value is numeric for safety
                return is_numeric($val) ? $val : 0;
            }, $formula);

            // Safety: Only allow numbers and basic math operators
            if (preg_match('/^[0-9\.\+\-\*\/\(\)\s]+$/', $parsedFormula)) {
                try {
                    $result = @eval("return {$parsedFormula};");

                    return $result;
                } catch (\Throwable $e) {
                    return null;
                }
            }
        }

        return null;
    }

    protected static function applyDynamicSettings(Input $input, ?Field $field = null): Input
    {
        if (! $field || empty($field->config['dynamic_mode']) || $field->config['dynamic_mode'] === 'none') {
            return $input;
        }

        return $input
            // We keep afterStateHydrated for initial load,
            // but we remove the `key` hack as we use "Push" model for updates.
            ->afterStateHydrated(function (Input $component, Get $get, Set $set) use ($field) {
                $mode = $field->config['dynamic_mode'] ?? 'none';

                // Use the shared calculation logic
                // But we need to resolve sourceValue from $get
                $sourceUlid = $field->config['dynamic_source_field'] ?? null;
                if ($sourceUlid) {
                    $sourceValue = $get("values.{$sourceUlid}");
                    $newValue = self::calculateDynamicValue($field, $sourceValue); // We need to update this sig for calc?

                    if ($newValue !== null && $component->getState() !== $newValue) {
                        $component->state($newValue);
                        $set($component->getStatePath(), $newValue);
                    }
                }
            });
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
                            Toggle::make('config.readOnly')
                                ->label(__('Read only')),
                            Grid::make(2)
                                ->schema([
                                    Select::make('config.autocapitalize')
                                        ->label(__('Autocapitalize'))
                                        ->options([
                                            'none' => __('None (off)'),
                                            'sentences' => __('Sentences'),
                                            'words' => __('Words'),
                                            'characters' => __('Characters'),
                                        ]),
                                    Input::make('config.autocomplete')
                                        ->default(false)
                                        ->label(__('Autocomplete')),
                                    self::affixFormFields(),
                                    self::datalistFormFields(),
                                    Input::make('config.placeholder')
                                        ->label(__('Placeholder')),
                                    Input::make('config.mask')
                                        ->label(__('Mask')),
                                    Input::make('config.minLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Minimum length')),
                                    Input::make('config.maxLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Maximum length')),
                                    Select::make('config.type')
                                        ->columnSpanFull()
                                        ->label(__('Type'))
                                        ->live(debounce: 250)
                                        ->options([
                                            'text' => __('Text'),
                                            'password' => __('Password'),
                                            'tel' => __('Telephone'),
                                            'url' => __('URL'),
                                            'email' => __('Email'),
                                            'numeric' => __('Numeric'),
                                            'integer' => __('Integer'),
                                        ]),
                                    Input::make('config.step')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Step'))
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'numeric'),
                                    Select::make('config.inputMode')
                                        ->label(__('Input mode'))
                                        ->options([
                                            'none' => __('None'),
                                            'text' => __('Text'),
                                            'decimal' => __('Decimal'),
                                            'numeric' => __('Numeric'),
                                            'tel' => __('Telephone'),
                                            'search' => __('Search'),
                                            'email' => __('Email'),
                                            'url' => __('URL'),
                                        ])
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'numeric'),
                                    Toggle::make('config.revealable')
                                        ->label(__('Revealable'))
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'password'),
                                    Input::make('config.telRegex')
                                        ->label(__('Telephone regex'))
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'tel'),
                                ]),
                        ]),
                    Tab::make('Dynamic Values')
                        ->label(__('Dynamic Values'))
                        ->schema([
                            Grid::make(1)
                                ->schema([
                                    Select::make('config.dynamic_mode')
                                        ->label(__('Mode'))
                                        ->options([
                                            'none' => __('None'),
                                            'relation' => __('Relation Prefill'),
                                            'calculation' => __('Calculation'),
                                        ])
                                        ->live(),
                                    Select::make('config.dynamic_source_field')
                                        ->label(__('Source Field'))
                                        ->helperText(__('Select the field to use as source.'))
                                        ->options(function ($record, $component) {
                                            $formSlug = null;

                                            if ($record && isset($record->model_key)) {
                                                $formSlug = $record->model_key;
                                            }

                                            if (! $formSlug) {
                                                $routeParams = request()->route()?->parameters() ?? [];
                                                $formSlug = $routeParams['record'] ?? $routeParams['form'] ?? $routeParams['id'] ?? null;
                                            }

                                            if (! $formSlug && method_exists($component, 'getOwnerRecord')) {
                                                $ownerRecord = $component->getOwnerRecord();
                                                if ($ownerRecord) {
                                                    $formSlug = $ownerRecord->getKey();
                                                }
                                            }

                                            if (! $formSlug) {
                                                return [];
                                            }

                                            $fields = \Backstage\Fields\Models\Field::where('model_type', 'App\Models\Form')
                                                ->where('model_key', $formSlug)
                                                ->when($record && isset($record->ulid), function ($query) use ($record) {
                                                    return $query->where('ulid', '!=', $record->ulid);
                                                })
                                                ->orderBy('name')
                                                ->pluck('name', 'ulid')
                                                ->toArray();

                                            return $fields;
                                        })
                                        ->searchable()
                                        ->visible(fn (Get $get): bool => $get('config.dynamic_mode') === 'relation'),
                                    \Filament\Forms\Components\Select::make('config.dynamic_relation_column')
                                        ->label(__('Relation Column'))
                                        ->helperText(__('The column to pluck from the related model.'))
                                        ->visible(fn (Get $get): bool => $get('config.dynamic_mode') === 'relation')
                                        ->searchable()
                                        ->options(function (Get $get) {
                                            $sourceUlid = $get('config.dynamic_source_field');
                                            if (! $sourceUlid) {
                                                return [];
                                            }

                                            $sourceField = \Backstage\Fields\Models\Field::find($sourceUlid);
                                            if (! $sourceField) {
                                                return [];
                                            }

                                            $relations = $sourceField->config['relations'] ?? [];
                                            $relationConfig = reset($relations);

                                            if (! $relationConfig || empty($relationConfig['resource'])) {
                                                return [];
                                            }

                                            $modelInstance = \Backstage\Fields\Fields\Select::resolveResourceModel($relationConfig['resource']);
                                            if (! $modelInstance) {
                                                return [];
                                            }

                                            $columns = \Illuminate\Support\Facades\Schema::getColumnListing($modelInstance->getTable());

                                            return collect($columns)->mapWithKeys(function ($column) {
                                                return [$column => $column];
                                            })->toArray();
                                        }),
                                    Input::make('config.dynamic_formula')
                                        ->label(__('Formula'))
                                        ->helperText(__('Use field names as variables. Example: "price * quantity". Use {field_ulid} for specific fields if needed.'))
                                        ->visible(fn (Get $get): bool => $get('config.dynamic_mode') === 'calculation'),
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

    public function getFieldType(): ?string
    {
        return 'text';
    }
}
