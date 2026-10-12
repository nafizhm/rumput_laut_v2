<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->string('sale_group_number', 100)->nullable()->index();
            $table->string('item_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropIndex(['sale_group_number']);
            $table->dropColumn(['sale_group_number', 'item_name']);
        });
    }
};
