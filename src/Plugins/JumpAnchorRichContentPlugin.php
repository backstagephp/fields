<?php

namespace Backstage\Fields\Plugins;

use Backstage\Fields\TipTapExtensions\JumpAnchorExtension;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentAsset;
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
        return [
            app(JumpAnchorExtension::class),
        ];
    }

    public function getTipTapJsExtensions(): array
    {
        return [
            FilamentAsset::getScriptSrc('rich-content-plugins/jump-anchor', 'backstage/fields'),
        ];
    }

    public function getEditorTools(): array
    {
        return [
            RichEditorTool::make('jumpAnchor')
                ->action(arguments: '{ anchorId: $getEditor().getAttributes(\'jumpAnchor\')?.[\'anchorId\'] }')
                ->icon(Heroicon::Hashtag)
                ->label(__('Add Jump Anchor')),
        ];
    }

    public function getEditorActions(): array
    {
        return [
            Action::make('jumpAnchor')
                ->modalHeading(__('Add Jump Anchor'))
                ->modalDescription(__('Add an anchor ID to the selected text so it can be linked to directly. For example, an anchor with ID "contact" can be reached via /page-url#contact.'))
                ->modalWidth(Width::Medium)
                ->modalSubmitActionLabel(__('Save'))
                ->fillForm(fn (array $arguments): array => [
                    'anchorId' => $arguments['anchorId'] ?? '',
                    'attributeType' => $arguments['attributeType'] ?? 'id',
                    'customAttribute' => $arguments['customAttribute'] ?? '',
                ])
                ->schema([
                    TextInput::make('anchorId')
                        ->label(__('Anchor ID'))
                        ->placeholder(__('e.g., section-1, my-anchor'))
                        ->rules(['regex:/^[a-zA-Z0-9-_]*$/'])
                        ->helperText(__('Allowed characters: letters, numbers, hyphens and underscores. Clear the field and save to remove the anchor.'))
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (empty($state)) {
                                return;
                            }

                            $safe = preg_replace(
                                ['/\s+/', '/[^a-zA-Z0-9\-_]/', '/-{2,}/'],
                                ['-', '', '-'],
                                $state,
                            );
                            $safe = trim($safe, '-');

                            if ($safe !== $state) {
                                $set('anchorId', $safe);
                            }
                        }),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    if (empty($data['anchorId'])) {
                        $component->runCommands(
                            [
                                EditorCommand::make('unsetJumpAnchor'),
                            ],
                            editorSelection: $arguments['editorSelection'],
                        );

                        return;
                    }

                    $attributes = [
                        'anchorId' => $data['anchorId'],
                        'attributeType' => 'id',
                    ];

                    $component->runCommands(
                        [
                            EditorCommand::make(
                                'setJumpAnchor',
                                arguments: [$attributes],
                            ),
                        ],
                        editorSelection: $arguments['editorSelection'],
                    );
                }),
        ];
    }
}
