<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 100)->unique();
            $table->date('po_date');
            $table->string('customer_name');
            $table->string('customer_phone', 30)->nullable();
            $table->text('customer_address')->nullable();
            $table->string('item_name');
            $table->decimal('quantity_kg', 11, 2);
            $table->decimal('price_per_kg', 11, 2);
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_purchase_orders');
    }
};
