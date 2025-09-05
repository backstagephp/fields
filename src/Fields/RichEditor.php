<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ToolbarButton;
use Backstage\Fields\Models\Field;
use Backstage\Fields\Services\ContentCleaningService;
use Filament\Forms;
use Filament\Forms\Components\RichEditor as Input;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class RichEditor extends Base implements FieldContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'toolbarButtons' => ['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'],
            'disableToolbarButtons' => [],
            'autoCleanContent' => true,
            'preserveCustomCaptions' => false,
            'hideCaptions' => true,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::createBaseInput($name, $field);
        $input = self::configureToolbarButtons($input, $field);
        $input = self::configureStateHandling($input, $name);
        $input = self::configureCaptions($input, $field);

        return $input;
    }

    private static function createBaseInput(string $name, ?Field $field): Input
    {
        return self::applyDefaultSettings(Input::make($name), $field)
            ->label($field->name ?? null)
            ->default(null)
            ->placeholder('')
            ->statePath($name)
            ->live()
            ->json(false)
            ->beforeStateDehydrated(function () {})
            ->saveRelationshipsUsing(function () {});
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

    private static function configureCaptions(Input $input, ?Field $field): Input
    {
        $hideCaptions = $field->config['hideCaptions'] ?? self::getDefaultConfig()['hideCaptions'];

        if ($hideCaptions) {
            $input->extraAttributes(['data-hide-captions' => 'true']);
        }

        return $input;
    }

    private static function formatRichEditorState($state)
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

    public static function cleanRichEditorState($state, array $options = [])
    {
        if (empty($state)) {
            return '';
        }

        $cleanedState = ContentCleaningService::cleanContent($state, $options);

        return $cleanedState;
    }

    public static function mutateBeforeSaveCallback($record, $field, array $data): array
    {
        $data = self::ensureRichEditorDataFormat($record, $field, $data);

        if (self::shouldAutoCleanContent($field)) {
            $data = self::applyContentCleaning($record, $field, $data);
        }

        return $data;
    }

    private static function shouldAutoCleanContent($field): bool
    {
        return $field->config['autoCleanContent'] ?? self::getDefaultConfig()['autoCleanContent'];
    }

    private static function applyContentCleaning($record, $field, array $data): array
    {
        $options = self::getCleaningOptions($field);

        if (isset($data['values'][$field->ulid])) {
            // Called from ContentResource
            $data['values'][$field->ulid] = self::cleanRichEditorState($data['values'][$field->ulid], $options);
        } elseif (isset($data[$record->valueColumn][$field->ulid])) {
            // Called from CanMapDynamicFields trait
            $data[$record->valueColumn][$field->ulid] = self::cleanRichEditorState($data[$record->valueColumn][$field->ulid], $options);
        }

        return $data;
    }

    private static function getCleaningOptions($field): array
    {
        return [
            'preserveCustomCaptions' => $field->config['preserveCustomCaptions'] ?? self::getDefaultConfig()['preserveCustomCaptions'],
        ];
    }

    private static function ensureRichEditorDataFormat($record, $field, array $data): array
    {
        $data = self::normalizeContentResourceValue($data, $field);
        $data = self::normalizeDynamicFieldValue($record, $data, $field);

        return $data;
    }

    private static function normalizeContentResourceValue(array $data, $field): array
    {
        if (isset($data['values'][$field->ulid]) && empty($data['values'][$field->ulid])) {
            $data['values'][$field->ulid] = '';
        }

        return $data;
    }

    private static function normalizeDynamicFieldValue($record, array $data, $field): array
    {
        if (isset($data[$record->valueColumn][$field->ulid]) && empty($data[$record->valueColumn][$field->ulid])) {
            $data[$record->valueColumn][$field->ulid] = '';
        }

        return $data;
    }

    public static function mutateFormDataCallback($record, $field, array $data): array
    {
        // Get the raw value from the database without JSON decoding
        $rawValue = $record->values()->where('field_ulid', $field->ulid)->first()?->value;

        if ($rawValue !== null) {
            $data[$record->valueColumn][$field->ulid] = $rawValue;
        }

        return $data;
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
                                        ->default(['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'])
                                        ->default(ToolbarButton::array()) // Not working in Filament yet.
                                        ->multiple()
                                        ->options(ToolbarButton::array())
                                        ->columnSpanFull(),
                                    Forms\Components\Toggle::make('config.autoCleanContent')
                                        ->label(__('Auto-clean content'))
                                        ->helperText(__('Automatically remove figcaption and unwrap images from links'))
                                        ->inline(false)
                                        ->columnSpanFull(),
                                    Forms\Components\Toggle::make('config.preserveCustomCaptions')
                                        ->label(__('Preserve custom captions'))
                                        ->helperText(__('Only remove default captions, keep custom ones'))
                                        ->inline(false)
                                        ->columnSpanFull(),
                                    Forms\Components\Toggle::make('config.hideCaptions')
                                        ->label(__('Hide caption fields'))
                                        ->helperText(__('Hide the caption input field that appears when uploading images'))
                                        ->inline(false)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
