<?php

namespace Doxa\Core\Facades;

use Illuminate\Support\Facades\Facade;


class Vcb extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'vcb';
    }


}
