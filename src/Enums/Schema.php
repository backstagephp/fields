<?php

namespace Backstage\Fields\Enums;

use Backstage\Fields\Concerns\HasSerializableEnumArray;

enum Schema: string
{
    use HasSerializableEnumArray;

    case Section = 'section';
    case Grid = 'grid';
}
