<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventories', fn (Blueprint $t) => $t->string('stock_stage', 20)->default('legacy'));
        Schema::table('harvest_transactions', fn (Blueprint $t) => $t->string('receipt_mode', 20)->default('legacy'));
        Schema::create('warehouse_areas', function (Blueprint $t) {
            $t->id(); $t->string('code', 30)->unique(); $t->string('name', 100);
            $t->foreignId('inventory_id')->constrained()->restrictOnDelete();
            $t->string('usage', 20)->default('receiving'); $t->text('description')->nullable();
            $t->boolean('is_active')->default(true); $t->timestamps();
        });
        Schema::create('quality_grade_warehouse_area', function (Blueprint $t) {
            $t->foreignId('warehouse_area_id')->constrained()->cascadeOnDelete();
            $t->foreignId('quality_grade_id')->constrained()->restrictOnDelete();
            $t->primary(['warehouse_area_id', 'quality_grade_id']);
        });
        Schema::create('goods_receipts', function (Blueprint $t) {
            $t->id(); $t->string('number')->nullable()->unique(); $t->date('received_date');
            $t->string('source_reference', 100)->nullable()->unique();
            $t->foreignId('farmer_id')->constrained()->restrictOnDelete();
            $t->foreignId('harvest_transaction_id')->nullable()->unique()->constrained()->restrictOnDelete();
            $t->foreignId('inventory_id')->constrained()->restrictOnDelete();
            $t->decimal('note_weight_kg', 12, 2)->nullable(); $t->text('notes')->nullable();
            $t->string('status', 20)->default('draft'); $t->timestamp('confirmed_at')->nullable();
            $t->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('confirmed_by_name')->nullable(); $t->timestamps();
        });
        Schema::create('goods_receipt_lines', function (Blueprint $t) {
            $t->id(); $t->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $t->foreignId('quality_grade_id')->constrained()->restrictOnDelete();
            $t->foreignId('warehouse_area_id')->constrained()->restrictOnDelete();
            $t->decimal('actual_weight_kg', 12, 2); $t->unsignedInteger('sack_count'); $t->timestamps();
        });
        Schema::create('material_batches', function (Blueprint $t) {
            $t->id(); $t->string('code')->unique();
            $t->foreignId('goods_receipt_line_id')->unique()->constrained()->restrictOnDelete();
            $t->decimal('incoming_weight_kg', 12, 2); $t->unsignedInteger('incoming_sacks');
            $t->unsignedInteger('remaining_sacks'); $t->string('status', 20)->default('available'); $t->timestamps();
        });
        Schema::table('stock_movements', fn (Blueprint $t) => $t->foreignId('goods_receipt_id')->nullable()->unique()->constrained()->restrictOnDelete());
    }

    public function down(): void
    {
        Schema::table('stock_movements', fn (Blueprint $t) => $t->dropConstrainedForeignId('goods_receipt_id'));
        Schema::dropIfExists('material_batches'); Schema::dropIfExists('goods_receipt_lines'); Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('quality_grade_warehouse_area'); Schema::dropIfExists('warehouse_areas');
        Schema::table('harvest_transactions', fn (Blueprint $t) => $t->dropColumn('receipt_mode'));
        Schema::table('inventories', fn (Blueprint $t) => $t->dropColumn('stock_stage'));
    }
};
