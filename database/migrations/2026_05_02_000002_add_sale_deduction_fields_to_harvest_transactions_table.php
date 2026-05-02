<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_transactions', function (Blueprint $table) {
            $table->enum('sale_deduction_type', ['none', 'percentage', 'nominal'])->default('none')->after('price_per_kg');
            $table->decimal('sale_deduction_value', 12, 2)->default(0)->after('sale_deduction_type');
            $table->bigInteger('sale_deduction_amount')->default(0)->after('sale_deduction_value');
        });
    }

    public function down(): void
    {
        Schema::table('harvest_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'sale_deduction_type',
                'sale_deduction_value',
                'sale_deduction_amount',
            ]);
        });
    }
};
