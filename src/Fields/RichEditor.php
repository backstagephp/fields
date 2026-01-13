<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ToolbarButton;
use Backstage\Fields\Models\Field;
use Backstage\Fields\Plugins\JumpAnchorRichContentPlugin;
use Filament\Forms\Components\RichEditor as Input;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Model;

class RichEditor extends Base implements FieldContract
{
    public function getFieldType(): ?string
    {
        return 'rich-editor';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'toolbarButtons' => ['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'jumpAnchor', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'],
            'disableToolbarButtons' => [],
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::createBaseInput($name, $field);
        $input = self::configureToolbarButtons($input, $field);
        $input = self::configureStateHandling($input, $name);

        return $input;
    }

    private static function createBaseInput(string $name, ?Field $field): Input
    {
        return self::applyDefaultSettings(Input::make($name), $field)
            ->label($field->name ?? null)
            ->default(null)
            ->placeholder('')
            ->statePath($name)
            ->json(true)
            ->beforeStateDehydrated(function () {})
            ->saveRelationshipsUsing(function () {})
            ->plugins([
                JumpAnchorRichContentPlugin::get(),
            ]);
    }

    private static function configureToolbarButtons(Input $input, ?Field $field): Input
    {
        $config = self::getDefaultConfig();

        return $input
            ->toolbarButtons([$field->config['toolbarButtons'] ?? $config['toolbarButtons']])
            ->disableToolbarButtons($field->config['disableToolbarButtons'] ?? $config['disableToolbarButtons']);
    }

    private static function configureStateHandling(Input $input, string $name): Input
    {
        return $input->formatStateUsing(function ($state) {
            return self::formatRichEditorState($state);
        });
    }

    private static function formatRichEditorState(mixed $state): mixed
    {
        if (empty($state)) {
            return null;
        }

        // If it's already a string (HTML), return it as is
        if (is_string($state)) {
            return $state;
        }

        // If it's an array (JSON format), handle it
        if (is_array($state)) {
            return self::formatJsonState($state);
        }

        return null;
    }

    private static function formatJsonState(array $state): ?array
    {
        // Handle nested doc structure
        if (isset($state[0]) && is_array($state[0]) && isset($state[0]['type']) && $state[0]['type'] === 'doc') {
            $state = $state[0];
        }

        // Clean up empty content arrays
        if (isset($state['content']) && is_array($state['content'])) {
            $state = self::cleanContentArray($state);
        }

        // Validate doc structure
        if (! isset($state['type']) || $state['type'] !== 'doc') {
            return null;
        }

        if (! isset($state['content']) || ! is_array($state['content'])) {
            $state['content'] = [];
        }

        return $state;
    }

    private static function cleanContentArray(array $state): array
    {
        $content = $state['content'];
        if (count($content) > 0 && is_array($content[0]) && empty($content[0])) {
            $state['content'] = [];
        }

        return $state;
    }

    public static function mutateBeforeSaveCallback(Model $record, Field $field, array $data): array
    {
        $data = self::ensureRichEditorDataFormat($record, $field, $data);

        return $data;
    }

    private static function ensureRichEditorDataFormat(Model $record, Field $field, array $data): array
    {
        $valueColumn = $record->valueColumn ?? 'values';
        $data = self::normalizeContentResourceValue($data, $field);
        $data = self::normalizeDynamicFieldValue($record, $data, $field, $valueColumn);

        return $data;
    }

    private static function normalizeContentResourceValue(array $data, Field $field): array
    {
        if (isset($data['values'][$field->ulid]) && empty($data['values'][$field->ulid])) {
            $data['values'][$field->ulid] = '';
        }

        return $data;
    }

    private static function normalizeDynamicFieldValue(Model $record, array $data, Field $field, string $valueColumn): array
    {
        if (isset($data[$valueColumn][$field->ulid]) && empty($data[$valueColumn][$field->ulid])) {
            $data[$valueColumn][$field->ulid] = '';
        }

        return $data;
    }

    public static function mutateFormDataCallback(Model $record, Field $field, array $data): array
    {
        $rawValue = self::getFieldValueFromRecord($record, $field);

        if ($rawValue !== null) {
            $valueColumn = $record->valueColumn ?? 'values';
            $data[$valueColumn][$field->ulid] = $rawValue;
        }

        return $data;
    }

    private static function getFieldValueFromRecord(Model $record, Field $field): mixed
    {
        // Check if record has values method
        if (! method_exists($record, 'values')) {
            return null;
        }

        $values = $record->values();

        // Handle relationship-based values (like Content model)
        if (self::isRelationship($values)) {
            return $values->where('field_ulid', $field->ulid)->first()?->value;
        }

        // Handle array/collection-based values (like Settings model)
        if (is_array($values) || $values instanceof \Illuminate\Support\Collection) {
            return $values[$field->ulid] ?? null;
        }

        return $record->values[$field->ulid] ?? null;
    }

    private static function isRelationship(mixed $values): bool
    {
        return is_object($values)
            && method_exists($values, 'where')
            && method_exists($values, 'get')
            && ! ($values instanceof \Illuminate\Support\Collection);
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
                                    Select::make('config.toolbarButtons')
                                        ->label(__('Toolbar buttons'))
                                        ->default(['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'jumpAnchor', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'])
                                        ->default(ToolbarButton::array()) // Not working in Filament yet.
                                        ->multiple()
                                        ->options(ToolbarButton::array())
                                        ->columnSpanFull(),
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
