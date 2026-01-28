<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasAffixes;
use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Select as Input;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Select extends Base implements FieldContract
{
    use HasAffixes;
    use HasOptions;

    public function getFieldType(): ?string
    {
        return 'select';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            ...self::getAffixesConfig(),
            ...self::getOptionsConfig(),
            'searchable' => false,
            'multiple' => false,
            'reorderable' => false,
            'preload' => false,
            'allowHtml' => false,
            'selectablePlaceholder' => true,
            'loadingMessage' => null,
            'noSearchResultsMessage' => null,
            'searchPrompt' => null,
            'searchingMessage' => null,
            'searchDebounce' => null,
            'optionsLimit' => null,
            'minItemsForSearch' => null,
            'maxItemsForSearch' => null,
            'dependsOnField' => null, // Simple field dependency
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? null)
            ->searchable($field->config['searchable'] ?? self::getDefaultConfig()['searchable'])
            ->multiple($field->config['multiple'] ?? self::getDefaultConfig()['multiple'])
            ->preload($field->config['preload'] ?? self::getDefaultConfig()['preload'])
            ->allowHtml($field->config['allowHtml'] ?? self::getDefaultConfig()['allowHtml'])
            ->selectablePlaceholder($field->config['selectablePlaceholder'] ?? self::getDefaultConfig()['selectablePlaceholder'])
            ->loadingMessage($field->config['loadingMessage'] ?? self::getDefaultConfig()['loadingMessage'])
            ->noSearchResultsMessage($field->config['noSearchResultsMessage'] ?? self::getDefaultConfig()['noSearchResultsMessage'])
            ->searchPrompt($field->config['searchPrompt'] ?? self::getDefaultConfig()['searchPrompt'])
            ->searchingMessage($field->config['searchingMessage'] ?? self::getDefaultConfig()['searchingMessage'])
            ->live() // Add live binding for real-time updates
            ->dehydrated() // Ensure the field is included in form submission
            ->reactive(); // Ensure the field reacts to state changes

        // Handle field dependencies
        if (isset($field->config['dependsOnField']) && $field->config['dependsOnField']) {
            $input = self::addFieldDependency($input, $field);
        }

        // Add dynamic options first (from relationships, etc.)
        $input = self::addOptionsToInput($input, $field);

        // Set static options as fallback if no dynamic options were added
        if (empty($field->config['optionType']) || ! is_array($field->config['optionType']) || ! in_array('relationship', $field->config['optionType'])) {
            $input = $input->options($field->config['options'] ?? self::getDefaultConfig()['options']);
        }

        $input = self::addAffixesToInput($input, $field);

        if (isset($field->config['searchDebounce'])) {
            $input->searchDebounce($field->config['searchDebounce']);
        }

        if (isset($field->config['optionsLimit'])) {
            $input->optionsLimit($field->config['optionsLimit']);
        }

        if (isset($field->config['minItemsForSearch'])) {
            $input->minItemsForSearch($field->config['minItemsForSearch']);
        }

        if (isset($field->config['maxItemsForSearch'])) {
            $input->maxItemsForSearch($field->config['maxItemsForSearch']);
        }

        if (($field->config['multiple'] ?? false) && ($field->config['reorderable'] ?? false)) {
            $input->reorderable();
        }

        return $input;
    }

    protected static function addFieldDependency(Input $input, Field $field): Input
    {
        $dependsOnField = $field->config['dependsOnField'];

        return $input
            ->live()
            ->visible(function (Get $get) use ($dependsOnField) {
                // The field name in the form is {valueColumn}.{field_ulid}
                $dependentFieldName = "values.{$dependsOnField}";
                $dependentValue = $get($dependentFieldName);

                // Show this field only when the dependent field has a value
                return ! empty($dependentValue);
            });
    }

    public static function mutateFormDataCallback(Model $record, Field $field, array $data): array
    {
        if (! property_exists($record, 'valueColumn')) {
            return $data;
        }

        $value = self::getFieldValueFromRecord($record, $field);

        $data[$record->valueColumn][$field->ulid] = self::normalizeSelectValue($value, $field);

        return $data;
    }

    public static function mutateBeforeSaveCallback(Model $record, Field $field, array $data): array
    {
        if (! property_exists($record, 'valueColumn')) {
            return $data;
        }

        $value = $data[$record->valueColumn][$field->ulid] ?? $data[$record->valueColumn][$field->slug] ?? null;

        if ($value === null && ! isset($data[$record->valueColumn][$field->ulid]) && ! isset($data[$record->valueColumn][$field->slug])) {
            return $data;
        }

        $data[$record->valueColumn][$field->ulid] = self::normalizeSelectValue($value, $field);

        return $data;
    }

    /**
     * Normalize the select value to an array or a single value. This is needed because the select field can be
     * changed from single to multiple or vice versa.
     */
    protected static function normalizeSelectValue($value, Field $field): mixed
    {
        if ($value instanceof Collection) {
            $value = $value->toArray();
        }

        $isMultiple = $field->config['multiple'] ?? false;

        // Handle JSON string values
        if (is_string($value) && json_validate($value)) {
            $value = json_decode($value, true);
        }

        // Handle null/empty values consistently
        if ($value === null || $value === '') {
            return $isMultiple ? [] : null;
        }

        // Convert to array if multiple is expected but value is not an array
        if ($isMultiple && ! is_array($value)) {
            return [$value];
        }

        // Convert array to single value if multiple is not expected
        if (! $isMultiple && is_array($value)) {
            return empty($value) ? null : reset($value);
        }

        return $value;
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
                                    Toggle::make('config.multiple')
                                        ->label(__('Multiple'))
                                        ->helperText(__('Only first value is kept when switching.'))
                                        ->live()
                                        ->columnSpan(1),
                                    Toggle::make('config.reorderable')
                                        ->label(__('Reorderable'))
                                        ->helperText(__('Allow users to reorder selected items.'))
                                        ->visible(fn (Get $get): bool => $get('config.multiple'))
                                        ->columnSpan(1),
                                    Toggle::make('config.allowHtml')
                                        ->label(__('Allow HTML')),
                                    Toggle::make('config.selectablePlaceholder')
                                        ->label(__('Selectable placeholder')),
                                    Toggle::make('config.preload')
                                        ->label(__('Preload'))
                                        ->live()
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                ])->columnSpanFull(),
                            self::optionFormFields(),
                            self::affixFormFields(),
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('config.loadingMessage')
                                        ->label(__('Loading message'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.noSearchResultsMessage')
                                        ->label(__('No search results message'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.searchPrompt')
                                        ->label(__('Search prompt'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.searchingMessage')
                                        ->label(__('Searching message'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.searchDebounce')
                                        ->numeric()
                                        ->minValue(0)
                                        ->step(100)
                                        ->label(__('Search debounce'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.optionsLimit')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Options limit'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.minItemsForSearch')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Min items for search'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                    TextInput::make('config.maxItemsForSearch')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Max items for search'))
                                        ->visible(fn (Get $get): bool => $get('config.searchable')),
                                ]),
                        ]),
                    Tab::make('Field Dependencies')
                        ->label(__('Field Dependencies'))
                        ->schema([
                            Grid::make(1)
                                ->schema([
                                    \Filament\Forms\Components\Select::make('config.dependsOnField')
                                        ->label(__('Depends on Field'))
                                        ->helperText(__('Select another field in this form that this select should depend on. When the dependent field changes, this field will show its options.'))
                                        ->options(function ($record, $component) {
                                            // Try to get the form slug from various sources
                                            $formSlug = null;

                                            // Method 1: From the record's model_key (most reliable)
                                            if ($record && isset($record->model_key)) {
                                                $formSlug = $record->model_key;
                                            }

                                            // Method 2: From route parameters as fallback
                                            if (! $formSlug) {
                                                $routeParams = request()->route()?->parameters() ?? [];
                                                $formSlug = $routeParams['record'] ?? $routeParams['form'] ?? $routeParams['id'] ?? null;
                                            }

                                            // Method 3: Try to get from the component's owner record if available
                                            if (! $formSlug && method_exists($component, 'getOwnerRecord')) {
                                                $ownerRecord = $component->getOwnerRecord();
                                                if ($ownerRecord) {
                                                    $formSlug = $ownerRecord->getKey();
                                                }
                                            }

                                            if (! $formSlug) {
                                                return ['debug' => 'No form slug found. Record: ' . ($record ? json_encode($record->toArray()) : 'null')];
                                            }

                                            // Get all select fields in the same form
                                            $fields = \Backstage\Fields\Models\Field::where('model_type', 'App\Models\Form')
                                                ->where('model_key', $formSlug)
                                                ->where('field_type', 'select')
                                                ->when($record && isset($record->ulid), function ($query) use ($record) {
                                                    return $query->where('ulid', '!=', $record->ulid);
                                                })
                                                ->orderBy('name')
                                                ->pluck('name', 'ulid')
                                                ->toArray();

                                            if (empty($fields)) {
                                                return ['debug' => 'No select fields found for form: ' . $formSlug . '. Total fields: ' . \Backstage\Fields\Models\Field::where('model_type', 'App\Models\Form')->where('model_key', $formSlug)->count()];
                                            }

                                            return $fields;
                                        })
                                        ->searchable()
                                        ->live(),
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
