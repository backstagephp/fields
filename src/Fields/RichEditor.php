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
            ->disableToolbarButtons($field->config['disableToolbarButtons'] ?? self::getDefaultConfig()['disableToolbarButtons'])
            ->default(null)
            ->placeholder('')
            ->statePath($name)
            ->live()
            ->formatStateUsing(function ($state) {
                // Handle malformed data that might be double-encoded or corrupted
                $validatedState = self::validateAndFixRichEditorState($state);
                
                // If state is null, return null (Tiptap handles null better than empty string)
                if ($validatedState === null) {
                    return null;
                }
                
                // For Filament v4 RichEditor, we need to return the array format, not HTML
                // The error suggests Filament expects ?array for $rawState
                if (is_string($validatedState)) {
                    // If we have HTML, convert it back to array format for Filament
                    $arrayFormat = self::convertHtmlToArray($validatedState);
                    return $arrayFormat;
                }
                
                // If it's already an array, return it as-is
                if (is_array($validatedState)) {
                    return $validatedState;
                }
                
                return $validatedState;
            })
            ->beforeStateDehydrated(function ($state) {
                // Handle malformed data that might be double-encoded or corrupted
                $validatedState = self::validateAndFixRichEditorState($state);
                
                // If state is null, return null
                if ($validatedState === null) {
                    return null;
                }
                
                // For Filament v4, beforeStateDehydrated expects array format
                if (is_string($validatedState)) {
                    // If we have HTML, convert it to array format for Filament
                    $arrayFormat = self::convertHtmlToArray($validatedState);
                    return $arrayFormat;
                }
                
                // If it's already an array, return it as-is
                if (is_array($validatedState)) {
                    return $validatedState;
                }
                
                return $validatedState;
            })
            ->dehydrateStateUsing(function ($state) {
                // Handle malformed data that might be double-encoded or corrupted
                $state = self::validateAndFixRichEditorState($state);
                
                // If state is null, return null for dehydration
                if ($state === null) {
                    return null;
                }
                
                // For Filament v4, we need to return HTML string for dehydration
                if (is_array($state)) {
                    $html = self::convertArrayToHtml($state);
                    return $html;
                }
                
                return $state;
            });

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
        // Handle null, empty string, or empty array
        if (empty($state) || $state === null) {
            return self::getEmptyRichEditorArray();
        }

        // Handle Filament v4 RichEditor format (array) vs legacy format (string)
        if (is_array($state)) {
            // For Filament v4, the content is in array format
            // We need to convert it to HTML, clean it, and convert back
            $html = self::convertArrayToHtml($state);
            if ($html) {
                $cleanedHtml = ContentCleaningService::cleanHtmlContent($html, $options);
                $result = self::convertHtmlToArray($cleanedHtml);
                return $result;
            }
            // If no HTML content, return empty array structure
            return self::getEmptyRichEditorArray();
        }

        // For legacy string format, clean directly
        if (is_string($state)) {
            $cleanedHtml = ContentCleaningService::cleanHtmlContent($state, $options);
            $result = self::convertHtmlToArray($cleanedHtml);
            return $result;
        }

        // Fallback for any other type
        return self::getEmptyRichEditorArray();
    }

    /**
     * Convert Filament v4 RichEditor array format to HTML
     */
    private static function convertArrayToHtml($state): string
    {
        // Handle null, empty, or non-array states
        if (empty($state) || $state === null || !is_array($state)) {
            return '';
        }

        if (!isset($state['type']) || $state['type'] !== 'doc') {
            return '';
        }

        if (!isset($state['content']) || empty($state['content'])) {
            return '';
        }

        return self::convertNodeToHtml($state);
    }

    /**
     * Convert a node to HTML recursively
     */
    private static function convertNodeToHtml(array $node): string
    {
        // Safety check for empty or invalid nodes
        if (empty($node) || !is_array($node)) {
            return '';
        }

        $html = '';

        if (isset($node['content']) && is_array($node['content'])) {
            foreach ($node['content'] as $child) {
                if (is_array($child)) {
                    $html .= self::convertNodeToHtml($child);
                }
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
        if (isset($data['values'][$field->ulid])) {
            $value = $data['values'][$field->ulid];
            if (is_string($value) || empty($value) || $value === null) {
                $converted = self::convertStringToArray($value);
                $data['values'][$field->ulid] = $converted;
            }
        } elseif (isset($data[$record->valueColumn][$field->ulid])) {
            $value = $data[$record->valueColumn][$field->ulid];
            if (is_string($value) || empty($value) || $value === null) {
                $converted = self::convertStringToArray($value);
                $data[$record->valueColumn][$field->ulid] = $converted;
            }
        }

        return $data;
    }

    public static function mutateFormDataCallback($record, $field, array $data): array
    {
        // Convert string values to array format for Filament v4 RichEditor
        if (isset($data['values'][$field->ulid])) {
            $value = $data['values'][$field->ulid];
            if (is_string($value) || empty($value) || $value === null) {
                $data['values'][$field->ulid] = self::convertStringToArray($value);
            }
        } elseif (isset($data[$record->valueColumn][$field->ulid])) {
            $value = $data[$record->valueColumn][$field->ulid];
            if (is_string($value) || empty($value) || $value === null) {
                $data[$record->valueColumn][$field->ulid] = self::convertStringToArray($value);
            }
        }

        return $data;
    }

    /**
     * Convert HTML string to Filament v4 RichEditor array format
     */
    private static function convertStringToArray(?string $html): array
    {
        if (empty($html) || $html === null) {
            return [
                'type' => 'doc',
                'content' => []
            ];
        }

        // For now, create a simple paragraph structure
        // In a more sophisticated implementation, you'd parse the HTML properly
        $text = strip_tags($html);
        
        if (empty($text)) {
            return [
                'type' => 'doc',
                'content' => []
            ];
        }
        
        return [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $text
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Validate and fix malformed RichEditor state data
     */
    private static function validateAndFixRichEditorState($state)
    {
        // Handle null or empty states
        if (empty($state) || $state === null) {
            return null;
        }

        // Handle string states (might be JSON or HTML)
        if (is_string($state)) {
            $decoded = json_decode($state, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // Return the decoded array directly
                return $decoded;
            } else {
                return $state;
            }
        }

        // Handle array states
        if (is_array($state)) {
            // Check for malformed data like the one in the error: [{"type":"doc","content":[[],{"s":"arr"}]},{"s":"arr"}]
            if (isset($state[0]) && is_array($state[0]) && isset($state[0]['type']) && $state[0]['type'] === 'doc') {
                $state = $state[0];
            }

            // Check for corrupted content structure
            if (isset($state['content']) && is_array($state['content'])) {
                $content = $state['content'];
                // Look for corrupted content like [[],{"s":"arr"}]
                if (count($content) > 0 && is_array($content[0]) && empty($content[0])) {
                    $state['content'] = [];
                }
            }

            // Ensure the structure is valid
            if (!isset($state['type']) || $state['type'] !== 'doc') {
                return null;
            }

            if (!isset($state['content']) || !is_array($state['content'])) {
                $state['content'] = [];
            }

            // Return the validated array directly
            return $state;
        }

        // Fallback for any other type
        return null;
    }

    /**
     * Get empty RichEditor array structure for new content
     */
    private static function getEmptyRichEditorArray(): array
    {
        return [
            'type' => 'doc',
            'content' => []
        ];
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
