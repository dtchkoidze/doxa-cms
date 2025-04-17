<?php

namespace Doxa\Admin\Repositories;

use ReflectionClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Doxa\Core\Repositories\Repository;
use Doxa\Core\Libraries\Package\Package;
use Doxa\Core\Libraries\ModuleManager\Manager as ModuleManager;

trait OmniRepositoryTrait
{
    public function toggleCheckbox($data)
    {
        $a = explode('.', $data['index']);
        $field_name = sizeof($a) > 1 ? $a[1] : $a[0];

        $tb = (strpos($data['index'], '_variations') !== false) ? $this->package->scheme->getVariationsTable() : $this->package->scheme->getTable();
        $new_value = DB::table($tb)->where('id', $data['id'])->value($field_name) == 1 ? 0 : 1;

        DB::table($tb)->where('id', $data['id'])->update([$field_name => $new_value]);

        return [
            'status' => $new_value,
            'message' => omniModuleTrans($this->module, 'data-updated')
        ];
    }


}
