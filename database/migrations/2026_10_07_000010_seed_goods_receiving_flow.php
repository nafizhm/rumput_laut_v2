<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void { (new \Database\Seeders\GoodsReceivingSeeder)->run(); }
    public function down(): void { DB::table('menus')->whereIn('route', ['area-gudang.index', 'penerimaan-barang.index'])->delete(); }
};
