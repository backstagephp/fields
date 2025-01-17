<?php

namespace Vormkracht10\Fields;

use Illuminate\Support\Str;

class Fields
{
    private static array $fields = [];

    public static function registerField(string $className): void
    {
        $name = Str::kebab(class_basename($className));

        static::$fields[$name] = $className;
    }

    public static function getFields(): array
    {
        return static::$fields;
    }

    public static function resolveField($slug)
    {
        return static::$fields[$slug] ?? null;
    }
}