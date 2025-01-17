<?php

namespace Vormkracht10\FilamentFields\Concerns;

use Vormkracht10\FilamentFields\Concerns\HasEnumNames;
use Vormkracht10\FilamentFields\Concerns\HasEnumValues;

trait HasSerializableEnumArray
{
    use HasEnumNames;
    use HasEnumValues;

    public static function array(): array
    {
        return array_combine(static::values(), static::names());
    }
}