<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\WarehouseActionMenuSeeder)->run();
    }

    public function down(): void
    {
        \App\Models\Menu::where('route', 'produksi.index')->update([
            'route' => 'produksi.create', 'url' => 'produksi/create',
            'name' => 'Mulai Proses Harian', 'permission' => 'production.create',
        ]);
    }
};
