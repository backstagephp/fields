<?php

namespace Backstage\Fields\Schemas;

use Backstage\Fields\Contracts\SchemaContract;
use Backstage\Fields\Models\Schema;
use Filament\Schemas\Components\Grid;

abstract class Base implements SchemaContract
{
    public function getForm(): array
    {
        return $this->getBaseFormSchema();
    }

    protected function getBaseFormSchema(): array
    {
        $schema = [
            Grid::make(3)
                ->schema([
                    //
                ]),
        ];

        return $this->filterExcludedFields($schema);
    }

    protected function excludeFromBaseSchema(): array
    {
        return [];
    }

    private function filterExcludedFields(array $schema): array
    {
        $excluded = $this->excludeFromBaseSchema();

        if (empty($excluded)) {
            return $schema;
        }

        return array_filter($schema, function ($field) use ($excluded) {
            foreach ($excluded as $excludedField) {
                if ($this->fieldContainsConfigKey($field, $excludedField)) {
                    return false;
                }
            }

            return true;
        });
    }

    private function fieldContainsConfigKey($field, string $configKey): bool
    {
        $reflection = new \ReflectionObject($field);
        $propertiesToCheck = ['name', 'statePath'];

        foreach ($propertiesToCheck as $propertyName) {
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                $value = $property->getValue($field);

                if (str_contains($value, "config.{$configKey}")) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getDefaultConfig(): array
    {
        return [
            //
        ];
    }

    public static function make(string $name, Schema $schema)
    {
        // Base implementation - should be overridden by child classes
        return null;
    }

    protected static function ensureArray($value, string $delimiter = ','): array
    {
        if (is_array($value)) {
            return $value;
        }

        return ! empty($value) ? explode($delimiter, $value) : [];
    }
}
