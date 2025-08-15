<?php

namespace Backstage\Fields\Contracts;

use Backstage\Fields\Models\Field;

interface FieldContract
{
    public function getForm(): array;

    public static function make(string $name, Field $field);

    public static function getDefaultConfig(): array;

    public function getFieldType(): ?string;
}
