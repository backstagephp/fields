<?php

namespace Vormkracht10\Fields\Filament\RelationManagers;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Livewire\Component;
use Vormkracht10\Fields\Facades\Fields;
use Vormkracht10\Fields\Concerns\HasConfigurableFields;
use Vormkracht10\Fields\Concerns\HasFieldTypeResolver;
use Vormkracht10\Fields\Models\Field;

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
                                    ->live(debounce: 250)
                                    ->afterStateUpdated(fn (Set $set, ?string $state) => $set('slug', Str::slug($state))),

                                TextInput::make('slug')
                                    ->readonly(),

                                Select::make('field_type')
                                    ->searchable()
                                    ->preload()
                                    ->label(__('Field Type'))
                                    ->live(debounce: 250)
                                    ->reactive()
                                    ->options(
                                        function () {
                                            $options = array_merge(
                                                Field::array(),
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
        $className = 'Vormkracht10\\FilamentFields\\Fields\\' . Str::studly($fieldType);

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
            ->recordTitleAttribute('name')
            ->reorderable('position')
            ->defaultSort('position', 'asc')
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
                        return [
                            ...$data,
                            'position' => Field::where('model_key', $this->ownerRecord->id)->get()->max('position') + 1,
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
                        return [
                            ...$data,
                            'model_type' => 'setting',
                            'model_key' => $this->ownerRecord->slug,
                        ];
                    })
                    ->mutateFormDataUsing(fn (array $data, Model $record): array => $this->transferValuesOnSlugChange($data, $record))
                    ->after(function (Component $livewire) {
                        $livewire->dispatch('refreshFields');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->after(function (Component $livewire, array $data, Model $record, array $arguments) {
                        $this->ownerRecord->update([
                            'values' => collect($this->ownerRecord->values)->forget($record->slug)->toArray(),
                        ]);
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

    private function transferValuesOnSlugChange(array $data, Model $record): array
    {
        $oldSlug = $record->slug;
        $newSlug = $data['slug'];

        if ($newSlug === $oldSlug) {
            return $data;
        }

        $existingValues = $this->ownerRecord->values;

        // Handle slug update in existing values
        if (isset($existingValues[$oldSlug])) {
            // Transfer value from old slug to new slug
            $existingValues[$newSlug] = $existingValues[$oldSlug];
            unset($existingValues[$oldSlug]);

            $this->ownerRecord->update([
                'values' => $existingValues,
            ]);
        } else {
            $existingValues[$newSlug] = null;
        }

        return $data;
    }
}
