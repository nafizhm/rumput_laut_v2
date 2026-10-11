<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CompanyProfileMenuSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        foreach (['company-profile.read', 'company-profile.update'] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
        foreach (Role::whereIn('name', ['superadmin', 'owner', 'admin'])->where('guard_name', 'web')->get() as $role) {
            $role->givePermissionTo(['company-profile.read', 'company-profile.update']);
        }
        $parent = Menu::firstOrCreate(['name' => 'Setting', 'parent_id' => null], ['icon' => 'settings', 'order' => 5, 'is_active' => true]);
        Menu::updateOrCreate(['route' => 'company-profile.index'], [
            'name' => 'Profile', 'url' => 'setting/profile', 'parent_id' => $parent->id,
            'permission' => 'company-profile.read', 'order' => 4, 'is_active' => true,
        ]);
    }
}
