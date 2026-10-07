<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        (new \Database\Seeders\QualityGradeSeeder)->run();
    }

    public function down(): void
    {
        DB::table('menus')->where('route', 'kualitas-barang.index')->delete();
    }
};
