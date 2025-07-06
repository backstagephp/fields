<?php

namespace Backstage\Fields\Services;

use Backstage\Fields\Contracts\FieldInspector;
use Backstage\Fields\Facades\Fields;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;

class FieldInspectionService implements FieldInspector
{
    public function initializeDefaultField(string $fieldType): array
    {
        $className = 'Backstage\\Fields\\Fields\\' . Str::studly($fieldType);

        return $this->getClassDetails($className);
    }

    public function initializeCustomField(string $fieldType): array
    {
        $className = Fields::getFields()[$fieldType] ?? null;

        return $this->getClassDetails($className);
    }

    private function getClassDetails(?string $className): array
    {
        if (! $className || ! class_exists($className)) {
            return [
                'exists' => false,
                'class' => $className,
                'methods' => [],
                'properties' => [],
                'constants' => [],
                'interfaces' => [],
                'instance' => null,
            ];
        }

        $reflection = new ReflectionClass($className);
        $instance = app($className);

        return [
            'exists' => true,
            'class' => $className,
            'methods' => $this->getMethodsDetails($reflection),
            'properties' => $this->getPropertiesDetails($reflection),
            'constants' => $reflection->getConstants(),
            'interfaces' => $reflection->getInterfaceNames(),
            'instance' => $instance,
            'parentClass' => $reflection->getParentClass() ? $reflection->getParentClass()->getName() : null,
            'traits' => $reflection->getTraitNames(),
        ];
    }

    private function getMethodsDetails(ReflectionClass $reflection): array
    {
        $methods = [];
        foreach ($reflection->getMethods() as $method) {
            $methods[$method->getName()] = [
                'visibility' => $this->getVisibility($method),
                'static' => $method->isStatic(),
                'parameters' => $this->getParametersDetails($method),
                'returnType' => $this->getTypeName($method->getReturnType()),
                'docComment' => $method->getDocComment() ?: null,
            ];
        }

        return $methods;
    }

    private function getPropertiesDetails(ReflectionClass $reflection): array
    {
        $properties = [];
        foreach ($reflection->getProperties() as $property) {
            $properties[$property->getName()] = [
                'visibility' => $this->getVisibility($property),
                'static' => $property->isStatic(),
                'type' => $this->getTypeName($property->getType()),
                'docComment' => $property->getDocComment() ?: null,
                'defaultValue' => $this->getPropertyDefaultValue($property),
            ];
        }

        return $properties;
    }

    private function getParametersDetails(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $param) {
            $parameters[$param->getName()] = [
                'type' => $this->getTypeName($param->getType()),
                'hasDefaultValue' => $param->isDefaultValueAvailable(),
                'defaultValue' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                'isVariadic' => $param->isVariadic(),
                'isPassedByReference' => $param->isPassedByReference(),
            ];
        }

        return $parameters;
    }

    private function getTypeName(?ReflectionType $type): ?string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }

        return null;
    }

    private function getVisibility($reflection): string
    {
        if ($reflection->isPrivate()) {
            return 'private';
        }
        if ($reflection->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    private function getPropertyDefaultValue(ReflectionProperty $property): mixed
    {
        try {
            return $property->getDefaultValue();
        } catch (ReflectionException $e) {
            return null;
        }
    }
}
