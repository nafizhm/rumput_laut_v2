<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\WarehouseNavigationSeeder)->run();
    }

    public function down(): void
    {
        DB::transaction(function () {
            $warehouse = DB::table('menus')->whereNull('parent_id')->where('name', 'Manajemen Gudang')->first();
            if (! $warehouse) return;
            DB::table('menus')->where('route', 'manajemen-gudang.index')->update([
                'name' => 'Manajemen Gudang', 'order' => 4, 'permission' => 'manajemen-gudang.read',
            ]);
            DB::table('menus')->where('route', 'manajemen-pemasukan-pengeluaran.index')->update([
                'parent_id' => $warehouse->id, 'order' => 3,
            ]);
        });
    }
};
