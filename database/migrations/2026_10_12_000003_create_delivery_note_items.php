<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('stock_movements', fn(Blueprint $t)=>$t->dropUnique('stock_movements_delivery_batch_unique'));
        Schema::create('delivery_note_items', function (Blueprint $t) {
            $t->id(); $t->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
            $t->foreignId('sales_transaction_id')->constrained()->restrictOnDelete();
            $t->foreignId('finished_batch_id')->nullable()->constrained()->restrictOnDelete();
            $t->string('item_name'); $t->decimal('quantity_kg',12,2); $t->unsignedInteger('sack_count'); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('delivery_note_items'); }
};
