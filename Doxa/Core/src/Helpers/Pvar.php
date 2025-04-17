<?php

namespace Doxa\Core\Helpers;

use Illuminate\Support\Arr;


class Pvar extends TextTools
{
    protected string $prefix = 'project_variables';

    protected function findInConfig()
    {
        // omitted $this->locale from config construction, since I don't use locales yet
        $config = config($this->prefix);

        // Use Arr::get to handle nested keys using dot notation
        $value = Arr::get($config, $this->key, false);

        return !empty($value) ? $value : false;
    }

    protected function findInDB()
    {

        $record = mr('project_variable')->mm->where('key', $this->key)->channel('current')->locale('current')->first();


        return $record ? $record->value : 'pvar:' . $this->key;
    }
}
