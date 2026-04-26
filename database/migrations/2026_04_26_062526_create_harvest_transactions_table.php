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
        Schema::create('harvest_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->onDelete('cascade');
            $table->string('batch_code')->unique();
            $table->date('harvest_date');
            $table->string('location');
            $table->decimal('wet_weight_kg', 10, 2);
            $table->bigInteger('price_per_kg');
            $table->integer('sack_count');
            $table->bigInteger('total_harvest_value');
            $table->bigInteger('cash_payout')->default(0);
            $table->bigInteger('debt_deduction');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harvest_transactions');
    }
};
