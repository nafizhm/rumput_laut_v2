<?php

use Database\Seeders\WarehouseActionMenuSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new WarehouseActionMenuSeeder)->run();
    }

    public function down(): void
    {
        \App\Models\Menu::whereIn('route', ['produksi.create', 'manajemen-gudang.movements'])->delete();
    }
};
