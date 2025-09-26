<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ToolbarButton;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\RichEditor as Input;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class MarkdownEditor extends Base implements FieldContract
{
    public function getFieldType(): ?string
    {
        return 'markdown-editor';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'toolbarButtons' => ['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'heading', 'italic', 'link', 'orderedList', 'redo', 'strike', 'table', 'undo'],
            'disableToolbarButtons' => [],
            'fileAttachmentsDisk' => 'public',
            'fileAttachmentsDirectory' => 'attachments',
            'fileAttachmentsVisibility' => 'public',
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? null)
            ->toolbarButtons($field->config['toolbarButtons'] ?? self::getDefaultConfig()['toolbarButtons'])
            ->disableToolbarButtons($field->config['disableToolbarButtons'] ?? self::getDefaultConfig()['disableToolbarButtons'])
            ->fileAttachmentsDisk($field->config['fileAttachmentsDisk'] ?? self::getDefaultConfig()['fileAttachmentsDisk'])
            ->fileAttachmentsDirectory($field->config['fileAttachmentsDirectory'] ?? self::getDefaultConfig()['fileAttachmentsDirectory'])
            ->fileAttachmentsVisibility($field->config['fileAttachmentsVisibility'] ?? self::getDefaultConfig()['fileAttachmentsVisibility']);

        return $input;
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
                                        ->default(['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'heading', 'italic', 'link', 'orderedList', 'redo', 'strike', 'table', 'undo'])
                                        ->default(ToolbarButton::array()) // Not working in Filament yet.
                                        ->multiple()
                                        ->options(ToolbarButton::array())
                                        ->columnSpanFull(),
                                    Grid::make(3)
                                        ->schema([
                                            TextInput::make('config.fileAttachmentsDisk')
                                                ->label(__('File attachments disk'))
                                                ->default('public'),
                                            TextInput::make('config.fileAttachmentsDirectory')
                                                ->label(__('File attachments directory'))
                                                ->default('attachments'),
                                            TextInput::make('config.fileAttachmentsVisibility')
                                                ->label(__('File attachments visibility'))
                                                ->default('public'),
                                        ]),
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
