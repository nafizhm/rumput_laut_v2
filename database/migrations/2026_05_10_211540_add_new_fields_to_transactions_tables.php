<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update harvest_transactions
        // First, we need to handle the enum change. MySQL doesn't support changing enum directly easily with Blueprint without doctrine/dbal.
        // We can use raw SQL to add 'weight' to the enum.
        DB::statement("ALTER TABLE harvest_transactions MODIFY COLUMN sale_deduction_type ENUM('none', 'percentage', 'nominal', 'weight') DEFAULT 'none'");

        Schema::table('harvest_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('harvest_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('is_received');
            }
        });

        // Update sales_transactions
        Schema::table('sales_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('sales_transactions', 'sold_sacks')) {
                $table->integer('sold_sacks')->nullable()->after('price_per_kg');
            }
            if (!Schema::hasColumn('sales_transactions', 'deduction_type')) {
                $table->enum('deduction_type', ['none', 'percentage', 'nominal', 'weight'])->default('none')->after('sold_sacks');
            }
            if (!Schema::hasColumn('sales_transactions', 'deduction_value')) {
                $table->decimal('deduction_value', 12, 2)->default(0)->after('deduction_type');
            }
            if (!Schema::hasColumn('sales_transactions', 'deduction_weight_kg')) {
                $table->decimal('deduction_weight_kg', 12, 2)->default(0)->after('deduction_value');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['sold_sacks', 'deduction_type', 'deduction_value', 'deduction_weight_kg']);
        });

        Schema::table('harvest_transactions', function (Blueprint $table) {
            $table->dropColumn('notes');
        });

        DB::statement("ALTER TABLE harvest_transactions MODIFY COLUMN sale_deduction_type ENUM('none', 'percentage', 'nominal') DEFAULT 'none'");
    }
};
