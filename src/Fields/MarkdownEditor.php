<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ToolbarButton;
use Backstage\Fields\Models\Field;
use Backstage\Fields\Services\ContentCleaningService;
use Filament\Forms;
use Filament\Forms\Components\RichEditor as Input;

class MarkdownEditor extends Base implements FieldContract
{
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
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\Select::make('config.toolbarButtons')
                                        ->label(__('Toolbar buttons'))
                                        ->default(['attachFiles', 'blockquote', 'bold', 'bulletList', 'codeBlock', 'heading', 'italic', 'link', 'orderedList', 'redo', 'strike', 'table', 'undo'])
                                        ->default(ToolbarButton::array()) // Not working in Filament yet.
                                        ->multiple()
                                        ->options(ToolbarButton::array())
                                        ->columnSpanFull(),
                                    Forms\Components\Grid::make(3)
                                        ->schema([
                                            Forms\Components\TextInput::make('config.fileAttachmentsDisk')
                                                ->label(__('File attachments disk'))
                                                ->default('public'),
                                            Forms\Components\TextInput::make('config.fileAttachmentsDirectory')
                                                ->label(__('File attachments directory'))
                                                ->default('attachments'),
                                            Forms\Components\TextInput::make('config.fileAttachmentsVisibility')
                                                ->label(__('File attachments visibility'))
                                                ->default('public'),
                                        ])
                                ]),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
