<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Fields\FormSchemas\BasicSettingsSchema;
use Backstage\Fields\Fields\FormSchemas\ConditionalLogicSchema;
use Backstage\Fields\Fields\FormSchemas\ValidationRulesSchema;
use Backstage\Fields\Fields\Logic\ConditionalLogicApplier;
use Backstage\Fields\Fields\Validation\ValidationRuleApplier;
use Backstage\Fields\Models\Field;
use Filament\Forms;
use Filament\Support\Colors\Color;

abstract class Base implements FieldContract
{
    public function getForm(): array
    {
        return BasicSettingsSchema::make();
    }

    /**
     * Get the Rules form schema for conditional logic
     */
    public function getRulesForm(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    ...ConditionalLogicSchema::make(),
                    ...ValidationRulesSchema::make(),
                ]),
        ];
    }

    public static function getDefaultConfig(): array
    {
        return [
            'required' => false,
            'disabled' => false,
            'hidden' => false,
            'helperText' => null,
            'hint' => null,
            'hintColor' => null,
            'hintIcon' => null,
            'conditionalField' => null,
            'conditionalOperator' => null,
            'conditionalValue' => null,
            'conditionalAction' => null,
            'validationRules' => [],
        ];
    }

    public static function applyDefaultSettings($input, ?Field $field = null)
    {
        $input
            ->required($field->config['required'] ?? self::getDefaultConfig()['required'])
            ->disabled($field->config['disabled'] ?? self::getDefaultConfig()['disabled'])
            ->hidden($field->config['hidden'] ?? self::getDefaultConfig()['hidden'])
            ->helperText($field->config['helperText'] ?? self::getDefaultConfig()['helperText'])
            ->hint($field->config['hint'] ?? self::getDefaultConfig()['hint'])
            ->hintIcon($field->config['hintIcon'] ?? self::getDefaultConfig()['hintIcon'])
            ->live();

        if (isset($field->config['hintColor']) && $field->config['hintColor']) {
            $input->hintColor(Color::hex($field->config['hintColor']));
        }

        $input = ConditionalLogicApplier::applyConditionalLogic($input, $field);
        $input = ConditionalLogicApplier::applyConditionalValidation($input, $field);
        $input = self::applyAdditionalValidation($input, $field);

        return $input;
    }

    protected static function applyAdditionalValidation($input, ?Field $field = null): mixed
    {
        if (! $field || empty($field->config['validationRules'])) {
            return $input;
        }

        $rules = $field->config['validationRules'];

        foreach ($rules as $rule) {
            $input = ValidationRuleApplier::applyValidationRule($input, $rule, $field);
        }

        return $input;
    }
}
