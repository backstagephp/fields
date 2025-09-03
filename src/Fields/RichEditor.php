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
                if (empty($state)) {
                    return null;
                }

                if (is_string($state)) {
                    $decoded = json_decode($state, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                    
                    return $state;
                }

                if (is_array($state)) {
                    if (isset($state[0]) && is_array($state[0]) && isset($state[0]['type']) && $state[0]['type'] === 'doc') {
                        $state = $state[0];
                    }

                    if (isset($state['content']) && is_array($state['content'])) {
                        $content = $state['content'];
                        if (count($content) > 0 && is_array($content[0]) && empty($content[0])) {
                            $state['content'] = [];
                        }
                    }

                    if (! isset($state['type']) || $state['type'] !== 'doc') {
                        return null;
                    }

                    if (! isset($state['content']) || ! is_array($state['content'])) {
                        $state['content'] = [];
                    }

                    return $state;
                }

                return null;
            })

            ->dehydrateStateUsing(function ($state) {
                if (empty($state)) {
                    return null;
                }

                if (is_string($state)) {
                    return $state;
                }

                if (is_array($state)) {
                    if (isset($state[0]) && is_array($state[0]) && isset($state[0]['type']) && $state[0]['type'] === 'doc') {
                        $state = $state[0];
                    }

                    if (isset($state['content']) && is_array($state['content'])) {
                        $content = $state['content'];
                        if (count($content) > 0 && is_array($content[0]) && empty($content[0])) {
                            $state['content'] = [];
                        }
                    }

                    if (! isset($state['type']) || $state['type'] !== 'doc') {
                        return null;
                    }

                    if (! isset($state['content']) || ! is_array($state['content'])) {
                        $state['content'] = [];
                    }

                    return $state;
                }

                return null;
            });


        $hideCaptions = $field->config['hideCaptions'] ?? self::getDefaultConfig()['hideCaptions'];
        if ($hideCaptions) {
            $input->extraAttributes(['data-hide-captions' => 'true']);
        }

        return $input;
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

    private static function ensureRichEditorDataFormat($record, $field, array $data): array
    {
        if (isset($data['values'][$field->ulid])) {
            $value = $data['values'][$field->ulid];
            if (empty($value)) {
                $data['values'][$field->ulid] = '';
            }
        } elseif (isset($data[$record->valueColumn][$field->ulid])) {
            $value = $data[$record->valueColumn][$field->ulid];
            if (empty($value)) {
                $data[$record->valueColumn][$field->ulid] = '';
            }
        }

        return $data;
    }

    public static function mutateFormDataCallback($record, $field, array $data): array
    {
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
