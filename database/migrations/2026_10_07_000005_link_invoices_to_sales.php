<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->foreignId('sales_transaction_id')->nullable()->unique()->constrained('sales_transactions')->restrictOnDelete();
            $table->unsignedBigInteger('sale_total_amount')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropConstrainedForeignId('sales_transaction_id');
            $table->dropColumn('sale_total_amount');
        });
    }
};
