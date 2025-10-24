<?php

namespace Backstage\Fields\Fields\Logic;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Backstage\Fields\Models\Field;
use Filament\Schemas\Components\Utilities\Get;

class VisibilityLogicApplier
{
    public static function applyVisibilityLogic($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['visibilityRules'])) {
            return $input;
        }

        $visibilityRules = $field->config['visibilityRules'];

        $input->visible(function (Get $get) use ($visibilityRules, $field): bool {
            return self::evaluateVisibilityRules($get, $visibilityRules, $field);
        });

        return $input;
    }

    protected static function evaluateVisibilityRules(Get $get, array $visibilityRules, Field $field): bool
    {
        foreach ($visibilityRules as $rule) {
            $logic = $rule['logic'] ?? 'AND';
            $conditions = $rule['conditions'] ?? [];

            if (empty($conditions)) {
                continue;
            }

            $ruleResult = self::evaluateRuleConditions($get, $conditions, $logic, $field);

            // If any rule evaluates to false, the field should be hidden
            if (! $ruleResult) {
                return false;
            }
        }

        // If all rules evaluate to true, the field should be visible
        return true;
    }

    protected static function evaluateRuleConditions(Get $get, array $conditions, string $logic, Field $field): bool
    {
        $results = [];

        foreach ($conditions as $condition) {
            $source = $condition['source'] ?? 'field';
            $operator = $condition['operator'] ?? 'equals';
            $value = $condition['value'] ?? null;

            if ($source === 'model_attribute') {
                $modelClass = $condition['model'] ?? '';
                $attributeName = $condition['property'] ?? '';

                if (! $modelClass || ! $attributeName) {
                    continue;
                }

                // Get model instance from Get closure context
                // The model attribute should be accessible directly from the form data
                $modelValue = $get($attributeName);
                $results[] = self::evaluateCondition($modelValue, $operator, $value);
            } else {
                // Field-based logic - use property field for both field and model_attribute sources
                $fieldUlid = $condition['property'] ?? $condition['field'] ?? '';
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($fieldUlid, $field);

                if (! $fieldName) {
                    continue;
                }

                $fieldValue = $get($fieldName);
                $results[] = self::evaluateCondition($fieldValue, $operator, $value);
            }
        }

        if (empty($results)) {
            return true;
        }

        return $logic === 'AND'
            ? ! in_array(false, $results, true)  // All must be true
            : in_array(true, $results, true);   // At least one must be true
    }

    protected static function evaluateCondition($fieldValue, string $operator, $expectedValue): bool
    {
        switch ($operator) {
            case 'equals':
                return $fieldValue == $expectedValue;

            case 'not_equals':
                return $fieldValue != $expectedValue;

            case 'contains':
                return is_string($fieldValue) && str_contains($fieldValue, $expectedValue);

            case 'not_contains':
                return is_string($fieldValue) && ! str_contains($fieldValue, $expectedValue);

            case 'starts_with':
                return is_string($fieldValue) && str_starts_with($fieldValue, $expectedValue);

            case 'ends_with':
                return is_string($fieldValue) && str_ends_with($fieldValue, $expectedValue);

            case 'is_empty':
                return empty($fieldValue);

            case 'is_not_empty':
                return ! empty($fieldValue);

            case 'greater_than':
                return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue > $expectedValue;

            case 'less_than':
                return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue < $expectedValue;

            case 'greater_than_or_equal':
                return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue >= $expectedValue;

            case 'less_than_or_equal':
                return is_numeric($fieldValue) && is_numeric($expectedValue) && $fieldValue <= $expectedValue;

            case 'in':
                if (! is_string($expectedValue)) {
                    return false;
                }
                $allowedValues = array_map('trim', explode(',', $expectedValue));

                return in_array($fieldValue, $allowedValues);

            case 'not_in':
                if (! is_string($expectedValue)) {
                    return true;
                }
                $excludedValues = array_map('trim', explode(',', $expectedValue));

                return ! in_array($fieldValue, $excludedValues);

            default:
                return false;
        }
    }
}
