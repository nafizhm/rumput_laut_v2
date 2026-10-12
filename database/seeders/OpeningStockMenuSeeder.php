<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

class OpeningStockMenuSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['opening-stock.read', 'opening-stock.create'] as $name) {
            $permission = Permission::findOrCreate($name, 'web');
            foreach (Role::where('guard_name', 'web')->whereIn('name', ['superadmin', 'owner', 'admin', 'gudang'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        $parent = Menu::whereNull('parent_id')->where('name', 'Manajemen Gudang')->firstOrFail();
        Menu::updateOrCreate(['route' => 'stok-awal.index'], ['name' => 'Stok Awal', 'url' => 'stok-awal',
            'parent_id' => $parent->id, 'order' => 5, 'permission' => 'opening-stock.read', 'is_active' => true]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
