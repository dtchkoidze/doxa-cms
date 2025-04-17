<?php

namespace Doxa\Core\Facades;

use Illuminate\Support\Facades\Facade;


class Vocab extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'vocab';
    }


}
