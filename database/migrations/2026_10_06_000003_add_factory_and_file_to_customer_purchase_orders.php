<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_purchase_orders', function (Blueprint $table) {
            $table->foreignId('factory_id')->nullable()->constrained('factories')->nullOnDelete();
            $table->string('po_file_path')->nullable();
            $table->string('po_file_name')->nullable();
            $table->string('po_file_mime')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('customer_purchase_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('factory_id');
            $table->dropColumn(['po_file_path', 'po_file_name', 'po_file_mime']);
        });
    }
};
