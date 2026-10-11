<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Menu::whereIn('route', ['manajemen-gudang.wet', 'manajemen-gudang.dry'])->update(['is_active' => false]);
    }

    public function down(): void
    {
        Menu::whereIn('route', ['manajemen-gudang.wet', 'manajemen-gudang.dry'])->update(['is_active' => true]);
    }
};
