<?php

namespace Backstage\Fields\TipTapExtensions;

use Tiptap\Core\Mark;

class JumpAnchorExtension extends Mark
{
    public static $name = 'jumpAnchor';

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function addAttributes(): array
    {
        return [
            'anchorId' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('id'),
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'span[id]',
                'getAttrs' => fn ($DOMNode) => [
                    'anchorId' => $DOMNode->getAttribute('id'),
                ],
            ],
        ];
    }

    public function renderHTML($mark, $HTMLAttributes = []): array
    {
        $attributes = (array) $mark->attrs;

        if (empty($attributes['anchorId'])) {
            return ['span', $HTMLAttributes, 0];
        }

        return ['span', ['id' => $attributes['anchorId']], 0];
    }
}
