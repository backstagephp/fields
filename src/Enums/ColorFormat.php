<?php

namespace Vormkracht10\Fields\Enums;

use Vormkracht10\Fields\Concerns\HasSerializableEnumArray;

enum ColorFormat: string
{
    use HasSerializableEnumArray;

    case HEX = 'hex';
    case RGB = 'rgb';
    case RGBA = 'rgba';
    case HSL = 'hsl';
}
