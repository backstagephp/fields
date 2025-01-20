<?php

namespace Vormkracht10\Fields\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Vormkracht10\Fields\Models\Field;

trait HasFields
{
    public function fields(): MorphMany
    {
        return $this->morphMany(Field::class, 'slug', 'model_type', 'model_key')
            ->orderBy('position');
    }
}
