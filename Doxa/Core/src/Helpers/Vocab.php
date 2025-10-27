<?php

namespace Doxa\Core\Helpers;

use Doxa\Core\Libraries\Logging\Clog;
use Doxa\Core\Libraries\ModuleManager\Manager as ModuleManager;

class Vocab extends TextTools
{
    protected string $prefix = 'vocabulary';

    protected function findInDB()
    {
        $record = ModuleManager::pm('vocabulary')
            ->channel(1)
            ->locale('current')
            ->where('key', $this->key)
            ->first();

        return $record ? $record->text : 'vocabulary:' . $this->key;
    }
}


