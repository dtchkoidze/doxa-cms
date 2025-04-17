<?php

namespace Doxa\Core\Libraries\Package;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

trait Permissions
{

    public function getUserPermissionTypes(): array
    {

        $permissions = [];
        $prefixes = [];

        $acl = config('admin.acl');

        foreach ($acl as $item) {
            $parts = explode('.', $item['key']);
            $prefix = $parts[0];
            if (!in_array($prefix, $prefixes)) {
                $prefixes[] = $prefix;
            }
        }

        $permissions = [
            'index' => false,
            'edit' => false,
            'update' => false,
            'create' => false,
            'store' => false,
            'delete' => false,
            'deleteVariation' => false,
            'docs' => false,
            'rowAction' => false,
            'savePositions' => false,
        ];

        foreach ($prefixes as $prefix) {
            foreach (array_keys($permissions) as $action) {
                if (!$permissions[$action] && $this->can($prefix . ($prefix === $this->module ? '' : '.' . $this->module) . '.' . $action)) {
                    $permissions[$action] = true;
                    if (!in_array(false, $permissions, true)) {
                        break;
                    }
                }
            }
        }

        return $permissions;
    }

    public function canEdit()
    {
        return $this->getUserPermissionTypes()['edit'] && $this->getUserPermissionTypes()['update'];
    }

    public function canDelete()
    {
        return $this->getUserPermissionTypes()['delete'] && $this->getUserPermissionTypes()['deleteVariation'];
    }

    public function canCreate()
    {
        return $this->getUserPermissionTypes()['create'] && $this->getUserPermissionTypes()['store'];
    }

    protected function can($permission):  bool
    {
        $is_admin = Auth::user()->admin;
        
        if ($is_admin) {
            return true;
        }
        
        $role_id = Auth::user()->role_id;

        if (!$role_id || empty($role_id)) {
            return false;
        }
        
        $role_permissions = DB::table('roles')
        ->where('active', 1)
        ->where('id', $role_id)
        ->first()
        ->permissions;

        $permissions = json_decode($role_permissions, true);

        return in_array($permission, $permissions);
    }
}
