<?php

namespace App\Services\Setting;

use App\Models\Menu;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    /**
     * Get formatted permission data grouped by module.
     */
    public function getGroupedPermissions(): Collection
    {
        return Permission::all()->groupBy(function ($permission) {
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
    }

    /**
     * Get menus available for permission association.
     */
    public function getMenuOptions(): Collection
    {
        return Menu::whereNotNull('route')
            ->orWhereNotNull('url')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Create a group of permissions.
     */
    public function createPermissionGroup(array $data): void
    {
        DB::transaction(function () use ($data) {
            $moduleSlug = Str::slug($data['module_name']);

            foreach ($data['access'] as $access) {
                $permissionName = $moduleSlug . '.' . $access;

                if (!Permission::where('name', $permissionName)->exists()) {
                    Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                }
            }

            if (isset($data['menu_id']) && in_array('read', $data['access'])) {
                $menu = Menu::find($data['menu_id']);
                if ($menu) {
                    $menu->update(['permission' => $moduleSlug . '.read']);
                }
            }
        });
    }

    /**
     * Update a permission group.
     */
    public function updatePermissionGroup(Permission $permission, array $data): void
    {
        DB::transaction(function () use ($data, $permission) {
            $oldBaseName = Str::contains($permission->name, '.')
                ? Str::before($permission->name, '.')
                : $permission->name;

            $newBaseName = Str::slug($data['module_name']);
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

            $desiredActions = $data['access'];

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
                if (isset($data['menu_id'])) {
                    Menu::where('permission', $newBaseName . '.read')
                        ->where('id', '!=', $data['menu_id'])
                        ->update(['permission' => null]);

                    $menu = Menu::find($data['menu_id']);
                    if ($menu) {
                        $menu->update(['permission' => $newBaseName . '.read']);
                    }
                } else {
                    Menu::where('permission', $newBaseName . '.read')->update(['permission' => null]);
                }
            }
        });
    }

    /**
     * Delete a permission group.
     */
    public function deletePermissionGroup(Permission $permission): void
    {
        DB::transaction(function () use ($permission) {
            $baseName = Str::contains($permission->name, '.')
                ? Str::before($permission->name, '.')
                : $permission->name;

            Menu::where('permission', $baseName . '.read')->update(['permission' => null]);

            Permission::where('name', 'like', $baseName . '.%')->delete();
        });
    }
}
