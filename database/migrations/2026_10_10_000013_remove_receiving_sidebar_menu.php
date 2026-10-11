<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Menu::where('route', 'penerimaan-barang.index')->delete();
    }

    public function down(): void
    {
        $warehouse = Menu::whereNull('parent_id')->where('name', 'Manajemen Gudang')->firstOrFail();
        Menu::updateOrCreate(['route' => 'penerimaan-barang.index'], [
            'name' => 'Penerimaan Barang', 'url' => 'penerimaan-barang',
            'parent_id' => $warehouse->id, 'permission' => 'receiving.read',
            'order' => (int) $warehouse->children()->max('order') + 1,
            'is_active' => true,
        ]);
    }
};
