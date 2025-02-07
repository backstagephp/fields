<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Facades\Fields;
use Illuminate\Support\Str;

trait HasConfigurableFields
{
    private function initializeConfig(string $fieldType): array
    {
        $className = Fields::getFields()[$fieldType] ??
            'Backstage\\Fields\\Fields\\' . Str::studly($fieldType);

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
