<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('opening_stock_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->date('stock_date');
            $table->foreignId('warehouse_area_id')->constrained()->restrictOnDelete();
            $table->foreignId('quality_grade_id')->constrained()->restrictOnDelete();
            $table->decimal('weight_kg', 12, 2);
            $table->unsignedInteger('package_count');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->timestamps();
        });
        (new \Database\Seeders\OpeningStockMenuSeeder)->run();
    }

    public function down(): void
    {
        \App\Models\Menu::where('route', 'stok-awal.index')->delete();
        Schema::dropIfExists('opening_stock_entries');
    }
};
