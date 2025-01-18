<?php

namespace Vormkracht10\Fields\Concerns;

trait HasSerializableEnumArray
{
    use HasEnumNames;
    use HasEnumValues;

    public static function array(): array
    {
        return array_combine(static::values(), static::names());
    }
}
