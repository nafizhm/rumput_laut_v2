<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factory_id')->constrained()->onDelete('restrict');
            $table->foreignId('inventory_id')->constrained()->onDelete('restrict');
            $table->string('invoice_number')->unique();
            $table->date('sale_date');
            $table->decimal('total_weight_kg', 12, 2);
            $table->bigInteger('price_per_kg');
            $table->bigInteger('total_sale_value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_transactions');
    }
};
