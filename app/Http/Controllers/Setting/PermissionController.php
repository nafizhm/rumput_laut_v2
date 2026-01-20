<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    public function index()
    {
        $columns = [
            ['key' => 'module_name', 'label' => 'Module Name'],
            ['key' => 'guard_name', 'label' => 'Guard Name'],
            ['key' => 'permission', 'label' => 'Permissions', 'render' => 'permission'],
            ['key' => 'action', 'label' => 'Action', 'width' => '170px', 'render' => 'action'],
        ];

        $data = Permission::all()->groupBy(function ($permission) {
            return Str::contains($permission->name, '.')
                ? Str::before($permission->name, '.')
                : $permission->name;
        })->map(function ($group, $baseName) {
            $menu = Menu::where('permission', $baseName . '.read')->first();

            return [
                'id' => $group->first()->id,
                'name' => $baseName,
                'module_name' => Str::title(str_replace('-', ' ', $baseName)),
                'access' => $group->map(fn($p) => Str::after($p->name, $baseName . '.'))->toArray(),
                'menu_id' => $menu ? $menu->id : null,
                'guard_name' => $group->first()->guard_name,
            ];
        })->values();

        $menus = Menu::whereNotNull('route')
            ->orWhereNotNull('url')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($menu) => [
                'label' => ($menu->parent ? $menu->parent->name . ' > ' : '') . $menu->name,
                'value' => $menu->id,
            ]);

        return view('pages.settings.permission.index', compact('data', 'columns', 'menus'));
    }

    public function data()
    {
        try {
            $data = Permission::all()->groupBy(function ($permission) {
                return Str::contains($permission->name, '.')
                    ? Str::before($permission->name, '.')
                    : $permission->name;
            })->map(function ($group, $baseName) {
                $menu = Menu::where('permission', $baseName . '.read')->first();

                return [
                    'id' => $group->first()->id,
                    'name' => $baseName,
                    'module_name' => Str::title(str_replace('-', ' ', $baseName)),
                    'access' => $group->map(fn($p) => Str::after($p->name, $baseName . '.'))->toArray(),
                    'menu_id' => $menu ? $menu->id : null,
                    'guard_name' => $group->first()->guard_name,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diambil',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'module_name' => 'required|string|max:255',
            'access' => 'required|array|min:1',
            'menu_id' => 'nullable|exists:menus,id'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $moduleSlug = Str::slug($request->module_name);

                foreach ($request->access as $access) {
                    $permissionName = $moduleSlug . '.' . $access;

                    if (!Permission::where('name', $permissionName)->exists()) {
                        Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                    }
                }

                if ($request->menu_id && in_array('read', $request->access)) {
                    $menu = Menu::find($request->menu_id);
                    if ($menu) {
                        $menu->update(['permission' => $moduleSlug . '.read']);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Permission $manajemen_permission)
    {
        $request->validate([
            'module_name' => 'required|string|max:255',
            'access' => 'required|array|min:1',
            'menu_id' => 'nullable|exists:menus,id'
        ]);

        try {
            DB::transaction(function () use ($request, $manajemen_permission) {
                $oldBaseName = Str::contains($manajemen_permission->name, '.')
                    ? Str::before($manajemen_permission->name, '.')
                    : $manajemen_permission->name;

                $newBaseName = Str::slug($request->module_name);
                $existingPermissions = Permission::where('name', 'like', $oldBaseName . '.%')->get();

                if ($oldBaseName !== $newBaseName) {
                    foreach ($existingPermissions as $existingPermission) {
                        $action = Str::after($existingPermission->name, $oldBaseName . '.');
                        $existingPermission->update([
                            'name' => $newBaseName . '.' . $action
                        ]);
                    }

                    Menu::where('permission', $oldBaseName . '.read')->update(['permission' => $newBaseName . '.read']);
                }

                $currentActions = Permission::where('name', 'like', $newBaseName . '.%')
                    ->get()
                    ->map(fn($p) => Str::after($p->name, $newBaseName . '.'))
                    ->toArray();

                $desiredActions = $request->access;

                $actionsToDelete = array_diff($currentActions, $desiredActions);
                foreach ($actionsToDelete as $action) {
                    Permission::where('name', $newBaseName . '.' . $action)->delete();

                    if ($action === 'read') {
                        Menu::where('permission', $newBaseName . '.read')->update(['permission' => null]);
                    }
                }

                $actionsToAdd = array_diff($desiredActions, $currentActions);
                foreach ($actionsToAdd as $action) {
                    $permissionName = $newBaseName . '.' . $action;
                    if (!Permission::where('name', $permissionName)->exists()) {
                        Permission::create([
                            'name' => $permissionName,
                            'guard_name' => 'web'
                        ]);
                    }
                }

                if (in_array('read', $desiredActions)) {
                    if ($request->menu_id) {
                        Menu::where('permission', $newBaseName . '.read')
                            ->where('id', '!=', $request->menu_id)
                            ->update(['permission' => null]);

                        $menu = Menu::find($request->menu_id);
                        if ($menu) {
                            $menu->update(['permission' => $newBaseName . '.read']);
                        }
                    } else {
                        Menu::where('permission', $newBaseName . '.read')->update(['permission' => null]);
                    }
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil diperbarui'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui permission: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Permission $manajemen_permission)
    {
        try {
            DB::transaction(function () use ($manajemen_permission) {
                $baseName = Str::contains($manajemen_permission->name, '.')
                    ? Str::before($manajemen_permission->name, '.')
                    : $manajemen_permission->name;

                Menu::where('permission', $baseName . '.read')->update(['permission' => null]);

                Permission::where('name', 'like', $baseName . '.%')->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Permission berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus permission: ' . $e->getMessage(),
            ], 500);
        }
    }
}
