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
                ->modalDescription(__('Add an anchor to the selected text that can be used for navigation.'))
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
                        ->required()
                        ->rules(['regex:/^[a-zA-Z0-9-_]+$/'])
                        // ->helperText(__('The ID that will be assigned to the span element (e.g., "section-1" for id="section-1")'))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $set) {
                            // Generate a slug-like ID if empty
                            if (empty($state)) {
                                $set('anchorId', 'anchor-' . uniqid());
                            }
                        }),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
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
