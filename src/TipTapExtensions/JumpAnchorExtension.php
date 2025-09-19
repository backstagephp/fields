<?php

namespace Backstage\Fields\TipTapExtensions;

use Tiptap\Core\Mark;
use Tiptap\Utils\HTML;

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
                'parseHTML' => fn ($DOMNode) => $DOMNode->getAttribute('id') ?: $DOMNode->getAttribute('data-anchor-id'),
            ],
            'attributeType' => [
                'default' => 'id',
                'parseHTML' => fn ($DOMNode) => $DOMNode->hasAttribute('id') ? 'id' : 'custom',
            ],
            'customAttribute' => [
                'default' => null,
                'parseHTML' => fn ($DOMNode) => null,
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
                    'attributeType' => 'id',
                ],
            ],
            [
                'tag' => 'span',
                'getAttrs' => function ($DOMNode) {
                    // Check for any custom attribute that looks like an anchor
                    foreach ($DOMNode->attributes as $attribute) {
                        if (strpos($attribute->name, 'data-') === 0 && $attribute->name !== 'data-anchor-id') {
                            return [
                                'anchorId' => $attribute->value,
                                'attributeType' => 'custom',
                                'customAttribute' => $attribute->name,
                            ];
                        }
                    }
                    return false;
                },
            ],
        ];
    }

    public function renderHTML($mark, $HTMLAttributes = []): array
    {
        $attributes = (array) $mark->attrs;
        
        if (empty($attributes['anchorId'])) {
            return ['span', $HTMLAttributes, 0];
        }

        // Always use the mark attributes, not the HTMLAttributes parameter
        $result = [];
        $attributeType = $attributes['attributeType'] ?? 'id';
        $customAttribute = $attributes['customAttribute'] ?? null;

        if ($attributeType === 'id') {
            $result['id'] = $attributes['anchorId'];
        } elseif ($attributeType === 'custom' && $customAttribute) {
            $result[$customAttribute] = $attributes['anchorId'];
        }

        return ['span', $result, 0];
    }
}
