<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quality_grade_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('item_name');
            $table->string('grade_name')->nullable();
            $table->decimal('quantity_kg', 12, 2);
            $table->decimal('price_per_kg', 14, 2);
            $table->timestamps();
        });
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->foreignId('customer_purchase_order_item_id')->nullable()->constrained('customer_purchase_order_items')->restrictOnDelete();
        });
        DB::table('customer_purchase_orders')->orderBy('id')->chunkById(100, function ($orders) {
            foreach ($orders as $order) {
                $id = DB::table('customer_purchase_order_items')->insertGetId([
                    'customer_purchase_order_id' => $order->id, 'item_name' => $order->item_name,
                    'quantity_kg' => $order->quantity_kg, 'price_per_kg' => $order->price_per_kg,
                    'created_at' => $order->created_at, 'updated_at' => $order->updated_at,
                ]);
                DB::table('sales_transactions')->where('customer_purchase_order_id', $order->id)->update(['customer_purchase_order_item_id' => $id]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_purchase_order_item_id');
        });
        Schema::dropIfExists('customer_purchase_order_items');
    }
};
