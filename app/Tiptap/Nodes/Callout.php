<?php

namespace App\Tiptap\Nodes;

use Tiptap\Core\Node;
use Tiptap\Utils\HTML;

/**
 * Callout block for TiptapConverter (PHP).
 * Parses and renders <div data-callout="type" class="callout callout-{type}"> for storage/API.
 */
class Callout extends Node
{
    public static $name = 'callout';

    public const VALID_TYPES = ['note', 'info', 'success', 'warning', 'danger'];

    public function addOptions(): array
    {
        return [
            'HTMLAttributes' => [],
        ];
    }

    public function addAttributes(): array
    {
        return [
            'type' => [
                'default' => 'note',
                'parseHTML' => function ($DOMNode) {
                    $type = $DOMNode->getAttribute('data-callout') ?: 'note';
                    return in_array($type, self::VALID_TYPES, true) ? $type : 'note';
                },
                'renderHTML' => function ($attributes) {
                    return ['data-callout' => $attributes->type ?? 'note'];
                },
            ],
        ];
    }

    public function parseHTML(): array
    {
        return [
            [
                'tag' => 'div[data-callout]',
                'getAttrs' => function ($dom) {
                    $type = $dom->getAttribute('data-callout') ?: 'note';
                    return in_array($type, self::VALID_TYPES, true) ? ['type' => $type] : false;
                },
            ],
            [
                'tag' => 'blockquote[data-callout]',
                'getAttrs' => function ($dom) {
                    $type = $dom->getAttribute('data-callout') ?: 'note';
                    return in_array($type, self::VALID_TYPES, true) ? ['type' => $type] : false;
                },
            ],
        ];
    }

    public function renderHTML($node, $HTMLAttributes = []): array
    {
        $type = $node->attrs->type ?? 'note';
        if (! in_array($type, self::VALID_TYPES, true)) {
            $type = 'note';
        }
        $attrs = HTML::mergeAttributes($this->options['HTMLAttributes'], $HTMLAttributes, [
            'data-callout' => $type,
            'class' => 'callout callout-' . $type,
        ]);

        return [
            'div',
            $attrs,
            0,
        ];
    }
}
