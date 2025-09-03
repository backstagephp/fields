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

        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? null)
            ->toolbarButtons([$field->config['toolbarButtons'] ?? self::getDefaultConfig()['toolbarButtons']])
            ->disableToolbarButtons($field->config['disableToolbarButtons'] ?? self::getDefaultConfig()['disableToolbarButtons']);

        // Add data attribute for hiding captions if enabled
        $hideCaptions = $field->config['hideCaptions'] ?? self::getDefaultConfig()['hideCaptions'];
        if ($hideCaptions) {
            $input->extraAttributes(['data-hide-captions' => 'true']);
        }

        // Content cleaning is handled in mutateBeforeSaveCallback to avoid state type conflicts

        return $input;
    }

    /**
     * Clean RichEditor content for database storage
     */
    public static function cleanRichEditorState($state, array $options = [])
    {
        if (empty($state)) {
            return $state;
        }

        // Handle Filament v4 RichEditor format (array) vs legacy format (string)
        if (is_array($state)) {
            // For Filament v4, the content is in array format
            // We need to convert it to HTML, clean it, and convert back
            $html = self::convertArrayToHtml($state);
            if ($html) {
                $cleanedHtml = ContentCleaningService::cleanHtmlContent($html, $options);
                return self::convertHtmlToArray($cleanedHtml);
            }
            return $state;
        }

        // For legacy string format, clean directly
        return ContentCleaningService::cleanHtmlContent($state, $options);
    }

    /**
     * Convert Filament v4 RichEditor array format to HTML
     */
    private static function convertArrayToHtml(array $state): ?string
    {
        if (!isset($state['type']) || $state['type'] !== 'doc') {
            return null;
        }

        return self::convertNodeToHtml($state);
    }

    /**
     * Convert a node to HTML recursively
     */
    private static function convertNodeToHtml(array $node): string
    {
        $html = '';

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                $html .= self::convertNodeToHtml($child);
            }
        }

        switch ($node['type'] ?? '') {
            case 'paragraph':
                $align = $node['attrs']['textAlign'] ?? 'start';
                $alignClass = $align !== 'start' ? " style=\"text-align: {$align}\"" : '';
                return "<p{$alignClass}>{$html}</p>";

            case 'text':
                $text = $node['text'] ?? '';
                $marks = $node['marks'] ?? [];
                
                foreach ($marks as $mark) {
                    switch ($mark['type'] ?? '') {
                        case 'bold':
                            $text = "<strong>{$text}</strong>";
                            break;
                        case 'italic':
                            $text = "<em>{$text}</em>";
                            break;
                        case 'underline':
                            $text = "<u>{$text}</u>";
                            break;
                        case 'strike':
                            $text = "<s>{$text}</s>";
                            break;
                        case 'code':
                            $text = "<code>{$text}</code>";
                            break;
                        case 'link':
                            $href = $mark['attrs']['href'] ?? '#';
                            $text = "<a href=\"{$href}\">{$text}</a>";
                            break;
                    }
                }
                return $text;

            case 'heading':
                $level = $node['attrs']['level'] ?? 1;
                return "<h{$level}>{$html}</h{$level}>";

            case 'bulletList':
                return "<ul>{$html}</ul>";

            case 'orderedList':
                return "<ol>{$html}</ol>";

            case 'listItem':
                return "<li>{$html}</li>";

            case 'blockquote':
                return "<blockquote>{$html}</blockquote>";

            case 'codeBlock':
                return "<pre><code>{$html}</code></pre>";

            case 'hardBreak':
                return '<br>';

            case 'horizontalRule':
                return '<hr>';

            default:
                return $html;
        }
    }

    /**
     * Convert HTML back to Filament v4 RichEditor array format
     * This is a simplified conversion - in practice, you might want to use a proper HTML parser
     */
    private static function convertHtmlToArray(string $html): array
    {
        // For now, return a simple paragraph structure
        // In a real implementation, you'd want to parse the HTML properly
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => strip_tags($html)
                        ]
                    ]
                ]
            ]
        ];
    }

    public static function mutateBeforeSaveCallback($record, $field, array $data): array
    {
        // First, ensure the data is in the correct format (array for Filament v4)
        $data = self::ensureRichEditorDataFormat($record, $field, $data);

        $autoCleanContent = $field->config['autoCleanContent'] ?? self::getDefaultConfig()['autoCleanContent'];

        if ($autoCleanContent) {
            $options = [
                'preserveCustomCaptions' => $field->config['preserveCustomCaptions'] ?? self::getDefaultConfig()['preserveCustomCaptions'],
            ];

            // Handle different data structures from different callers
            if (isset($data['values'][$field->ulid])) {
                // Called from ContentResource
                $data['values'][$field->ulid] = self::cleanRichEditorState($data['values'][$field->ulid], $options);
            } elseif (isset($data[$record->valueColumn][$field->ulid])) {
                // Called from CanMapDynamicFields trait
                $data[$record->valueColumn][$field->ulid] = self::cleanRichEditorState($data[$record->valueColumn][$field->ulid], $options);
            }
        }

        return $data;
    }

    /**
     * Ensure RichEditor data is in the correct format (array for Filament v4)
     */
    private static function ensureRichEditorDataFormat($record, $field, array $data): array
    {
        // Handle different data structures from different callers
        if (isset($data['values'][$field->ulid]) && is_string($data['values'][$field->ulid])) {
            $data['values'][$field->ulid] = self::convertStringToArray($data['values'][$field->ulid]);
        } elseif (isset($data[$record->valueColumn][$field->ulid]) && is_string($data[$record->valueColumn][$field->ulid])) {
            $data[$record->valueColumn][$field->ulid] = self::convertStringToArray($data[$record->valueColumn][$field->ulid]);
        }

        return $data;
    }

    public static function mutateFormDataCallback($record, $field, array $data): array
    {
        // Convert string values to array format for Filament v4 RichEditor
        if (isset($data['values'][$field->ulid]) && is_string($data['values'][$field->ulid])) {
            $data['values'][$field->ulid] = self::convertStringToArray($data['values'][$field->ulid]);
        } elseif (isset($data[$record->valueColumn][$field->ulid]) && is_string($data[$record->valueColumn][$field->ulid])) {
            $data[$record->valueColumn][$field->ulid] = self::convertStringToArray($data[$record->valueColumn][$field->ulid]);
        }

        return $data;
    }

    /**
     * Convert HTML string to Filament v4 RichEditor array format
     */
    private static function convertStringToArray(?string $html): array
    {
        return \Backstage\Fields\Services\RichEditorDataService::convertStringToRichEditorArray($html);
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
