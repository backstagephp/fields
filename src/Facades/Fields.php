<?php

namespace Backstage\Fields\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Backstage\Fields\Fields
 */
class Fields extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Backstage\Fields\Fields::class;
    }
}
