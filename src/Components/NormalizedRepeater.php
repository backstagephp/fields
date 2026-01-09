<?php

namespace Backstage\Fields\Components;

use Filament\Forms\Components\Repeater;

class NormalizedRepeater extends Repeater
{
    public function getRawState(): mixed
    {
        $state = parent::getRawState();

        if (is_string($state)) {
            $state = json_decode($state, true);
        }

        if (is_array($state)) {
            // Check if wrapping is needed
            $hasNonArrayItems = false;
            foreach ($state as $item) {
                if (! is_array($item)) {
                    $hasNonArrayItems = true;

                    break;
                }
            }

            if ($hasNonArrayItems) {
                $state = [$state];
            }

            // If it's a numeric list (0, 1, 2...), generate UUID keys
            // Filament requires UUID keys for Repeater items to bind correctly
            if (array_is_list($state)) {
                $keyedState = [];
                foreach ($state as $item) {
                    $keyedState[\Illuminate\Support\Str::uuid()->toString()] = $item;
                }
                $state = $keyedState;

                // Persist the normalized state so keys don't rotate on every call
                $this->rawState($state);
            }

            return $state;
        }

        return [];
    }
}
