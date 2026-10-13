<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\{Permission, Role};
use Spatie\Permission\PermissionRegistrar;

class HarvestFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $financePermissions = ['harvest-finance.read', 'harvest-finance.price', 'harvest-finance.pay'];
        $warehousePermissions = ['manajemen-panen.read', 'manajemen-panen.create', 'receiving.read', 'receiving.create', 'receiving.confirm', 'area.read', 'quality.read', 'manajemen-gudang.read', 'production.read', 'production.create', 'production.confirm'];
        foreach (array_unique(array_merge($financePermissions, $warehousePermissions)) as $name) {
            Permission::findOrCreate($name, 'web');
        }
        foreach (Role::whereIn('name', ['owner', 'superadmin'])->get() as $role) $role->givePermissionTo($financePermissions);
        Role::firstOrCreate(['name' => 'keuangan', 'guard_name' => 'web'])->givePermissionTo($financePermissions);
        Role::firstOrCreate(['name' => 'gudang', 'guard_name' => 'web'])->givePermissionTo($warehousePermissions);
        // Existing receiving operators can continue entering physical harvests.
        foreach (Role::whereIn('name', ['owner', 'superadmin', 'admin'])->get() as $role) {
            $role->givePermissionTo(['manajemen-panen.read', 'manajemen-panen.create']);
        }
        $finance = Menu::firstOrCreate(['name' => 'Keuangan', 'parent_id' => null], ['icon' => 'circle-dollar-sign', 'order' => 3, 'is_active' => true]);
        Menu::updateOrCreate(['route' => 'harvest-finance.index'], [
            'name' => 'Pembelian & Pembayaran Panen', 'url' => 'keuangan/pembayaran-panen',
            'parent_id' => $finance->id, 'permission' => 'harvest-finance.read', 'is_active' => true, 'order' => 3,
        ]);
        (new FinanceNavigationSeeder)->run();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
