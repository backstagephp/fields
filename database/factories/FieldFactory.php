<?php

namespace Vormkracht10\Fields\Database\Factories;

use Vormkracht10\Fields\Models\Field;
use Illuminate\Database\Eloquent\Factories\Factory;

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