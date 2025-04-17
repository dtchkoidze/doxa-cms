<?php

namespace Doxa\Core\Helpers;

use Doxa\Core\Libraries\ModuleManager\Manager;

class TextBlock extends TextTools
{
    protected string $prefix = 'text_blocks';

    protected function findInDB()
    {
        $record = Manager::pm('text_block')
            ->channel('current')
            ->locale('current')
            ->where('key', $this->key)
            ->first(); 
        
        return $record ? $record->text : 'text_block:'.$this->key;    
    }
}


