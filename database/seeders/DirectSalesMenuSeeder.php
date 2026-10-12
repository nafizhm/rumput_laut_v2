<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirectSalesMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $po = Menu::where('route', 'po-konsumen.index')->firstOrFail();
            $sales = Menu::updateOrCreate(['route' => 'manajemen-penjualan.index'], [
                'name' => 'penjualan', 'parent_id' => $po->parent_id,
                'icon' => null, 'url' => 'manajemen-penjualan', 'order' => $po->order + 1,
                'permission' => 'manajemen-penjualan.read', 'is_active' => true,
            ]);
            $order = $po->order + 2;
            foreach (Menu::where('parent_id', $po->parent_id)->whereNotIn('id', [$po->id, $sales->id])
                ->where('order', '>=', $po->order)->orderBy('order')->orderBy('id')->get() as $menu) {
                $menu->update(['order' => $order++]);
            }
        });
    }
}
