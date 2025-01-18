<?php

namespace Vormkracht10\FilamentFields\Enums;

use Vormkracht10\Fields\Concerns\HasSerializableEnumArray;

enum ColorFormat: string
{
    use HasSerializableEnumArray;

    case HEX = 'hex';
    case RGB = 'rgb';
    case RGBA = 'rgba';
    case HSL = 'hsl';
}
