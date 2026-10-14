<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Menu::updateOrCreate(['route' => 'proses-bisnis.index'], [
            'name' => 'Proses Bisnis', 'icon' => 'chart-no-axes-combined', 'url' => 'proses-bisnis',
            'permission' => 'financial-reports.read', 'parent_id' => null, 'order' => 3, 'is_active' => true,
        ]);
    }

    public function down(): void
    {
        Menu::where('route', 'proses-bisnis.index')->delete();
    }
};
