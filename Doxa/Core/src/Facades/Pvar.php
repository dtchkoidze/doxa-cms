<?php

namespace Doxa\Core\Facades;

use Illuminate\Support\Facades\Facade;


class Pvar extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'pvar';
    }

}
