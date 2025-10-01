<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\FileUpload as FilamentFileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;

class FileUpload extends Base implements FieldContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'disk' => 'public',
            'directory' => 'uploads',
            'visibility' => 'public',
            'acceptedFileTypes' => null,
            'maxSize' => null,
            'maxFiles' => 1,
            'multiple' => false,
            'appendFiles' => false,
            'reorderable' => false,
            'openable' => true,
            'downloadable' => true,
            'previewable' => true,
            'deletable' => true,
        ];
    }

    public static function make(string $name, ?Field $field = null): FilamentFileUpload
    {
        $config = array_merge(self::getDefaultConfig(), $field->config ?? []);

        $component = FilamentFileUpload::make($name)
            ->label($field->name ?? null)
            ->disk($config['disk'])
            ->directory($config['directory'])
            ->visibility($config['visibility'])
            ->maxFiles($config['maxFiles'])
            ->multiple($config['multiple'])
            ->appendFiles($config['appendFiles'])
            ->reorderable($config['reorderable'])
            ->openable($config['openable'])
            ->downloadable($config['downloadable'])
            ->previewable($config['previewable'])
            ->deletable($config['deletable']);

        if ($config['acceptedFileTypes']) {
            $component->acceptedFileTypes(explode(',', $config['acceptedFileTypes']));
        }

        if ($config['maxSize']) {
            $component->maxSize($config['maxSize']);
        }

        return self::applyDefaultSettings($component, $field);
    }

    public static function mutateFormDataCallback(Model $record, Field $field, array $data): array
    {
        if (! property_exists($record, 'valueColumn') || ! isset($record->values[$field->ulid])) {
            return $data;
        }

        $data[$record->valueColumn][$field->ulid] = self::decodeFileValueForForm($record->values[$field->ulid]);

        return $data;
    }

    public static function mutateBeforeSaveCallback(Model $record, Field $field, array $data): array
    {
        if (! property_exists($record, 'valueColumn') || ! isset($data[$record->valueColumn][$field->ulid])) {
            return $data;
        }

        $data[$record->valueColumn][$field->ulid] = self::normalizeFileValue($data[$record->valueColumn][$field->ulid]);

        return $data;
    }

    private static function decodeFileValueForForm(mixed $value): array
    {
        if (is_null($value) || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && json_validate($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        if (is_string($value) && ! empty($value)) {
            return [$value];
        }

        if (! empty($value)) {
            return [(string) $value];
        }

        return [];
    }

    private static function normalizeFileValue(mixed $value): ?string
    {
        if (is_null($value) || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_string($value) && json_validate($value)) {
            return $value;
        }

        if (is_string($value) && ! empty($value)) {
            return json_encode([$value]);
        }

        if (! empty($value)) {
            return json_encode([(string) $value]);
        }

        return null;
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
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('config.disk')
                                        ->label(__('Storage Disk'))
                                        ->default('public')
                                        ->required(),

                                    TextInput::make('config.directory')
                                        ->label(__('Upload Directory'))
                                        ->default('uploads')
                                        ->required(),

                                    Select::make('config.visibility')
                                        ->label(__('File Visibility'))
                                        ->options([
                                            'public' => __('Public'),
                                            'private' => __('Private'),
                                        ])
                                        ->default('public')
                                        ->required(),

                                    TextInput::make('config.acceptedFileTypes')
                                        ->label(__('Accepted File Types'))
                                        ->placeholder('image/*,application/pdf')
                                        ->helperText(__('Comma-separated list of MIME types or file extensions')),

                                    TextInput::make('config.maxSize')
                                        ->label(__('Max File Size (KB)'))
                                        ->numeric()
                                        ->minValue(1),

                                    TextInput::make('config.maxFiles')
                                        ->label(__('Max Files'))
                                        ->numeric()
                                        ->minValue(1)
                                        ->default(1)
                                        ->required(),
                                ]),

                            Grid::make(2)
                                ->schema([
                                    Toggle::make('config.multiple')
                                        ->label(__('Multiple Files'))
                                        ->helperText(__('Allow multiple file selection'))
                                        ->live(),

                                    Toggle::make('config.appendFiles')
                                        ->label(__('Append Files'))
                                        ->helperText(__('Append new files to existing ones'))
                                        ->visible(fn (Get $get): bool => $get('config.multiple')),

                                    Toggle::make('config.reorderable')
                                        ->label(__('Reorderable'))
                                        ->helperText(__('Allow reordering of files'))
                                        ->visible(fn (Get $get): bool => $get('config.multiple')),

                                    Toggle::make('config.openable')
                                        ->label(__('Openable'))
                                        ->helperText(__('Allow opening files in new tab')),

                                    Toggle::make('config.downloadable')
                                        ->label(__('Downloadable'))
                                        ->helperText(__('Allow downloading files')),

                                    Toggle::make('config.previewable')
                                        ->label(__('Previewable'))
                                        ->helperText(__('Allow previewing files')),

                                    Toggle::make('config.deletable')
                                        ->label(__('Deletable'))
                                        ->helperText(__('Allow deleting files')),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
