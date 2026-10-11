<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseActionMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $warehouse = Menu::whereNull('parent_id')->where('name', 'Manajemen Gudang')->firstOrFail();
            Menu::where('route', 'produksi.create')->update(['route' => 'produksi.index']);

            foreach ([
                ['produksi.index', 'Proses Harian', 'produksi', 'production.read', 3],
                ['manajemen-gudang.movements', 'Riwayat Mutasi', 'manajemen-gudang/mutasi', 'manajemen-gudang.read', 4],
            ] as [$route, $name, $url, $permission, $order]) {
                Menu::updateOrCreate(['route' => $route], [
                    'name' => $name, 'url' => $url, 'permission' => $permission,
                    'parent_id' => $warehouse->id, 'order' => $order,
                    'icon' => null, 'is_active' => true,
                ]);
            }
        });
    }
}
