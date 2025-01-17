<?php

namespace Vormkracht10\FilamentFields\Concerns;

trait HasEnumNames
{
    abstract public static function cases(): array;

    public static function names(): array
    {
        return array_map(fn($enum) => $enum->name, static::cases());
    }
}