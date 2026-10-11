<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', fn (Blueprint $table) => $table->foreignId('quality_grade_id')->nullable()->constrained()->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('sales_transactions', fn (Blueprint $table) => $table->dropConstrainedForeignId('quality_grade_id'));
    }
};
