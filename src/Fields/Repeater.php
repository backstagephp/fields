<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasConfigurableFields;
use Backstage\Fields\Concerns\HasFieldTypeResolver;
use Backstage\Fields\Concerns\HasOptions;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\Field as FieldEnum;
use Backstage\Fields\Facades\Fields;
use Backstage\Fields\Fields\Select as FieldsSelect;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater as Input;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Saade\FilamentAdjacencyList\Forms\Components\AdjacencyList;

class Repeater extends Base implements FieldContract
{
    use HasConfigurableFields;
    use HasFieldTypeResolver;
    use HasOptions;

    private const FIELD_TYPE_MAP = [
        'text' => Text::class,
        'textarea' => Textarea::class,
        'rich-editor' => RichEditor::class,
        'repeater' => Repeater::class,
        'select' => FieldsSelect::class,
        'checkbox' => Checkbox::class,
        'checkbox-list' => CheckboxList::class,
        'key-value' => KeyValue::class,
        'radio' => Radio::class,
        'toggle' => Toggle::class,
        'color' => Color::class,
        'date-time' => DateTime::class,
    ];

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
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(input: Input::make($name), field: $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->addActionLabel($field->config['addActionLabel'] ?? self::getDefaultConfig()['addActionLabel'])
            ->addable($field->config['addable'] ?? self::getDefaultConfig()['addable'])
            ->deletable($field->config['deletable'] ?? self::getDefaultConfig()['deletable'])
            ->reorderable($field->config['reorderable'] ?? self::getDefaultConfig()['reorderable'])
            ->collapsible($field->config['collapsible'] ?? self::getDefaultConfig()['collapsible'])
            ->cloneable($field->config['cloneable'] ?? self::getDefaultConfig()['cloneable'])
            ->columns($field->config['columns'] ?? self::getDefaultConfig()['columns']);

        if ($field->config['reorderableWithButtons'] ?? self::getDefaultConfig()['reorderableWithButtons']) {
            $input = $input->reorderableWithButtons();
        }

        if ($field && ! $field->relationLoaded('children')) {
            $field->load('children');
        }

        if ($field && $field->children->count() > 0) {
            $input = $input->schema(self::generateSchemaFromChildren($field->children));
        }

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
                            Forms\Components\Toggle::make('config.addable')
                                ->label(__('Addable'))
                                ->inline(false),
                            Forms\Components\Toggle::make('config.deletable')
                                ->label(__('Deletable'))
                                ->inline(false),
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\Toggle::make('config.reorderable')
                                    ->label(__('Reorderable'))
                                    ->live()
                                    ->inline(false),
                                Forms\Components\Toggle::make('config.reorderableWithButtons')
                                    ->label(__('Reorderable with buttons'))
                                    ->dehydrated()
                                    ->disabled(fn (Forms\Get $get): bool => $get('config.reorderable') === false)
                                    ->inline(false),
                            ]),
                            Forms\Components\Toggle::make('config.collapsible')
                                ->label(__('Collapsible'))
                                ->inline(false),
                            Forms\Components\Toggle::make('config.collapsed')
                                ->label(__('Collapsed'))
                                ->visible(fn (Forms\Get $get): bool => $get('config.collapsible') === true)
                                ->inline(false),
                            Forms\Components\Toggle::make('config.cloneable')
                                ->label(__('Cloneable'))
                                ->inline(false),
                            Forms\Components\TextInput::make('config.addActionLabel')
                                ->label(__('Add action label')),
                            Forms\Components\TextInput::make('config.columns')
                                ->label(__('Columns'))
                                ->default(1)
                                ->numeric(),
                            AdjacencyList::make('config.form')
                                ->columnSpanFull()
                                ->label(__('Fields'))
                                ->orderColumn('position')
                                ->relationship('children')
                                ->live(debounce: 250)
                                ->labelKey('name')
                                ->maxDepth(0)
                                ->addable(fn (string $operation) => $operation !== 'create')
                                ->disabled(fn (string $operation) => $operation === 'create')
                                ->hint(fn (string $operation) => $operation === 'create' ? __('Fields can be added once the field is created.') : '')
                                ->hintColor('primary')
                                ->form([
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
                                                ->live(debounce: 250)
                                                ->afterStateUpdated(function (Set $set, Get $get, ?string $state, ?string $old) {
                                                    $currentSlug = $get('slug');

                                                    if (! $currentSlug || $currentSlug === Str::slug($old)) {
                                                        $set('slug', Str::slug($state));
                                                    }
                                                }),
                                            TextInput::make('slug')
                                                ->required(),
                                            Select::make('field_type')
                                                ->searchable()
                                                ->preload()
                                                ->label(__('Field Type'))
                                                ->live(debounce: 250)
                                                ->reactive()
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
                        ])->columns(2),
                ])->columnSpanFull(),
        ];
    }

    private static function generateSchemaFromChildren(Collection $children): array
    {
        $schema = [];

        $children = $children->sortBy('position');

        foreach ($children as $child) {
            $fieldType = $child['field_type'];

            $field = self::resolveFieldTypeClassName($fieldType);

            if ($field === null) {
                continue;
            }

            $schema[] = $field::make($child['name'], $child);
        }

        return $schema;
    }
}
