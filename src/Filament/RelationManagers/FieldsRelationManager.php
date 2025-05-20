<?php

namespace Backstage\Fields\Filament\RelationManagers;

use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Livewire\Component;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Grid;
use Filament\Tables\Grouping\Group;
use Backstage\Fields\Facades\Fields;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Backstage\Fields\Enums\Field as FieldEnum;
use Backstage\Fields\Concerns\HasFieldTypeResolver;
use Backstage\Fields\Concerns\HasConfigurableFields;
use Filament\Resources\RelationManagers\RelationManager;

class FieldsRelationManager extends RelationManager
{
    use HasConfigurableFields;
    use HasFieldTypeResolver;

    protected static string $relationship = 'fields';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->columns(3)
                    ->schema([
                        Section::make('Field')
                            ->columns(3)
                            ->schema([
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
                                    ->options(function () {
                                        return Field::pluck('group')
                                            ->filter()
                                            ->unique()
                                            ->mapWithKeys(fn ($group) => [$group => $group])
                                            ->toArray();
                                    }),

                            ]),
                        Section::make('Configuration')
                            ->columns(3)
                            ->schema(fn (Get $get) => $this->getFieldTypeFormSchema(
                                $get('field_type')
                            ))
                            ->visible(fn (Get $get) => filled($get('field_type'))),
                    ]),
            ]);
    }

    private function formatCustomFields(array $fields): array
    {
        return collect($fields)->mapWithKeys(function ($field, $key) {
            $parts = explode('\\', $field);
            $lastPart = end($parts);
            $formattedName = Str::title(Str::snake($lastPart, ' '));

            return [$key => $formattedName];
        })->toArray();
    }

    private function initializeDefaultConfig(string $fieldType): array
    {
        $className = 'Backstage\\Fields\\Fields\\' . Str::studly($fieldType);

        if (! class_exists($className)) {
            return [];
        }

        $fieldInstance = app($className);

        return $fieldInstance::getDefaultConfig();
    }

    private function initializeCustomConfig(string $fieldType): array
    {
        $className = Fields::getFields()[$fieldType] ?? null;

        if (! class_exists($className)) {
            return [];
        }

        $fieldInstance = app($className);

        return $fieldInstance::getDefaultConfig();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([25, 50, 100])
            ->recordTitleAttribute('name')
            ->reorderable('position')
            ->defaultSort('position', 'asc')
            ->defaultGroup('group')
            ->groups([
                Group::make('group')
                    ->label(__('Group'))
                    ->getTitleFromRecordUsing(fn ($record): string => filled($record->group) ? $record->group : '-')
            ])
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->limit(),

                Tables\Columns\TextColumn::make('field_type')
                    ->label(__('Type'))
                    ->searchable(),
            ])
            ->filters([])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data) {

                        $key = $this->ownerRecord->getKeyName() ?? 'id';

                        return [
                            ...$data,
                            'position' => Field::where('model_key', $key)->get()->max('position') + 1,
                            'model_type' => 'setting',
                            'model_key' => $this->ownerRecord->slug,
                        ];
                    })
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('refreshFields');
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->mutateRecordDataUsing(function (array $data) {

                        $key = $this->ownerRecord->getKeyName() ?? 'id';

                        return [
                            ...$data,
                            'model_type' => 'setting',
                            'model_key' => $this->ownerRecord->{$key},
                        ];
                    })
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('refreshFields');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Component $livewire, array $data, Model $record, array $arguments) {
                        if (
                            isset($record->valueColumn) && $this->ownerRecord->getConnection()
                                ->getSchemaBuilder()
                                ->hasColumn($this->ownerRecord->getTable(), $record->valueColumn)
                        ) {

                            $key = $this->ownerRecord->getKeyName() ?? 'id';

                            $this->ownerRecord->update([
                                $record->valueColumn => collect($this->ownerRecord->{$record->valueColumn})->forget($record->{$key})->toArray(),
                            ]);
                        }

                        $livewire->dispatch('refreshFields');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->after(function (Component $livewire) {
                            $livewire->dispatch('refreshFields');
                        }),
                ]),
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
}
