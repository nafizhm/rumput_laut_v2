<?php

use Illuminate\Database\Migrations\Migration;
use Database\Seeders\HarvestFinanceSeeder;

return new class extends Migration
{
    public function up(): void { (new HarvestFinanceSeeder)->run(); }
    public function down(): void
    {
        \App\Models\Menu::where('route', 'harvest-finance.index')->delete();
    }
};
