<?php

namespace Doxa\Core\Services;

use Doxa\Core\Libraries\Package\Permissions;

class PermissionChecker
{
    use Permissions;

    public string $module;

    public function __construct()
    {
        $this->module = request()->segments()[1];
    }
}
