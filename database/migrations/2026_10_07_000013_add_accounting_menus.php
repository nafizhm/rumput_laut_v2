<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void { (new \Database\Seeders\AccountingMenuSeeder)->run(); }
    public function down(): void { DB::table('menus')->whereIn('route', ['jurnal.index', 'saldo-awal.index', 'neraca.index', 'laba-rugi.index'])->delete(); }
};
