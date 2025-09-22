<?php

namespace Backstage\Fields\Enums;

use Backstage\Fields\Concerns\HasSerializableEnumArray;

enum Field: string
{
    use HasSerializableEnumArray;

    case Checkbox = 'checkbox';
    case CheckboxList = 'checkbox-list';
    case Color = 'color';
    case DateTime = 'date-time';
    case File = 'file-upload';
    // case Hidden = 'hidden';
    case KeyValue = 'key-value';
    // case Link = 'link';
    case MarkdownEditor = 'markdown-editor';
    case Radio = 'radio';
    case Repeater = 'repeater';
    case RichEditor = 'rich-editor';
    case Select = 'select';
    case Tags = 'tags';
    case Text = 'text';
    case Textarea = 'textarea';
    case Toggle = 'toggle';
    // case ToggleButtons = 'toggle-buttons';
}
