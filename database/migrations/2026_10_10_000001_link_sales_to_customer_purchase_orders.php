<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->foreignId('customer_purchase_order_id')->nullable()->constrained('customer_purchase_orders')->restrictOnDelete();
            $table->decimal('price_per_kg', 14, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_purchase_order_id');
        });
    }
};
