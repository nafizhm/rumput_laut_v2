<?php

namespace App\Services\Setting;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * Get all roles with formatted permission groups.
     */
    public function getRolesWithPermissions(): Collection
    {
        return Role::with('permissions')
            ->orderBy('id', 'desc')
            ->get()
            ->map(function ($role) {
                $grouped = [];

                foreach ($role->permissions as $permission) {
                    $parts = explode('.', $permission->name);
                    $menu = $parts[0] ?? $permission->name;
                    $action = $parts[1] ?? '';

                    if ($action) {
                        $grouped[$menu][] = $action;
                    }
                }

                $role->permission_grouped = $grouped;

                return $role;
            });
    }

    /**
     * Get all permissions available for assignment.
     */
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    /**
     * Create a new role.
     */
    public function createRole(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            if (!empty($data['permissions'])) {
                $role->givePermissionTo($data['permissions']);
            }

            return $role;
        });
    }

    /**
     * Update an existing role.
     */
    public function updateRole(Role $role, array $data): void
    {
        DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data['name'],
            ]);

            if (isset($data['permissions']) && !empty($data['permissions'])) {
                $role->syncPermissions($data['permissions']);
            } else {
                $role->syncPermissions([]);
            }
        });
    }

    /**
     * Delete a role.
     */
    public function deleteRole(Role $role): void
    {
        $role->delete();
    }
}
