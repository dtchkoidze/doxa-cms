<?php

namespace Doxa\Admin\Http\Controllers;

use App\Models\User;
use Doxa\Core\Libraries\Chlo;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Doxa\Core\Libraries\Logging\Clog;
use Illuminate\Support\Facades\Route;
use Doxa\Core\Libraries\Package\Package;
use Doxa\Core\Modules\Channel\Repositories\Channel;


class AdminController extends Controller
{

    const ADMIN_ROLE_IDENTIFICATOR = 'Administrator';

    private $user;
    /**
     * Create a controller instance.
     *
     * @return void
     */
    public function __construct() {}

    /**
     * Dashboard page.
     *
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function dashboard()
    {

        // dd(session()->all());
        // ----------- datagrid test

        // Chlo::init();

        // $configuration = [
        //     'module' => 'vocabulary',
        //     'package' => new Package('vocabulary', 'index')
        // ];

        // $datagridClass = 'Doxa\\Admin\\Libraries\\DoxaDataGrid';
        // $dataGrid = app($datagridClass);

        // $dataGrid->configure($configuration);

        // $data = $dataGrid->getData();

        // dd($data);
        // ---END----- datagrid test

        //dd(config('doxa.modules'));

        return view('admin::dashboard', ['modules' => config('doxa.modules')]);
    }

    public function noPermissions()
    {
        die('noPermissions');
    }

    public function sidebarMenu()
    {

        if (request()->ajax()) {

            $user = Auth::user();

            $user_role_ids = explode(',', $user->roles);

            $superuser_role = DB::table('roles')->where('permissions', 'all')->first();


            $is_superuser = in_array($superuser_role->id, $user_role_ids);

            if ($is_superuser) {
                $menu = config('admin.menu');
            } else {
                $user_role_id = $user_role_ids[0];
                $user_role = DB::table('roles')->where('id', $user_role_id)->first();
                if(!$user_role){
                    dd('Role '. $user_role_id.' not found');
                } else {
                    $user_permissions = json_decode($user_role->permissions, true);

                    $acl = config('admin.acl');
                    $user_acl = array_filter($acl, fn($item) => in_array($item['key'], $user_permissions));
                    $acl_route_names = array_map(fn($item) => $item['route'], $user_acl);
                    $menu = array_filter(config('admin.menu'), fn($item) => in_array($item['route'], $acl_route_names));
                }
            }

            foreach ($menu as &$item) {
                $item['url'] = route($item['route']);
            }

            return response()->json(['menu' => $menu]);
        }
    }

    public function settingsAcl($id)
    {

        $role = DB::table('roles')
            ->where('id', $id)
            ->first();

        $rolePermissions = $role->permissions ? json_decode($role->permissions, true) : [];

        $menuCollection = collect(config('admin.menu'));

        $aclCollection = collect(config('admin.acl'));

        $mergedAcl = $aclCollection->map(function ($aclItem) use ($menuCollection, $rolePermissions) {

            $menuItem = $menuCollection->firstWhere('key', $aclItem['key']);
            if ($menuItem && isset($menuItem['icon'])) {
                $aclItem['icon'] = $menuItem['icon'];
            }

            $aclItem['checked'] = in_array($aclItem['key'], $rolePermissions);

            return $aclItem;
        });

        // dd($mergedAcl);

        return view('admin::settings.acl.index', ['role' => $role, 'mergedAcl' => $mergedAcl]);
    }


    public function roles()
    {
        return view('admin::settings.roles.index');
    }

    public function updateRolePermissions()
    {
        // info(request()->all());

        $changes = request()->get('changes');
        $role_id = request()->get('role_id');

        if (!is_array($changes) || $role_id === null) {
            return response()->json(['status' => 'error', 'message' => 'Invalid request, no role or changes'], 200);
        }

        $role = DB::table('roles')->where('id', $role_id)->first();

        if (!$role) {
            return response()->json(['status' => 'error', 'message' => 'Role not found'], 200);
        }

        $permissions = json_decode($role->permissions, true) ?? [];

        foreach ($changes as $change) {
            $key = $change['key'] ?? null;
            $checked = filter_var($change['checked'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (!$key) {
                continue;
            }

            if ($checked && !in_array($key, $permissions)) {
                $permissions[] = $key;
            } elseif (!$checked && in_array($key, $permissions)) {
                $permissions = array_filter($permissions, fn($permission) => $permission !== $key);
            }
        }

        DB::table('roles')->where('id', $role_id)->update([
            'permissions' => json_encode(array_values($permissions)),
        ]);

        return response()->json(['status' => 'success', 'permissions' => $permissions, 'message' => 'Permissions saved successfully'], 200);
    }

    public function currentUser()
    {
        $user = Auth::user();

        $user->role = session('user_role');

        if (!$user->admin) {
            $role_id = DB::table('roles')
                ->where('name', $user->role)
                ->value('id');

            $user_all_role_ids = json_decode($user->roles, true);

            $user->other_roles = DB::table('roles')
                ->whereIn('id', $user_all_role_ids)
                ->where('id', '!=', $role_id)
                ->get();
        } else {
            $all_roles = DB::table('roles')->get();
            // $all_roles = [...$all_roles, self::ADMIN_ROLE_IDENTIFICATOR];

            if ($user->role != self::ADMIN_ROLE_IDENTIFICATOR) {
                $administrator = (object) [
                    'name' => self::ADMIN_ROLE_IDENTIFICATOR,
                ];
                $all_roles = [...$all_roles, $administrator];
            }
            $user->other_roles = $all_roles;
        }

        return response()->json(['user' => $user], 200);
    }

    public function switchUserRole()
    {

        $new_role = request()->get('new_role');

        if (!$new_role) {
            return response()->json(['status' => 'error', 'message' => 'Invalid request, no role'], 200);
        }

        $user = Auth::user();

        $user->role = $new_role;

        session(['user_role' => $new_role]);

        return response()->json(['status' => 'success', 'message' => 'Role switched successfully'], 200);
    }
}
