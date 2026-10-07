<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number', 100)->unique();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->foreignId('delivery_note_id')->nullable()->constrained()->nullOnDelete();
            $table->string('customer_name');
            $table->text('customer_address');
            $table->string('item_name');
            $table->decimal('quantity_kg', 9, 2);
            $table->decimal('price_per_kg', 9, 2);
            $table->string('payment_status', 10)->default('unpaid');
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
