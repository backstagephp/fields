<?php

namespace Backstage\Fields\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Backstage\Fields\Models\Field;

class FieldFactory extends Factory
{
    protected $model = Field::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'field_type' => 'text',
        ];
    }
}
