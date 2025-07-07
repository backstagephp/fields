<?php

namespace Backstage\Fields\Database\Factories;

use Backstage\Fields\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

class FieldFactory extends Factory
{
    protected $model = Field::class;

    public function definition(): array
    {
        return [
            'field_type' => 'text',
        ];
    }
}
