<?php

namespace Vormkracht10\Fields\Services;

use ReflectionClass;
use Vormkracht10\Fields\Contracts\FieldInspector;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionProperty;
use Vormkracht10\Fields\Facades\Fields;

class FieldInspectionService implements FieldInspector
{
    public function initializeDefaultField(string $fieldType): array
    {
        $className = 'Vormkracht10\\Fields\\Fields\\' . Str::studly($fieldType);

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
                'returnType' => $method->getReturnType() ? $method->getReturnType()->getName() : null,
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
                'type' => $property->getType() ? $property->getType()->getName() : null,
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
                'type' => $param->getType() ? $param->getType()->getName() : null,
                'hasDefaultValue' => $param->isDefaultValueAvailable(),
                'defaultValue' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                'isVariadic' => $param->isVariadic(),
                'isPassedByReference' => $param->isPassedByReference(),
            ];
        }

        return $parameters;
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
        } catch (\ReflectionException $e) {
            return null;
        }
    }
}