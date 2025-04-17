<?php

namespace Doxa\Admin\Facades;

use Illuminate\Support\Facades\Facade;


class OmniTranslator extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'omnitranslator';
    }
}
