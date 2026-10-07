<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\CustomerPurchaseOrderMenuSeeder)->run();
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'po-konsumen.index')->delete();
        $parent = DB::table('menus')->where('name', 'Penjualan')->whereNull('parent_id')->first();
        if ($parent && ! DB::table('menus')->where('parent_id', $parent->id)->exists()) {
            DB::table('menus')->where('id', $parent->id)->delete();
        }
    }
};
