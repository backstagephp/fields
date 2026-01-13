<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasAffixes;
use Backstage\Fields\Concerns\HasDatalist;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput as Input;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;

class Text extends Base implements FieldContract
{
    use HasAffixes;
    use HasDatalist;

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            ...self::getAffixesConfig(),
            ...self::getDatalistConfig(),
            'readOnly' => false,
            'autocapitalize' => 'none',
            'autocomplete' => null,
            'placeholder' => null,
            'mask' => null,
            'minLength' => null,
            'maxLength' => null,
            'type' => 'text',
            'step' => null,
            'inputMode' => null,
            'telRegex' => null,
            'revealable' => false,
        ];
    }

    public static function make(string $name, ?Field $field = null): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->readOnly($field->config['readOnly'] ?? self::getDefaultConfig()['readOnly'])
            ->placeholder($field->config['placeholder'] ?? self::getDefaultConfig()['placeholder'])
            ->mask($field->config['mask'] ?? self::getDefaultConfig()['mask'])
            ->minLength($field->config['minLength'] ?? self::getDefaultConfig()['minLength'])
            ->maxLength($field->config['maxLength'] ?? self::getDefaultConfig()['maxLength'])
            ->type($field->config['type'] ?? self::getDefaultConfig()['type'])
            ->step($field->config['step'] ?? self::getDefaultConfig()['step'])
            ->inputMode($field->config['inputMode'] ?? self::getDefaultConfig()['inputMode'])
            ->telRegex($field->config['telRegex'] ?? self::getDefaultConfig()['telRegex'])
            ->revealable($field->config['revealable'] ?? self::getDefaultConfig()['revealable']);

        if ($field->config && $field->config['type'] === 'email') {
            $input->email();
        }

        if ($field->config && $field->config['type'] === 'tel') {
            $input->tel();
        }

        if ($field->config && $field->config['type'] === 'url') {
            $input->url();
        }

        if ($field->config && $field->config['type'] === 'password') {
            $input->password();
        }

        if ($field->config && $field->config['type'] === 'numeric') {
            $input->numeric();
        }

        if ($field->config && $field->config['type'] === 'integer') {
            $input->integer();
        }

        $input = self::addAffixesToInput($input, $field);
        $input = self::addDatalistToInput($input, $field);

        return $input;
    }

    public function getForm(): array
    {
        return [
            Tabs::make()
                ->schema([
                    Tab::make('General')
                        ->label(__('General'))
                        ->schema([
                            ...parent::getForm(),
                        ]),
                    Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Toggle::make('config.readOnly')
                                ->label(__('Read only')),
                            Grid::make(2)
                                ->schema([
                                    Select::make('config.autocapitalize')
                                        ->label(__('Autocapitalize'))
                                        ->options([
                                            'none' => __('None (off)'),
                                            'sentences' => __('Sentences'),
                                            'words' => __('Words'),
                                            'characters' => __('Characters'),
                                        ]),
                                    Input::make('config.autocomplete')
                                        ->default(false)
                                        ->label(__('Autocomplete')),
                                    self::affixFormFields(),
                                    self::datalistFormFields(),
                                    Input::make('config.placeholder')
                                        ->label(__('Placeholder')),
                                    Input::make('config.mask')
                                        ->label(__('Mask')),
                                    Input::make('config.minLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Minimum length')),
                                    Input::make('config.maxLength')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Maximum length')),
                                    Select::make('config.type')
                                        ->columnSpanFull()
                                        ->label(__('Type'))
                                        ->live(debounce: 250)
                                        ->options([
                                            'text' => __('Text'),
                                            'password' => __('Password'),
                                            'tel' => __('Telephone'),
                                            'url' => __('URL'),
                                            'email' => __('Email'),
                                            'numeric' => __('Numeric'),
                                            'integer' => __('Integer'),
                                        ]),
                                    Input::make('config.step')
                                        ->numeric()
                                        ->minValue(0)
                                        ->label(__('Step'))
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'numeric'),
                                    Select::make('config.inputMode')
                                        ->label(__('Input mode'))
                                        ->options([
                                            'none' => __('None'),
                                            'text' => __('Text'),
                                            'decimal' => __('Decimal'),
                                            'numeric' => __('Numeric'),
                                            'tel' => __('Telephone'),
                                            'search' => __('Search'),
                                            'email' => __('Email'),
                                            'url' => __('URL'),
                                        ])
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'numeric'),
                                    Toggle::make('config.revealable')
                                        ->label(__('Revealable'))
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'password'),
                                    Input::make('config.telRegex')
                                        ->label(__('Telephone regex'))
                                        ->visible(fn (Get $get): bool => $get('config.type') === 'tel'),
                                ]),
                        ]),
                    Tab::make('Rules')
                        ->label(__('Rules'))
                        ->schema([
                            ...parent::getRulesForm(),
                        ]),
                ])->columnSpanFull(),
        ];
    }

    public function getFieldType(): ?string
    {
        return 'text';
    }
}
