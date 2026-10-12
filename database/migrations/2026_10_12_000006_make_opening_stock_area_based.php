<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('opening_stock_entries', fn (Blueprint $table) => $table->unsignedBigInteger('quality_grade_id')->nullable()->change());
    }

    public function down(): void
    {
        // Preserve area-based entries, which have no grade.
    }
};
