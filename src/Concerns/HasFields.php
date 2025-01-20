<?php

namespace Vormkracht10\Fields\Concerns;

use Vormkracht10\Fields\Models\Field;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasFields
{
    public function fields(): MorphMany
    {
        return $this->morphMany(Field::class, 'slug', 'model_type', 'model_key')
            ->orderBy('position');
    }
}