<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Services\Auth\RoutePermissionService;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

class MenuAccessSeeder extends Seeder
{
    public function run(): void
    {
        $editProduction = Permission::findOrCreate('production.update', 'web');
        foreach (Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->where('name', 'production.create'))->get() as $role) {
            $role->givePermissionTo($editProduction);
        }
        foreach (['invoice', 'surat-jalan', 'po-konsumen'] as $module) {
            foreach (['read', 'create', 'update', 'delete'] as $action) {
                $permission = Permission::findOrCreate($module.'.'.$action, 'web');
                $roles = Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->where('name', 'manajemen-penjualan.'.$action))->get();
                foreach ($roles as $role) $role->givePermissionTo($permission);
            }
        }
        $warehouse = Permission::findOrCreate('manajemen-gudang.read', 'web');
        foreach (Role::where('guard_name', 'web')->whereHas('permissions', fn ($query) => $query->where('name', 'receiving.read'))->get() as $role) {
            $role->givePermissionTo($warehouse);
        }
        foreach (Role::where('guard_name', 'web')->where('name', 'superadmin')->get() as $role) {
            $role->givePermissionTo(Permission::where('guard_name', 'web')->get());
        }
        foreach (Menu::whereNotNull('route')->get() as $menu) {
            if ($permission = RoutePermissionService::permission($menu->route)) {
                $menu->update(['permission' => $permission]);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
