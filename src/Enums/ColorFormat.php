<?php

namespace Backstage\Fields\Enums;

use Backstage\Fields\Concerns\HasSerializableEnumArray;

enum ColorFormat: string
{
    use HasSerializableEnumArray;

    case HEX = 'hex';
    case RGB = 'rgb';
    case RGBA = 'rgba';
    case HSL = 'hsl';
}
