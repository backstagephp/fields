<?php

namespace Backstage\Fields\Fields\Logic;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Backstage\Fields\Models\Field;
use Filament\Schemas\Components\Utilities\Get;

class ConditionalLogicApplier
{
    public static function applyConditionalLogic($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['conditionalField']) || empty($field->config['conditionalAction'])) {
            return $input;
        }

        $conditionalField = $field->config['conditionalField'];
        $operator = $field->config['conditionalOperator'] ?? 'equals';
        $value = $field->config['conditionalValue'] ?? null;
        $action = $field->config['conditionalAction'];

        // Get the field name for the conditional field
        $conditionalFieldName = FieldOptionsHelper::getFieldNameFromUlid($conditionalField, $field);

        if (! $conditionalFieldName) {
            return $input;
        }

        switch ($action) {
            case 'show':
                $input->visible(
                    fn (Get $get): bool => self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;

            case 'hide':
                $input->visible(
                    fn (Get $get): bool => ! self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;

            case 'required':
                $input->required(
                    fn (Get $get): bool => self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;

            case 'not_required':
                $input->required(
                    fn (Get $get): bool => ! self::evaluateCondition($get($conditionalFieldName), $operator, $value)
                );

                break;
        }

        return $input;
    }

    public static function applyConditionalValidation($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['conditionalField']) || empty($field->config['conditionalAction'])) {
            return $input;
        }

        $conditionalField = $field->config['conditionalField'];
        $operator = $field->config['conditionalOperator'] ?? 'equals';
        $value = $field->config['conditionalValue'] ?? null;
        $action = $field->config['conditionalAction'];

        $conditionalFieldName = FieldOptionsHelper::getFieldNameFromUlid($conditionalField, $field);

        if (! $conditionalFieldName) {
            return $input;
        }

        switch ($action) {
            case 'required':
                if ($operator === 'equals' && $value !== null) {
                    $input->requiredIf($conditionalFieldName, $value);
                } elseif ($operator === 'not_equals' && $value !== null) {
                    $input->requiredUnless($conditionalFieldName, $value);
                } elseif ($operator === 'is_empty') {
                    $input->requiredUnless($conditionalFieldName, '');
                } elseif ($operator === 'is_not_empty') {
                    $input->requiredIf($conditionalFieldName, '');
                }

                break;

            case 'not_required':
                break;
        }

        return $input;
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

            default:
                return false;
        }
    }
}
