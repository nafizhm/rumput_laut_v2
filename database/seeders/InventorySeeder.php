<?php

namespace Database\Seeders;

use App\Models\Inventory;
use Illuminate\Database\Seeder;

class InventorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Inventory::updateOrCreate(
            ['item_name' => 'Gudang Sidoarjo'],
            [
                'current_stock_kg' => 0,
                'current_sack_count' => 0,
            ]
        );
    }
}
