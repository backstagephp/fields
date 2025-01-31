<?php

namespace Vormkracht10\Fields\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Vormkracht10\Fields\Models\Field;

trait HasFields
{
    public string $valueColumn = 'values';

    protected function casts(): array
    {
        return [
            $this->valueColumn => 'array',
        ];
    }

    public function fields(): MorphMany
    {
        return $this->morphMany(Field::class, 'ulid', 'model_type', 'model_key')
            ->orderBy('position');
    }
}
