<?php

namespace Backstage\Fields\Fields\Validation;

use Backstage\Fields\Fields\Helpers\FieldOptionsHelper;
use Backstage\Fields\Models\Field;

class ValidationRuleApplier
{
    public static function applyValidationRule($input, array $rule, ?Field $field = null): mixed
    {
        $ruleType = $rule['type'] ?? '';
        $parameters = $rule['parameters'] ?? [];

        switch ($ruleType) {
            case 'min':
                $input->min($parameters['value'] ?? 0);

                break;

            case 'max':
                $input->max($parameters['value'] ?? 999999);

                break;

            case 'min_length':
                $input->minLength($parameters['value'] ?? 0);

                break;

            case 'max_length':
                $input->maxLength($parameters['value'] ?? 255);

                break;

            case 'numeric':
                $input->numeric();

                break;

            case 'integer':
                $input->integer();

                break;

            case 'email':
                $input->email();

                break;

            case 'url':
                $input->url();

                break;

            case 'active_url':
                $input->rules(['active_url']);

                break;

            case 'ip':
                $input->rules(['ip']);

                break;

            case 'ipv4':
                $input->rules(['ipv4']);

                break;

            case 'ipv6':
                $input->rules(['ipv6']);

                break;

            case 'mac_address':
                $input->rules(['mac_address']);

                break;

            case 'uuid':
                $input->uuid();

                break;

            case 'ulid':
                $input->ulid();

                break;

            case 'alpha':
                $input->rules(['alpha']);

                break;

            case 'alpha_dash':
                $input->rules(['alpha_dash']);

                break;

            case 'alpha_num':
                $input->rules(['alpha_num']);

                break;

            case 'ascii':
                $input->rules(['ascii']);

                break;

            case 'json':
                $input->rules(['json']);

                break;

            case 'regex':
                $input->regex($parameters['pattern'] ?? '/.*/');

                break;

            case 'not_regex':
                $input->rules(['not_regex:' . ($parameters['pattern'] ?? '/.*/')]);

                break;

            case 'starts_with':
                $input->startsWith(self::parseValidationValues($parameters['values'] ?? ''));

                break;

            case 'ends_with':
                $input->endsWith(self::parseValidationValues($parameters['values'] ?? ''));

                break;

            case 'in':
                $input->in(self::parseValidationValues($parameters['values'] ?? ''));

                break;

            case 'not_in':
                $input->notIn(self::parseValidationValues($parameters['values'] ?? ''));

                break;

            case 'exists':
                $input->exists($parameters['table'] ?? '', $parameters['column'] ?? 'id');

                break;

            case 'unique':
                $input->unique(
                    table: $parameters['table'] ?? null,
                    column: $parameters['column'] ?? null,
                    ignorable: $parameters['ignorable'] ?? null,
                    ignoreRecord: $parameters['ignoreRecord'] ?? false
                );

                break;

            case 'different':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->different($fieldName);
                }

                break;

            case 'same':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->same($fieldName);
                }

                break;

            case 'confirmed':
                $input->confirmed();

                break;

            case 'date':
                $input->date();

                break;

            case 'date_equals':
                $input->rules(['date_equals:' . ($parameters['date'] ?? 'today')]);

                break;

            case 'date_format':
                $input->rules(['date_format:' . ($parameters['format'] ?? 'Y-m-d')]);

                break;

            case 'after':
                $input->after($parameters['date'] ?? 'today');

                break;

            case 'after_or_equal':
                $input->afterOrEqual($parameters['date'] ?? 'today');

                break;

            case 'before':
                $input->before($parameters['date'] ?? 'today');

                break;

            case 'before_or_equal':
                $input->beforeOrEqual($parameters['date'] ?? 'today');

                break;

            case 'multiple_of':
                $input->rules(['multiple_of:' . ($parameters['value'] ?? 1)]);

                break;

            case 'hex_color':
                $input->rules(['hex_color']);

                break;

            case 'filled':
                $input->rules(['filled']);

                break;

            case 'nullable':
                $input->nullable();

                break;

            case 'prohibited':
                $input->rules(['prohibited']);

                break;

            case 'prohibited_if':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->prohibitedIf($fieldName, $value);
                }

                break;

            case 'prohibited_unless':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->prohibitedUnless($fieldName, $value);
                }

                break;

            case 'prohibits':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->prohibits($fieldName);
                }

                break;

            case 'required_with':
                $fieldNames = FieldOptionsHelper::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->requiredWith($fieldNames);

                break;

            case 'required_with_all':
                $fieldNames = FieldOptionsHelper::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->requiredWithAll($fieldNames);

                break;

            case 'required_without':
                $fieldNames = FieldOptionsHelper::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->requiredWithout($fieldNames);

                break;

            case 'required_without_all':
                $fieldNames = FieldOptionsHelper::getFieldNamesFromUlids($parameters['fields'] ?? [], $field);
                $input->requiredWithoutAll($fieldNames);

                break;

            case 'required_if':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->requiredIf($fieldName, $value);
                }

                break;

            case 'required_unless':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                $value = $parameters['value'] ?? '';
                if ($fieldName && $value !== '') {
                    $input->requiredUnless($fieldName, $value);
                }

                break;

            case 'required_if_accepted':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->requiredIfAccepted($fieldName);
                }

                break;

            case 'greater_than':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->gt($fieldName);
                }

                break;

            case 'greater_than_or_equal':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->gte($fieldName);
                }

                break;

            case 'less_than':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->lt($fieldName);
                }

                break;

            case 'less_than_or_equal':
                $fieldName = FieldOptionsHelper::getFieldNameFromUlid($parameters['field'] ?? '', $field);
                if ($fieldName) {
                    $input->lte($fieldName);
                }

                break;

            case 'enum':
                $input->enum($parameters['enum'] ?? '');

                break;

            case 'string':
                $input->string();

                break;

            case 'doesnt_start_with':
                $values = self::parseValidationValues($parameters['values'] ?? '');
                $input->doesntStartWith($values);

                break;

            case 'doesnt_end_with':
                $values = self::parseValidationValues($parameters['values'] ?? '');
                $input->doesntEndWith($values);

                break;

            case 'decimal':
                $places = $parameters['places'] ?? 2;
                $input->decimal($places);

                break;

            case 'required':
                $input->required();

                break;
        }

        return $input;
    }

    protected static function parseValidationValues(string $values): array
    {
        return array_map('trim', explode(',', $values));
    }
}
