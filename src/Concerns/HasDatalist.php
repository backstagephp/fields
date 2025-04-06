<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms\Components\TagsInput;

trait HasDatalist
{
    use HasSelectableValues;

    public static function addDatalistToInput(mixed $input, mixed $field): mixed
    {
        return static::addValuesToInput(
            input: $input,
            field: $field,
            type: 'datalistType',
            method: 'datalist'
        );
    }

    public static function getDatalistConfig(): array
    {
        return array_merge(static::getSelectableValuesConfig(), [
            'datalistType' => null,
        ]);
    }

    public function datalistFormFields(): \Filament\Forms\Components\Fieldset
    {
        return $this->selectableValuesFormFields(
            type: 'datalistType',
            label: 'Datalist',
            arrayComponent: TagsInput::class
        );
    }
}
