<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Fields\FormSchemas\BasicSettingsSchema;
use Backstage\Fields\Fields\FormSchemas\ValidationRulesSchema;
use Backstage\Fields\Fields\FormSchemas\VisibilityRulesSchema;
use Backstage\Fields\Fields\Logic\ConditionalLogicApplier;
use Backstage\Fields\Fields\Logic\VisibilityLogicApplier;
use Backstage\Fields\Fields\Validation\ValidationRuleApplier;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;
use ReflectionObject;

abstract class Base implements FieldContract
{
    public function getForm(): array
    {
        return BasicSettingsSchema::make();
    }

    public function getRulesForm(): array
    {
        return [
            ...ValidationRulesSchema::make($this->getFieldType()),
            ...VisibilityRulesSchema::make(),
        ];
    }

    protected function getBaseFormSchema(): array
    {
        $schema = [
            Grid::make(3)
                ->schema([
                    Toggle::make('config.required')
                        ->label(__('Required')),
                    Toggle::make('config.disabled')
                        ->label(__('Disabled')),
                    Toggle::make('config.hidden')
                        ->label(__('Hidden')),
                ]),
            Grid::make(2)
                ->schema([
                    TextInput::make('config.helperText')
                        ->live(onBlur: true)
                        ->label(__('Helper text')),
                    TextInput::make('config.hint')
                        ->live(onBlur: true)
                        ->label(__('Hint')),
                    ColorPicker::make('config.hintColor')
                        ->label(__('Hint color'))
                        ->visible(function (Get $get): bool {
                            $hint = $get('config.hint');

                            return ! empty(trim($hint));
                        }),
                    TextInput::make('config.hintIcon')
                        ->label(__('Hint icon'))
                        ->placeholder('heroicon-m-')
                        ->visible(function (Get $get): bool {
                            $hint = $get('config.hint');

                            return ! empty(trim($hint));
                        }),
                ]),
            TextInput::make('config.defaultValue')
                ->label(__('Default value'))
                ->helperText(__('This value will be used when creating new records.')),
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
        $reflection = new ReflectionObject($field);
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

    public function getFieldType(): ?string
    {
        // This method should be overridden by specific field classes
        // to return their field type
        return null;
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
            'visibilityRules' => [],
            'defaultValue' => null,
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
            $input->hintColor(Color::generateV3Palette($field->config['hintColor']));
        }

        $input = ConditionalLogicApplier::applyConditionalLogic($input, $field);
        $input = ConditionalLogicApplier::applyConditionalValidation($input, $field);
        $input = VisibilityLogicApplier::applyVisibilityLogic($input, $field);

        $input = self::applyAdditionalValidation($input, $field);

        if (isset($field->config['defaultValue'])) {
            $input->default($field->config['defaultValue']);
        }

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
