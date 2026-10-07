<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\FinanceMenuSeeder)->run();
    }

    public function down(): void
    {
        DB::transaction(function () {
            $warehouse = DB::table('menus')->whereNull('parent_id')->where('name', 'Manajemen Gudang')->first();
            $finance = DB::table('menus')->whereNull('parent_id')->where('name', 'Keuangan')->first();
            if (! $warehouse || ! $finance) {
                return;
            }
            DB::table('menus')->where('route', 'manajemen-pinjaman.index')->update(['parent_id' => $warehouse->id, 'order' => 1]);
            DB::table('menus')->where('route', 'cash-flow.index')->update(['parent_id' => null, 'order' => $finance->order]);
            DB::table('menus')->where('id', $warehouse->id)->update(['name' => 'Transaksi', 'icon' => 'circle-dollar-sign']);
            DB::table('menus')->where('parent_id', $finance->id)->update(['parent_id' => $warehouse->id]);
            DB::table('menus')->where('id', $finance->id)->delete();
        });
    }
};
