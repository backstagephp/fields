<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasConfigurableFields;
use Backstage\Fields\Concerns\HasFieldTypeResolver;
use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\Field as FieldEnum;
use Backstage\Fields\Facades\Fields;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Backstage\Fields\Components\NormalizedRepeater;
use Filament\Forms\Components\CodeEditor\Enums\Language;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater as Input;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Alignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Saade\FilamentAdjacencyList\Forms\Components\AdjacencyList;

class Repeater extends Base implements FieldContract
{
    use HasConfigurableFields;
    use HasFieldTypeResolver;
    use HasOptions;

    public function getFieldType(): ?string
    {
        return 'repeater';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'addActionLabel' => __('Add row'),
            'addable' => true,
            'deletable' => true,
            'reorderable' => false,
            'reorderableWithButtons' => false,
            'collapsible' => false,
            'collapsed' => false,
            'cloneable' => false,
            'columns' => 1,
            'form' => [],
            'tableMode' => false,
            'tableColumns' => [],
            'compact' => false,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        // Create an anonymous class extending the Filament Repeater to intercept the state
        // This is necessary because standard Filament hooks (like formatStateUsing)
        // are bypassed by the Repeater's internal rendering logic.
        // We use NormalizedRepeater (separate class) because standard anonymous classes
        // cannot be serialized by Livewire.
        $input = self::applyDefaultSettings(NormalizedRepeater::make($name), $field);

        $input->configure();

        $isReorderable = $field->config['reorderable'] ?? self::getDefaultConfig()['reorderable'];
        $isReorderableWithButtons = $field->config['reorderableWithButtons'] ?? self::getDefaultConfig()['reorderableWithButtons'];

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->addActionLabel($field->config['addActionLabel'] ?? self::getDefaultConfig()['addActionLabel'])
            ->addable($field->config['addable'] ?? self::getDefaultConfig()['addable'])
            ->deletable($field->config['deletable'] ?? self::getDefaultConfig()['deletable'])
            ->reorderable($isReorderable)
            ->collapsible($field->config['collapsible'] ?? self::getDefaultConfig()['collapsible'])
            ->cloneable($field->config['cloneable'] ?? self::getDefaultConfig()['cloneable'])
            ->columns($field->config['columns'] ?? self::getDefaultConfig()['columns']);

        if ($field->config['compact'] ?? self::getDefaultConfig()['compact']) {
            $input = $input->compact();
        }

        if ($isReorderableWithButtons) {
            $input = $input->reorderableWithButtons();
        }

        // Fix for Filament Forms v4.2.0 reorder bug
        // The default reorder action has a bug where array_flip() creates integer values
        // that get merged with the state array, causing type errors
        if ($isReorderable || $isReorderableWithButtons) {
            $input = $input->reorderAction(function ($action) {
                return $action->action(function (array $arguments, Input $component): void {
                    $currentState = $component->getRawState();
                    $newOrder = $arguments['items'];

                    // Reorder the items based on the new order
                    $reorderedItems = [];
                    foreach ($newOrder as $oldIndex) {
                        if (isset($currentState[$oldIndex])) {
                            $reorderedItems[] = $currentState[$oldIndex];
                        }
                    }

                    $component->rawState($reorderedItems);
                    $component->callAfterStateUpdated();
                    $component->shouldPartiallyRenderAfterActionsCalled() ? $component->partiallyRender() : null;
                });
            });
        }

        if ($field && ! $field->relationLoaded('children')) {
            $field->load('children');
        }

        if ($field && $field->children->count() > 0) {
            $input = $input->schema(self::generateSchemaFromChildren($field->children));

            // Apply table mode if enabled
            if ($field->config['tableMode'] ?? self::getDefaultConfig()['tableMode']) {
                $tableColumns = self::generateTableColumnsFromChildren($field->children, $field->config['tableColumns'] ?? []);
                if (! empty($tableColumns)) {
                    $input = $input->table($tableColumns);
                }
            }
        }

        return $input;
    }

    public function getForm(): array
    {
        return [
            Tabs::make()
                ->schema([
                    Tab::make('General')
                        ->label(__('General'))
                        ->schema($this->getBaseFormSchema()),
                    Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Grid::make(3)->schema([
                                Forms\Components\Toggle::make('config.addable')
                                    ->label(__('Addable')),
                                Forms\Components\Toggle::make('config.deletable')
                                    ->label(__('Deletable')),
                                Forms\Components\Toggle::make('config.reorderable')
                                    ->label(__('Reorderable'))
                                    ->live(),
                                Forms\Components\Toggle::make('config.reorderableWithButtons')
                                    ->label(__('Reorderable with buttons'))
                                    ->dehydrated()
                                    ->disabled(fn (Get $get): bool => $get('config.reorderable') === false),
                                Forms\Components\Toggle::make('config.collapsible')
                                    ->label(__('Collapsible')),
                                Forms\Components\Toggle::make('config.collapsed')
                                    ->label(__('Collapsed'))
                                    ->visible(fn (Get $get): bool => $get('config.collapsible') === true),
                                Forms\Components\Toggle::make('config.cloneable')
                                    ->label(__('Cloneable')),
                            ])->columnSpanFull(),
                            Grid::make(2)->schema([
                                TextInput::make('config.addActionLabel')
                                    ->label(__('Add action label'))
                                    ->columnSpan(fn (Get $get) => ($get('config.tableMode') ?? false) ? 'full' : 1),
                                TextInput::make('config.columns')
                                    ->label(__('Columns'))
                                    ->default(1)
                                    ->numeric()
                                    ->visible(fn (Get $get): bool => ! ($get('config.tableMode') ?? false)),
                                Forms\Components\Toggle::make('config.tableMode')
                                    ->label(__('Table Mode'))
                                    ->live(),
                                Forms\Components\Toggle::make('config.compact')
                                    ->label(__('Compact table'))
                                    ->live()
                                    ->visible(fn (Get $get): bool => ($get('config.tableMode') ?? false)),
                            ])->columnSpanFull(),
                            AdjacencyList::make('config.form')
                                ->columnSpanFull()
                                ->label(__('Fields'))
                                ->orderColumn('position')
                                ->relationship('children')
                                ->live(debounce: 250)
                                ->labelKey('name')
                                ->indentable(false)
                                ->moveable(true)
                                ->reorderable(false)
                                ->addable(fn (string $operation) => $operation !== 'create')
                                ->disabled(fn (string $operation) => $operation === 'create')
                                ->hint(fn (string $operation) => $operation === 'create' ? __('Fields can be added once the field is created.') : '')
                                ->hintColor('primary')
                                ->schema([
                                    Section::make('Field')
                                        ->columns(3)
                                        ->schema([
                                            Hidden::make('model_type')
                                                ->default('field'),
                                            Hidden::make('model_key')
                                                ->default('ulid'),
                                            TextInput::make('name')
                                                ->label(__('Name'))
                                                ->required()
                                                ->placeholder(__('Name'))
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old, ?Field $record) {
                                                    if (! $record || blank($get('slug'))) {
                                                        $set('slug', Str::slug($state));
                                                    }

                                                    $currentSlug = $get('slug');

                                                    if (! $record?->slug && (! $currentSlug || $currentSlug === Str::slug($old))) {
                                                        $set('slug', Str::slug($state));
                                                    }
                                                }),
                                            TextInput::make('slug')
                                                ->readonly(),
                                            Select::make('field_type')
                                                ->searchable()
                                                ->preload()
                                                ->label(__('Field Type'))
                                                ->live(debounce: 250)
                                                ->reactive()
                                                ->default(FieldEnum::Text->value)
                                                ->options(
                                                    function () {
                                                        $options = array_merge(
                                                            FieldEnum::array(),
                                                            $this->prepareCustomFieldOptions(Fields::getFields())
                                                        );

                                                        asort($options);

                                                        return $options;
                                                    }
                                                )
                                                ->required()
                                                ->afterStateUpdated(function ($state, Set $set) {
                                                    $set('config', []);

                                                    $set('config', $this->initializeConfig($state));
                                                }),
                                        ])->columnSpanFull(),
                                    Section::make('Configuration')
                                        ->columns(3)
                                        ->schema(fn (Get $get) => $this->getFieldTypeFormSchema(
                                            $get('field_type')
                                        ))
                                        ->visible(fn (Get $get) => filled($get('field_type'))),
                                ]),
                             Forms\Components\CodeEditor::make('config.defaultValue')
                                ->label(__('Default Items (JSON)'))
                                ->language(Language::Json)
                                ->formatStateUsing(function ($state) {
                                    if (is_array($state)) {
                                        return json_encode($state, JSON_PRETTY_PRINT);
                                    }

                                    return $state;
                                })
                                ->rules('json')
                                ->helperText(__('Array of objects for default rows. Example: [{"slug": "value"}]'))
                                ->columnSpanFull(),
                        ])->columns(2),
                ])->columnSpanFull(),
        ];
    }

    protected function excludeFromBaseSchema(): array
    {
        return ['defaultValue'];
    }

    private static function generateSchemaFromChildren(Collection $children, bool $isTableMode = false): array
    {
        $schema = [];
        $dependencyMap = []; // source_slug => [dependent_child_1, ... ]
        $ulidToSlug = [];

        $children = $children->sortBy('position');

        // First pass: Build dependency map
        foreach ($children as $child) {
            $ulidToSlug[$child['ulid']] = $child['slug'];

            $config = $child['config'] ?? [];
            $mode = $config['dynamic_mode'] ?? 'none';

            if ($mode === 'relation') {
                $sourceUlid = $config['dynamic_source_field'] ?? null;
                if ($sourceUlid) {
                    $dependencyMap[$sourceUlid][] = $child;
                }
            } elseif ($mode === 'calculation') {
                $formula = $config['dynamic_formula'] ?? '';
                preg_match_all('/\{([a-zA-Z0-9-]+)\}/', $formula, $matches);
                foreach ($matches[1] as $sourceUlid) {
                    $dependencyMap[$sourceUlid][] = $child;
                }
            }
        }

        foreach ($children as $child) {
            $fieldType = $child['field_type'];
            $fieldClass = self::resolveFieldTypeClassName($fieldType);

            if ($fieldClass === null) {
                continue;
            }

            $component = $fieldClass::make($child['slug'], $child);
            
            // Check if this field is a source for others
            if (isset($dependencyMap[$child['ulid']])) {
                $dependents = $dependencyMap[$child['ulid']];
                
                $component->live(onBlur: true)
                    ->afterStateUpdated(function (Get $get, Set $set, $state) use ($dependents, $ulidToSlug) {
                        foreach ($dependents as $dependent) {
                             $targetSlug = $dependent['slug'];
                             
                             // We need to pass the dependent Field model to calculateDynamicValue
                             // Since $dependent is likely the model instance itself (from $children collection)
                             // we can pass it directly.
                             
                             // Determine source value. 
                             // For 'relation', the $state of the current field IS the source value.
                             
                             // Note: Text::calculateDynamicValue is static and stateless, 
                             // it just needs the config from the field.
                             
                             $newValue = \Backstage\Fields\Fields\Text::calculateDynamicValue($dependent, $state, $get);
                             
                             if ($newValue !== null) {
                                  // Relative path set
                                  // Since we are inside a Repeater row, $set('slug', val) works for sibling fields
                                  // BUT check if $get/set context is correct.
                                  // In a Repeater item, Get/Set operate relative to the item.
                                  // So $set($targetSlug, $newValue) should work.
                                  $set($targetSlug, $newValue);
                             }
                        }
                    });
            }

            $schema[] = $component;
        }

        return $schema;
    }

    private static function generateTableColumnsFromChildren(Collection $children, array $tableColumnsConfig = []): array
    {
        $tableColumns = [];

        $children = $children->sortBy('position');

        foreach ($children as $child) {
            $slug = $child['slug'];
            $name = $child['name'];

            $columnConfig = $tableColumnsConfig[$slug] ?? [];

            $tableColumn = TableColumn::make($name);

            // Apply custom configuration if provided
            if (isset($columnConfig['hiddenHeaderLabel']) && $columnConfig['hiddenHeaderLabel']) {
                $tableColumn = $tableColumn->hiddenHeaderLabel();
            }

            if (isset($columnConfig['markAsRequired']) && $columnConfig['markAsRequired']) {
                $tableColumn = $tableColumn->markAsRequired();
            }

            if (isset($columnConfig['wrapHeader']) && $columnConfig['wrapHeader']) {
                $tableColumn = $tableColumn->wrapHeader();
            }

            if (isset($columnConfig['alignment'])) {
                $alignment = match ($columnConfig['alignment']) {
                    'start' => Alignment::Start,
                    'center' => Alignment::Center,
                    'end' => Alignment::End,
                    default => Alignment::Start,
                };
                $tableColumn = $tableColumn->alignment($alignment);
            }

            if (isset($columnConfig['width'])) {
                $tableColumn = $tableColumn->width($columnConfig['width']);
            }

            $tableColumns[] = $tableColumn;
        }

        return $tableColumns;
    }


}
