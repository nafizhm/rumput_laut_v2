<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'guard_name', 'label' => 'Guard Name'],
            ['key' => 'permission', 'label' => 'Permissions', 'render' => 'permission'],
            ['key' => 'action', 'label' => 'Action', 'width' => '170px', 'render' => 'action'],
        ];

        $data = Role::with('permissions')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($role) {
                $grouped = [];

                foreach ($role->permissions as $permission) {
                    [$menu, $action] = explode('.', $permission->name);

                    $grouped[$menu][] = $action;
                }

                $role->permission_grouped = $grouped;

                return $role;
            });

        $permission = Permission::all()->map(fn($menu) => [
            'label' => $menu->name,
            'value' => $menu->id,
        ]);

        return view('pages.settings.role.index', compact('data', 'columns', 'permission'));
    }

    public function data()
    {
        $data = Role::with('permissions')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($role) {
                $grouped = [];

                foreach ($role->permissions as $permission) {
                    [$menu, $action] = explode('.', $permission->name);

                    $grouped[$menu][] = $action;
                }

                $role->permission_grouped = $grouped;

                return $role;
            });

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil',
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request) {
            $db = [
                'name' => $request->name,
                'guard_name' => 'web',
            ];

            $role = Role::create($db);

            if ($request->has('permissions') && !empty($request->permissions)) {
                $role->givePermissionTo($request->permissions);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil ditambahkan'
        ]);
    }

    public function update(Request $request, Role $manajemen_role)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $manajemen_role->id,
            'permissions' => 'nullable|array',
        ]);

        DB::transaction(function () use ($request, $manajemen_role) {
            $manajemen_role->update([
                'name' => $request->name,
            ]);

            if ($request->filled('permissions')) {
                $manajemen_role->syncPermissions($request->permissions);
            } else {
                $manajemen_role->syncPermissions([]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diupdate'
        ]);
    }

    public function destroy(Role $manajemen_role)
    {
        $manajemen_role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil dihapus'
        ]);
    }
}
