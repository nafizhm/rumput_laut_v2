<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerPurchaseOrderMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $order = (Menu::where('route', 'dashboard')->value('order') ?? 1) + 1;
            $parent = Menu::firstOrNew(['name' => 'Penjualan', 'parent_id' => null]);
            $following = Menu::whereNull('parent_id')
                ->when($parent->exists, fn ($query) => $query->where('id', '!=', $parent->id));
            if ((clone $following)->where('order', $order)->exists()) {
                $following->where('order', '>=', $order)->increment('order');
            }
            $parent->fill(['icon' => 'shopping-cart', 'order' => $order, 'is_active' => true])->save();
            Menu::updateOrCreate(['route' => 'po-konsumen.index'], [
                'name' => 'PO konsumen', 'url' => 'po-konsumen', 'parent_id' => $parent->id,
                'order' => 1, 'is_active' => true, 'permission' => 'po-konsumen.read',
            ]);
        });
    }
}
