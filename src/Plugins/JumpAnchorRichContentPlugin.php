<?php

namespace Backstage\Fields\Plugins;

use Backstage\Fields\TipTapExtensions\JumpAnchorExtension;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\RichEditor\EditorCommand;
use Filament\Forms\Components\RichEditor\Plugins\Contracts\RichContentPlugin;
use Filament\Forms\Components\RichEditor\RichEditorTool;
use Filament\Forms\Components\Select;
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
                ->action(arguments: '{ anchorId: $getEditor().getAttributes(\'jumpAnchor\')?.[\'data-anchor-id\'] }')
                ->icon(Heroicon::Hashtag)
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
                ->modalSubmitActionLabel(__('Save'))
                ->fillForm(fn (array $arguments): array => [
                    'anchorId' => $arguments['anchorId'] ?? '',
                    'attributeType' => $arguments['attributeType'] ?? 'id',
                    'customAttribute' => $arguments['customAttribute'] ?? '',
                ])
                ->schema([
                    Select::make('attributeType')
                        ->label('Attribute Type')
                        ->options([
                            'id' => 'ID (for standard HTML anchors)',
                            'custom' => 'Custom Attribute',
                        ])
                        ->default('id')
                        ->live()
                        ->required(),

                    TextInput::make('anchorId')
                        ->label('Anchor Value')
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

                    TextInput::make('customAttribute')
                        ->label('Custom Attribute Name')
                        ->placeholder('e.g., data-section, data-anchor')
                        ->visible(fn (callable $get) => $get('attributeType') === 'custom')
                        ->required(fn (callable $get) => $get('attributeType') === 'custom')
                        ->rules(['regex:/^[a-zA-Z0-9-_]+$/'])
                        ->helperText('Custom attribute name (without data- prefix)'),
                ])
                ->action(function (array $arguments, array $data, RichEditor $component): void {
                    $attributes = [
                        'anchorId' => $data['anchorId'],
                        'attributeType' => $data['attributeType'],
                    ];

                    if ($data['attributeType'] === 'custom' && ! empty($data['customAttribute'])) {
                        $attributes['customAttribute'] = $data['customAttribute'];
                    }

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
