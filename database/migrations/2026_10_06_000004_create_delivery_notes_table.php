<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique();
            $table->date('delivery_date');
            $table->foreignId('customer_purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name');
            $table->text('destination_address');
            $table->string('origin');
            $table->string('item_name');
            $table->decimal('quantity_kg', 11, 2);
            $table->string('packaging')->nullable();
            $table->text('remarks')->nullable();
            $table->text('notes')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('receiver_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
