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

        // Add content processing to automatically clean HTML
        $autoCleanContent = $field->config['autoCleanContent'] ?? self::getDefaultConfig()['autoCleanContent'];

        if ($autoCleanContent) {
            $options = [
                'preserveCustomCaptions' => $field->config['preserveCustomCaptions'] ?? self::getDefaultConfig()['preserveCustomCaptions'],
            ];

            // Clean content when state is updated (including file uploads)
            $input->afterStateUpdated(function ($state) use ($options) {
                if (! empty($state)) {
                    return ContentCleaningService::cleanHtmlContent($state, $options);
                }

                return $state;
            });

            // Ensure cleaned content is saved to database
            $input->dehydrateStateUsing(function ($state) use ($options) {
                if (! empty($state)) {
                    return ContentCleaningService::cleanHtmlContent($state, $options);
                }

                return $state;
            });
        }

        return $input;
    }

    public static function mutateBeforeSaveCallback($record, $field, array $data): array
    {
        $autoCleanContent = $field->config['autoCleanContent'] ?? self::getDefaultConfig()['autoCleanContent'];

        if ($autoCleanContent && isset($data['values'][$field->ulid])) {
            \Illuminate\Support\Facades\Log::info('RichEditor mutateBeforeSaveCallback before cleaning:', ['content' => $data['values'][$field->ulid]]);

            $options = [
                'preserveCustomCaptions' => $field->config['preserveCustomCaptions'] ?? self::getDefaultConfig()['preserveCustomCaptions'],
            ];

            $data['values'][$field->ulid] = ContentCleaningService::cleanHtmlContent($data['values'][$field->ulid], $options);

            \Illuminate\Support\Facades\Log::info('RichEditor mutateBeforeSaveCallback after cleaning:', ['content' => $data['values'][$field->ulid]]);
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
