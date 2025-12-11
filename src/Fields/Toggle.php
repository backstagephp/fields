<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Enums\ToggleColor;
use Backstage\Fields\Models\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle as Input;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Facades\FilamentColor;

class Toggle extends Base implements FieldContract
{
    public function getFieldType(): ?string
    {
        return 'toggle';
    }

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            'inline' => false,
            'accepted' => null,
            'declined' => null,
            'onColor' => ToggleColor::SUCCESS->value,
            'offColor' => ToggleColor::DANGER->value,
            'onIcon' => null,
            'offIcon' => null,
        ];
    }

    public static function make(string $name, Field $field): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->inline($field->config['inline'] ?? self::getDefaultConfig()['inline'])
            ->onColor($field->config['onColor'] ?? self::getDefaultConfig()['onColor'])
            ->offColor($field->config['offColor'] ?? self::getDefaultConfig()['offColor']);

        if ($field->config['accepted'] ?? self::getDefaultConfig()['accepted']) {
            $input->accepted($field->config['accepted']);
        }

        if ($field->config['declined'] ?? self::getDefaultConfig()['declined']) {
            $input->declined($field->config['declined']);
        }

        if ($field->config['onIcon'] ?? self::getDefaultConfig()['onIcon']) {
            $input->onIcon($field->config['onIcon']);
        }

        if ($field->config['offIcon'] ?? self::getDefaultConfig()['offIcon']) {
            $input->offIcon($field->config['offIcon']);
        }

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
                            Grid::make(2)->schema([
                                Input::make('config.inline')
                                    ->label(__('Inline'))
                                    ->columnSpanFull(),
                                Input::make('config.accepted')
                                    ->label(__('Accepted'))
                                    ->helperText(__('Requires the checkbox to be checked')),
                                Input::make('config.declined')
                                    ->label(__('Declined'))
                                    ->helperText(__('Requires the checkbox to be unchecked')),
                                Select::make('config.onColor')
                                    ->label(__('On color'))
                                    ->options(
                                        collect(ToggleColor::array())->map(function ($color) {
                                            return match (strtoupper($color)) {
                                                'DANGER' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Danger</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'GRAY' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Gray</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'INFO' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Info</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'PRIMARY' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Primary</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'SUCCESS' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Success</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'WARNING' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Warning</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                default => $color
                                            };
                                        })
                                    )->allowHtml(),
                                Select::make('config.offColor')
                                    ->label(__('Off color'))
                                    ->options(
                                        collect(ToggleColor::array())->map(function ($color) {
                                            return match (strtoupper($color)) {
                                                'DANGER' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Danger</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'GRAY' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Gray</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'INFO' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Info</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'PRIMARY' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Primary</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'SUCCESS' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Success</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                'WARNING' => sprintf(
                                                    '<div class="flex items-center gap-2"><span style="background-color: rgb(%s)" class="rounded-full h-3 w-3 inline-block"></span>Warning</div>',
                                                    FilamentColor::getColors()[strtolower($color)]['500']
                                                ),
                                                default => $color
                                            };
                                        })
                                    )->allowHtml(),
                                TextInput::make('config.onIcon')
                                    ->label(__('On icon')),
                                TextInput::make('config.offIcon')
                                    ->label(__('Off icon')),
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
}
