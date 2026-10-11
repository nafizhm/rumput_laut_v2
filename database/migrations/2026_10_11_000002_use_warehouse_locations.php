<?php

use Database\Seeders\WarehouseLocationSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['goods_receipt_lines', 'finished_batches'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->unsignedBigInteger('quality_grade_id')->nullable()->change());
        }
        // Rename only the original defaults; preserve custom area names and all stock references.
        foreach ([['BM-A', 'Area Bahan Grade A', 'Area 1'], ['BM-B', 'Area Bahan Grade B', 'Area 2'], ['BM-C', 'Area Bahan Grade C', 'Area 3'], ['BJ-A', 'Area Barang Jadi', 'Area A']] as [$code, $old, $new]) {
            DB::table('warehouse_areas')->where('code', $code)->where('name', $old)->update(['name' => $new]);
        }
        (new WarehouseLocationSeeder)->run();
        // Keep an occupied legacy area available under its existing identity.
        DB::table('warehouse_areas')->where('code', 'BM-K')->where('name', 'Area Pemeriksaan / Karantina')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('goods_receipt_lines')->whereColumn('warehouse_area_id', 'warehouse_areas.id'))
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('menus')->whereIn('route', ['area-barang-masuk.index', 'area-barang-ready.index'])->update(['is_active' => false]);
        DB::table('menus')->whereIn('route', ['kualitas-barang.index', 'area-gudang.index'])->update(['is_active' => true]);
    }
};
