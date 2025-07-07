<?php

namespace Backstage\Fields\Concerns;

use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Fieldset;

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
            'datalistType' => [],
        ]);
    }

    public function datalistFormFields(): Fieldset
    {
        return $this->selectableValuesFormFields(
            type: 'datalistType',
            label: 'Datalist',
            arrayComponent: TagsInput::class
        );
    }
}
