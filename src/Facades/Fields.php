<?php

namespace Vormkracht10\Fields\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Vormkracht10\Fields\Fields
 */
class Fields extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Vormkracht10\Fields\Fields::class;
    }
}
