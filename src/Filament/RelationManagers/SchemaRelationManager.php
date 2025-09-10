<?php

namespace Backstage\Fields\Filament\RelationManagers;

use Backstage\Fields\Concerns\HasConfigurableFields;
use Backstage\Fields\Concerns\HasFieldTypeResolver;
use Backstage\Fields\Enums\Schema as SchemaEnum;
use Backstage\Fields\Models\Field;
use Backstage\Fields\Models\Schema;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema as FilamentSchema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;

class SchemaRelationManager extends RelationManager
{
    use HasConfigurableFields;
    use HasFieldTypeResolver;

    protected static string $relationship = 'schemas';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(FilamentSchema $schema): FilamentSchema
    {
        return $schema
            ->schema([
                Section::make('Schema')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->autocomplete(false)
                            ->required()
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
                            ->label(__('Schema Type'))
                            ->live(debounce: 250)
                            ->reactive()
                            ->default(SchemaEnum::Section->value)
                            ->options(function () {
                                return collect(SchemaEnum::array())
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
                    ]),
                Section::make('Configuration')
                    ->columnSpanFull()
                    ->schema(fn (Get $get) => $this->getFieldTypeFormSchema(
                        $get('field_type')
                    ))
                    ->visible(fn (Get $get) => filled($get('field_type'))),
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
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->limit(),

                TextColumn::make('field_type')
                    ->label(__('Type'))
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                CreateAction::make()
                    ->slideOver()
                    ->mutateDataUsing(function (array $data) {

                        $key = $this->ownerRecord->getKeyName();

                        return [
                            ...$data,
                            'position' => Schema::where('model_key', $key)->get()->max('position') + 1,
                            'model_type' => get_class($this->ownerRecord),
                            'model_key' => $this->ownerRecord->getKey(),
                        ];
                    })
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('$refresh');
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->slideOver()
                    ->mutateRecordDataUsing(function (array $data) {

                        $key = $this->ownerRecord->getKeyName();

                        return [
                            ...$data,
                            'model_type' => 'setting',
                            'model_key' => $this->ownerRecord->{$key},
                        ];
                    })
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('refreshSchemas');
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

                        $livewire->dispatch('refreshSchemas');
                    }),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('delete')
                        ->requiresConfirmation()
                        ->after(function (Component $livewire) {
                            $livewire->dispatch('refreshSchemas');
                        }),
                ])->label('Actions'),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('Schemas');
    }

    public static function getModelLabel(): string
    {
        return __('Schema');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Schemas');
    }
}
