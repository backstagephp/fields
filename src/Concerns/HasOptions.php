<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms\Components\KeyValue;
use Filament\Schemas\Components\Fieldset;

trait HasOptions
{
    use HasSelectableValues;

    public static function addOptionsToInput(mixed $input, mixed $field): mixed
    {
        return static::addValuesToInput(
            input: $input,
            field: $field,
            type: 'optionType',
            method: 'options'
        );
    }

    public static function getOptionsConfig(): array
    {
        return array_merge(static::getSelectableValuesConfig(), [
            'optionType' => [],
        ]);
    }

    public function optionFormFields(): Fieldset
    {
        return $this->selectableValuesFormFields(
            type: 'optionType',
            label: 'Options',
            arrayComponent: KeyValue::class
        );
    }
}
