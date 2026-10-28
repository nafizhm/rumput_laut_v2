<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        foreach (['delivery_notes', 'delivery_note_items'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->foreignId('warehouse_area_id')->nullable()->constrained('warehouse_areas')->restrictOnDelete());
        }
    }

    public function down(): void
    {
        foreach (['delivery_note_items', 'delivery_notes'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropConstrainedForeignId('warehouse_area_id'));
        }
    }
};
