<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\DirectSalesMenuSeeder)->run();
    }

    public function down(): void
    {
        $parent = DB::table('menus')->whereNull('parent_id')->where('name', 'Manajemen Gudang')->first();
        if ($parent) {
            DB::table('menus')->where('route', 'manajemen-penjualan.index')->update([
                'name' => 'Manajemen Penjualan', 'parent_id' => $parent->id, 'order' => 7,
            ]);
        }
    }
};
