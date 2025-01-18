<?php

namespace Vormkracht10\Fields\Concerns;

use Illuminate\Support\Str;
use Vormkracht10\Fields\Facades\Fields;

trait HasConfigurableFields
{
    private function initializeConfig(string $fieldType): array
    {
        $className = Fields::getFields()[$fieldType] ??
            'Vormkracht10\\Fields\\Fields\\' . Str::studly($fieldType);

        if (! class_exists($className)) {
            return [];
        }

        $fieldInstance = app($className);

        return $fieldInstance::getDefaultConfig();
    }

    private function prepareCustomFieldOptions(array $fields): array
    {
        return collect($fields)->mapWithKeys(function ($field, $key) {
            $lastPart = Str::afterLast($field, '\\');

            $formattedName = Str::of($lastPart)
                ->snake()
                ->replace('_', ' ')
                ->title()
                ->value();

            return [$key => $formattedName];
        })->toArray();
    }
}