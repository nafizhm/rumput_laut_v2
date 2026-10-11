<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserManagementMenuSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $permissions = ['users.read', 'users.create', 'users.update'];
        foreach ($permissions as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (Role::whereIn('name', ['superadmin', 'owner', 'admin'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo($permissions);
        }
        $parent = Menu::firstOrCreate(['name' => 'Setting', 'parent_id' => null], ['icon' => 'settings', 'order' => 5, 'is_active' => true]);
        Menu::updateOrCreate(['route' => 'users.index'], [
            'name' => 'Manajemen Pengguna', 'url' => 'setting/users', 'parent_id' => $parent->id,
            'permission' => 'users.read', 'order' => 5, 'is_active' => true,
        ]);
    }
}
