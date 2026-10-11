<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->index('delivery_note_id', 'stock_movements_delivery_note_lookup');
            $table->dropUnique(['delivery_note_id']);
            $table->unique(['delivery_note_id', 'finished_batch_id'], 'stock_movements_delivery_batch_unique');
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('stock_movements')->whereNotNull('delivery_note_id')->groupBy('delivery_note_id')->havingRaw('COUNT(*) > 1')->exists()) {
            throw new \RuntimeException('Rollback tidak dapat dilakukan setelah pengiriman memakai beberapa batch.');
        }
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropUnique('stock_movements_delivery_batch_unique');
            $table->unique('delivery_note_id');
            $table->dropIndex('stock_movements_delivery_note_lookup');
        });

    }
};
