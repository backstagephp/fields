<?php

namespace Backstage\Fields\Contracts;

use Backstage\Fields\Models\Schema;

interface SchemaContract
{
    public function getForm(): array;

    public static function make(string $name, Schema $schema);

    public static function getDefaultConfig(): array;
}
