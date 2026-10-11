<?php

namespace Database\Seeders;

use App\Models\Inventory;
use App\Models\Menu;
use App\Models\WarehouseArea;
use Illuminate\Database\Seeder;

class WarehouseLocationSeeder extends Seeder
{
    public function run(): void
    {
        $material = Inventory::firstOrCreate(['stock_stage' => 'material'], ['item_name' => 'Bahan Masuk', 'current_stock_kg' => 0, 'current_sack_count' => 0]);
        $finished = Inventory::firstOrCreate(['stock_stage' => 'finished'], ['item_name' => 'Barang Jadi', 'current_stock_kg' => 0, 'current_sack_count' => 0]);
        foreach ([['BM-A', 'Area 1'], ['BM-B', 'Area 2'], ['BM-C', 'Area 3']] as [$code, $name]) {
            WarehouseArea::firstOrCreate(['code' => $code], ['name' => $name, 'inventory_id' => $material->id, 'usage' => 'receiving', 'is_active' => true]);
        }
        foreach (['A', 'B', 'C', 'D'] as $letter) {
            WarehouseArea::firstOrCreate(['code' => 'BJ-'.$letter], ['name' => 'Area '.$letter, 'inventory_id' => $finished->id, 'usage' => 'finished', 'is_active' => true]);
        }
        $master = Menu::firstOrCreate(['name' => 'Master', 'parent_id' => null], ['is_active' => true, 'icon' => 'database', 'order' => 1]);
        Menu::whereIn('route', ['kualitas-barang.index', 'area-gudang.index'])->update(['is_active' => false]);
        foreach ([['area-barang-masuk.index', 'Area Barang Masuk', 'area-barang-masuk'], ['area-barang-ready.index', 'Area Barang Ready', 'area-barang-ready']] as $index => [$route, $name, $url]) {
            Menu::updateOrCreate(['route' => $route], ['name' => $name, 'url' => $url, 'parent_id' => $master->id, 'permission' => 'area.read', 'order' => 8 + $index, 'is_active' => true]);
        }
    }
}
