<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->string('vehicle_plate', 30)->nullable();
            $table->string('vehicle_type', 100)->nullable();
            $table->string('driver_name')->nullable();
            $table->string('condition', 10)->nullable();
            $table->text('origin_address')->nullable();
            $table->string('sender_contact')->nullable();
            $table->string('recipient_contact')->nullable();
            $table->decimal('gross_weight_kg', 12, 2)->nullable();
            $table->decimal('tare_weight_kg', 12, 2)->nullable();
            $table->decimal('moisture_percent', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('delivery_notes', function (Blueprint $table) {
            $table->dropColumn(['vehicle_plate', 'vehicle_type', 'driver_name', 'condition', 'origin_address', 'sender_contact', 'recipient_contact', 'gross_weight_kg', 'tare_weight_kg', 'moisture_percent']);
        });
    }
};
