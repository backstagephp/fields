<?php

namespace Backstage\Fields\Contracts;

interface HydratesValues
{
    /**
     * Hydrate the raw field value into its runtime representation.
     */
    public function hydrate(mixed $value, ?\Illuminate\Database\Eloquent\Model $model = null): mixed;
}
