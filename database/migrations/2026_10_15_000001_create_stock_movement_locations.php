<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('stock_movement_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_movement_id')->constrained()->cascadeOnDelete();
            // Keep the historical names even when an area is renamed or removed.
            $table->unsignedBigInteger('warehouse_area_id')->nullable()->index();
            $table->string('area_name');
            $table->string('counterpart_area_name')->nullable();
            $table->string('batch_code')->nullable();
            $table->string('item_name')->nullable();
            $table->decimal('weight_kg', 12, 2);
            $table->integer('sack_count');
            $table->boolean('inferred')->default(false);
            $table->timestamps();
        });

        // Only recover locations supported by an explicit batch or receipt link.
        \App\Models\StockMovement::query()->chunkById(200, function ($movements) {
            foreach ($movements as $movement) {
                app(\App\Services\Transaction\MovementLocationService::class)->capture($movement, true);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movement_locations');
    }
};
