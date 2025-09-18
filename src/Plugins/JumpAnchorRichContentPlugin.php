<?php

namespace Backstage\Fields\Plugins;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\TextInput;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class JumpAnchorRichContentPlugin implements RichContentPlugin
{
    public static function get(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'jump-anchor';
    }

    public function getTipTapPhpExtensions(): array
    {
        return [];
    }

    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('rich-content-plugins/jump-anchor'),
        ];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('jumpAnchor')
                ->action(arguments: '{ anchorId: $getEditor().getAttributes(\'jumpAnchor\')?.[\'data-anchor-id\'] }')
                ->icon(Heroicon::Link)
                ->label('Add Jump Anchor'),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('jumpAnchor')
                ->modalHeading('Add Jump Anchor')
                ->modalDescription('Add an anchor to the selected text that can be used for navigation.')
                ->modalWidth(Width::Medium)
                ->fillForm(fn (array $arguments): array => [
                    'anchorId' => $arguments['anchorId'] ?? '',
                ])
                ->schema([
                    TextInput::make('anchorId')
                        ->label('Anchor ID')
                        ->placeholder('e.g., section-1')
                        ->required()
                        ->rules(['regex:/^[a-zA-Z0-9-_]+$/'])
                        ->helperText('Use only letters, numbers, hyphens, and underscores. No spaces allowed.')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Generate a slug-like ID if empty
                            if (empty($state)) {
                                $set('anchorId', 'anchor-' . uniqid());
                            }
                        }),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $component->runCommands(
                        [
                            EditorCommand::make(
                                'setJumpAnchor',
                                arguments: [
                                    [
                                        'anchorId' => $data['anchorId'],
                                    ],
                                ],
                            ),
                        ],
                        editorSelection: $arguments['editorSelection'],
                    );
                }),
        ];
    }
}
