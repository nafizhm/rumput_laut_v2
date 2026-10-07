<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class DirectSalesMenuSeeder extends Seeder
{
    public function run(): void
    {
        $parent = Menu::whereNull('parent_id')->where('name', 'Penjualan')->firstOrFail();
        Menu::updateOrCreate(['route' => 'manajemen-penjualan.index'], [
            'name' => 'Penjualan Langsung', 'parent_id' => $parent->id,
            'icon' => null, 'url' => 'manajemen-penjualan', 'order' => 4,
            'permission' => 'manajemen-penjualan.read', 'is_active' => true,
        ]);
    }
}
