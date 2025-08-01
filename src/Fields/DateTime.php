<?php

namespace Backstage\Fields\Fields;

use Backstage\Fields\Concerns\HasAffixes;
use Backstage\Fields\Contracts\FieldContract;
use Backstage\Fields\Models\Field;
use Baspa\Timezones\Facades\Timezones;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker as Input;

class DateTime extends Base implements FieldContract
{
    use HasAffixes;

    public static function getDefaultConfig(): array
    {
        return [
            ...parent::getDefaultConfig(),
            ...self::getAffixesConfig(),
            'format' => 'd-m-Y H:i',
            'seconds' => false,
            'timezone' => null,
            'native' => false,
            'displayFormat' => 'd-m-Y H:i',
            'locale' => config('app.locale'),
            'firstDayOfWeek' => 1,
            'hoursStep' => 1,
            'minutesStep' => 15,
            'secondsStep' => 10,
            'closeOnDateSelection' => false,

        ];
    }

    public static function make(string $name, Field $field): Input
    {
        $input = self::applyDefaultSettings(Input::make($name), $field);

        $input = $input->label($field->name ?? self::getDefaultConfig()['label'] ?? null)
            ->format($field->config['format'] ?? self::getDefaultConfig()['format'])
            ->seconds($field->config['seconds'] ?? self::getDefaultConfig()['seconds'])
            ->timezone($field->config['timezone'] ?? self::getDefaultConfig()['timezone'])
            ->native($field->config['native'] ?? self::getDefaultConfig()['native'])
            ->displayFormat($field->config['displayFormat'] ?? self::getDefaultConfig()['displayFormat'])
            ->locale($field->config['locale'] ?? self::getDefaultConfig()['locale'])
            ->firstDayOfWeek($field->config['firstDayOfWeek'] ?? self::getDefaultConfig()['firstDayOfWeek'])
            ->hoursStep($field->config['hoursStep'] ?? self::getDefaultConfig()['hoursStep'])
            ->minutesStep($field->config['minutesStep'] ?? self::getDefaultConfig()['minutesStep'])
            ->secondsStep($field->config['secondsStep'] ?? self::getDefaultConfig()['secondsStep'])
            ->closeOnDateSelection($field->config['closeOnDateSelection'] ?? self::getDefaultConfig()['closeOnDateSelection']);

        $input = self::addAffixesToInput($input, $field);

        return $input;
    }

    public function getForm(): array
    {
        return [
            Forms\Components\Tabs::make()
                ->schema([
                    Forms\Components\Tabs\Tab::make('General')
                        ->label(__('General'))
                        ->schema([
                            ...parent::getForm(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Field specific')
                        ->label(__('Field specific'))
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Toggle::make('config.seconds')
                                    ->label(__('Seconds'))
                                    ->inline(false),
                                Forms\Components\Toggle::make('config.native')
                                    ->label(__('Native'))
                                    ->inline(false),
                                Forms\Components\Toggle::make('config.closeOnDateSelection')
                                    ->label(__('Close on date selection'))
                                    ->inline(false),
                            ]),
                            Forms\Components\Grid::make(2)->schema([

                                Forms\Components\Select::make('config.timezone')
                                    ->label(__('Timezone'))
                                    ->searchable(true)
                                    ->preload(true)
                                    ->allowHtml()
                                    ->options(Timezones::includeGeneral()->toArray(grouped: true)),
                                Forms\Components\TextInput::make('config.format')
                                    ->label(__('Format')),
                                Forms\Components\TextInput::make('config.locale')
                                    ->label(__('Locale')),
                                Forms\Components\Select::make('config.firstDayOfWeek')
                                    ->label(__('First day of week'))
                                    ->options([
                                        7 => __('Sunday'),
                                        1 => __('Monday'),
                                    ]),
                                Forms\Components\Grid::make(3)->schema([

                                    Forms\Components\TextInput::make('config.hoursStep')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(24)
                                        ->label(__('Hours step')),
                                    Forms\Components\TextInput::make('config.minutesStep')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(60)
                                        ->label(__('Minutes step')),
                                    Forms\Components\TextInput::make('config.secondsStep')
                                        ->numeric()
                                        ->minValue(1)
                                        ->maxValue(60)
                                        ->label(__('Seconds step')),
                                ]),
                            ]),
                            self::affixFormFields(),
                        ]),
                    Forms\Components\Tabs\Tab::make('Rules')
                        ->label(__('Rules'))
                        ->schema([
                            ...parent::getRulesForm(),
                        ]),
                ])->columnSpanFull(),
        ];
    }
}
