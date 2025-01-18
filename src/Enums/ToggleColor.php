<?php

namespace Vormkracht10\FilamentFields\Enums;

use Vormkracht10\FilamentFields\Concerns\HasSerializableEnumArray;

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
