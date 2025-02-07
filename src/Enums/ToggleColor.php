<?php

namespace Backstage\Fields\Enums;

use Backstage\Fields\Concerns\HasSerializableEnumArray;

enum ToggleColor: string
{
    use HasSerializableEnumArray;

    case DANGER = 'danger';
    case GRAY = 'gray';
    case INFO = 'info';
    case PRIMARY = 'primary';
    case SUCCESS = 'success';
    case WARNING = 'warning';
}
