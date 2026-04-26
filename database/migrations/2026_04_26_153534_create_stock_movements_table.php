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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('harvest_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sales_transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->date('movement_date');
            $table->enum('direction', ['in', 'out']);
            $table->enum('type', ['in', 'out', 'shrinkage', 'adjustment']);
            $table->decimal('weight_kg', 12, 2);
            $table->decimal('note_weight_kg', 12, 2)->nullable();
            $table->decimal('discrepancy_kg', 12, 2)->default(0);
            $table->integer('sack_count')->default(0);
            $table->decimal('balance_after_kg', 12, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
