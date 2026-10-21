<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->string('cart_deduction_type')->nullable();
            $table->decimal('cart_deduction_value', 12, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn(['cart_deduction_type', 'cart_deduction_value']);
        });
    }
};
