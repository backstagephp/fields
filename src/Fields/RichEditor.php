<?php

namespace Backstage\Fields\Fields;

use Backstage\Enums\ToolbarButton;
use Backstage\Fields\Contracts\FieldContract;
<<<<<<< Updated upstream
use Backstage\Fields\Models\Field;
use Filament\Forms;
=======
use Backstage\Fields\Services\ContentCleaningService;
>>>>>>> Stashed changes
use Filament\Forms\Components\RichEditor as Input;

class RichEditor extends Base implements FieldContract
{
    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'disableGrammarly' => false,
            'toolbarButtons' => ['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'],
            'disableToolbarButtons' => [],
            'autoCleanContent' => true,
            'preserveCustomCaptions' => false,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? null)
            ->toolbarButtons($field->config['toolbarButtons'] ?? self::getDefaultConfig()['toolbarButtons'])
            ->disableGrammarly($field->config['disableGrammarly'] ?? self::getDefaultConfig()['disableGrammarly'])
            ->disableToolbarButtons($field->config['disableToolbarButtons'] ?? self::getDefaultConfig()['disableToolbarButtons']);

        // Add content processing to automatically clean HTML
        $autoCleanContent = $field->config['autoCleanContent'] ?? self::getDefaultConfig()['autoCleanContent'];
        
        if ($autoCleanContent) {
            $input->afterStateUpdated(function ($state) use ($field) {
                $options = [
                    'preserveCustomCaptions' => $field->config['preserveCustomCaptions'] ?? self::getDefaultConfig()['preserveCustomCaptions'],
                ];
                
                return ContentCleaningService::cleanHtmlContent($state, $options);
            });
        }

        return $input;
    }

    public function getForm(): array
    {
        return [
            Forms\Components\Tabs::make()
                ->schema([
                    Forms\Components\Tabs\Tab::make('General')
                        ->label(__('General'))
                        ->schema([
                            ...parent::getForm(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Forms\Components\Toggle::make('config.disableGrammarly')
                                ->inline(false)
                                ->label(__('Disable Grammarly')),
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('config.toolbarButtons')
                                        ->label(__('Toolbar buttons'))
                                        ->default(['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'h2', 'h3', 'italic', 'link', 'orderedList', 'redo', 'strike', 'underline', 'undo'])
                                        ->default(ToolbarButton::array()) // Not working in Filament yet.
                                        ->multiple()
                                        ->options(ToolbarButton::array())
                                        ->columnSpanFull(),
                                    Toggle::make('config.autoCleanContent')
                                        ->label(__('Auto-clean content'))
                                        ->helperText(__('Automatically remove figcaption and unwrap images from links'))
                                        ->default(true)
                                        ->columnSpanFull(),
                                    Toggle::make('config.preserveCustomCaptions')
                                        ->label(__('Preserve custom captions'))
                                        ->helperText(__('Only remove default captions, keep custom ones'))
                                        ->default(false)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
