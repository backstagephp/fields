<?php

namespace Backstage\Fields\Concerns;

use Backstage\Fields\Models\Field;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @phpstan-ignore-next-line */
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
