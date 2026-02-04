<?php

namespace Backstage\Fields\Filament\RelationManagers;

use Livewire\Component;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Schemas\Schema;
use Filament\Actions\BulkAction;
use Filament\Actions\EditAction;
use Backstage\Fields\Models\Field;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Grouping\Group;
use Backstage\Fields\Facades\Fields;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Backstage\Fields\Enums\Field as FieldEnum;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Backstage\Fields\Concerns\HasFieldTypeResolver;
use Backstage\Fields\Concerns\HasConfigurableFields;
use Filament\Resources\RelationManagers\RelationManager;

class FieldsRelationManager extends RelationManager
{
    use HasConfigurableFields;
    use HasFieldTypeResolver;

    protected static string $relationship = 'fields';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->columnSpanFull()
                    ->schema([
                        Section::make('Field')
                            ->columns(2)
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('Name'))
                                    ->required()
                                    ->autocomplete(false)
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

                                TextInput::make('slug'),

                                Select::make('field_type')
                                    ->searchable()
                                    ->preload()
                                    ->label(__('Field Type'))
                                    ->live(debounce: 250)
                                    ->reactive()
                                    ->default(FieldEnum::Text->value)
                                    ->options(function () {
                                        return collect([
                                            ...FieldEnum::array(),
                                            ...$this->prepareCustomFieldOptions(Fields::getFields()),
                                        ])
                                            ->sortBy(fn ($value) => $value)
                                            ->mapWithKeys(fn ($value, $key) => [
                                                $key => Str::headline($value),
                                            ])
                                            ->toArray();
                                    })
                                    ->required()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        $set('config', []);

                                        if (blank($state)) {
                                            return;
                                        }

                                        $set('config', $this->initializeConfig($state));
                                    }),

                                Select::make('group')
                                    ->label(__('Group'))
                                    ->createOptionForm([
                                        TextInput::make('group')
                                            ->label(__('Group'))
                                            ->required(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return $data['group'] ?? null;
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->options(function (Get $get) {
                                        $existingGroups = Field::pluck('group')
                                            ->filter()
                                            ->unique()
                                            ->mapWithKeys(fn ($group) => [$group => $group])
                                            ->toArray();

                                        $currentValue = $get('group');
                                        if ($currentValue && ! array_key_exists($currentValue, $existingGroups)) {
                                            $existingGroups[$currentValue] = $currentValue;
                                        }

                                        return $existingGroups;
                                    }),

                                Select::make('schema_id')
                                    ->label(__('Attach to Schema'))
                                    ->placeholder(__('Select a schema (optional)'))
                                    ->options($this->getSchemaOptions())
                                    ->searchable()
                                    ->live()
                                    ->reactive()
                                    ->helperText(__('Attach this field to a specific schema for better organization')),

                            ]),
                        Section::make('Configuration')
                            ->columnSpanFull()
                            ->schema(fn (Get $get) => $this->getFieldTypeFormSchema(
                                $get('field_type')
                            ))
                            ->visible(fn (Get $get) => filled($get('field_type'))),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100])
            ->recordTitleAttribute('name')
            ->reorderable('position')
            ->defaultSort('position', 'asc')
            ->modifyQueryUsing(fn ($query) => $query->with(['schema']))
            ->groupingSettingsHidden(true)
            ->defaultGroup('group')
            ->groups([
               'group' => Group::make('group')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn(Model|Field $record) => $record->group ?? __('No group'))
            ])
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->limit(),

                TextColumn::make('group')
                    ->label(__('Group'))
                    ->placeholder(__('No Group'))
                    ->searchable()
                    ->sortable()
                    ->hidden(fn(Table $table) => !$table->isReordering())
                    ->getStateUsing(fn (Field $record): string => $record->group ?? __('No Group')),

                TextColumn::make('field_type')
                    ->label(__('Type'))
                    ->searchable(),

                TextColumn::make('schema.name')
                    ->label(__('Schema'))
                    ->placeholder(__('No schema'))
                    ->searchable()
                    ->getStateUsing(fn (Field $record): string => $record->schema->name ?? __('No Schema')),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('group')
                    ->label(__('Group'))
                    ->options(function () {
                        return Field::where('model_type', get_class($this->ownerRecord))
                            ->where('model_key', $this->ownerRecord->getKey())
                            ->pluck('group')
                            ->filter()
                            ->unique()
                            ->mapWithKeys(fn ($group) => [$group => $group])
                            ->prepend(__('No Group'), '')
                            ->toArray();
                    }),
                \Filament\Tables\Filters\SelectFilter::make('schema_id')
                    ->label(__('Schema'))
                    ->relationship('schema', 'name')
                    ->placeholder(__('All Schemas')),
            ])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->mutateDataUsing(function (array $data) {

                        return [
                            ...$data,
                            'position' => Field::where('model_key', $this->ownerRecord->getKey())
                                ->where('model_type', get_class($this->ownerRecord))
                                ->get()
                                ->max('position') + 1,
                            'model_type' => get_class($this->ownerRecord),
                            'model_key' => $this->ownerRecord->getKey(),
                        ];
                    })
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('refreshFields');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->mutateRecordDataUsing(function (array $data) {

                        return [
                            ...$data,
                            'model_type' => get_class($this->ownerRecord),
                            'model_key' => $this->ownerRecord->getKey(),
                        ];
                    })
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('refreshFields');
                    }),
                DeleteAction::make()
                    ->after(function (Component $livewire, array $data, Model $record, array $arguments) {
                        if (
                            isset($record->valueColumn) && $this->ownerRecord->getConnection()
                                ->getSchemaBuilder()
                                ->hasColumn($this->ownerRecord->getTable(), $record->valueColumn)
                        ) {

                            $key = $this->ownerRecord->getKeyName();

                            $this->ownerRecord->update([
                                $record->valueColumn => collect($this->ownerRecord->{$record->valueColumn})->forget($record->{$key})->toArray(),
                            ]);
                        }

                        $livewire->dispatch('refreshFields');
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->requiresConfirmation()
                        ->after(function (Component $livewire) {
                            $livewire->dispatch('refreshFields');
                        }),
                ])->label('Actions'),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Fields');
    }

    public static function getModelLabel(): string
    {
        return __('Field');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Fields');
    }

    protected function getSchemaOptions(): array
    {
        $options = \Backstage\Fields\Models\Schema::where('model_key', $this->ownerRecord->getKey())
            ->where('model_type', get_class($this->ownerRecord))
            ->orderBy('position')
            ->pluck('name', 'ulid')
            ->toArray();

        return $options;
    }
}
