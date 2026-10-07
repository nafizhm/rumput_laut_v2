<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseNavigationSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            (new FinanceMenuSeeder)->run();
            $warehouse = Menu::whereNull('parent_id')->where('name', 'Manajemen Gudang')->firstOrFail();
            Menu::updateOrCreate(['route' => 'manajemen-gudang.index'], [
                'name' => 'Dashboard Gudang', 'parent_id' => $warehouse->id,
                'icon' => 'layout-dashboard', 'url' => 'manajemen-gudang', 'order' => 0,
                'permission' => 'receiving.read', 'is_active' => true,
            ]);
        });
    }
}
