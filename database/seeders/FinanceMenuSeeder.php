<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $warehouse = Menu::whereNull('parent_id')->whereIn('name', ['Transaksi', 'Manajemen Gudang'])->firstOrFail();
            $warehouse->update(['name' => 'Manajemen Gudang', 'icon' => 'warehouse']);

            $finance = Menu::firstOrNew(['name' => 'Keuangan', 'parent_id' => null]);
            $order = $warehouse->order + 1;
            if (! $finance->exists) {
                Menu::whereNull('parent_id')->where('order', '>=', $order)->increment('order');
            }
            $finance->fill([
                'icon' => 'circle-dollar-sign', 'route' => null, 'url' => null,
                'permission' => null, 'order' => $order, 'is_active' => true,
            ])->save();

            foreach (['manajemen-pemasukan-pengeluaran.index' => 1, 'manajemen-pinjaman.index' => 2, 'cash-flow.index' => 4] as $route => $order) {
                Menu::where('route', $route)->update(['parent_id' => $finance->id, 'order' => $order]);
            }
        });
        (new FinanceNavigationSeeder)->run();
    }
}
