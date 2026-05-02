<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid')->after('total_sale_value');
            $table->date('payment_date')->nullable()->after('payment_status');
            $table->bigInteger('paid_amount')->nullable()->after('payment_date');
            $table->string('payment_proof_path')->nullable()->after('paid_amount');
            $table->text('payment_notes')->nullable()->after('payment_proof_path');
        });
    }

    public function down(): void
    {
        Schema::table('sales_transactions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_date',
                'paid_amount',
                'payment_proof_path',
                'payment_notes',
            ]);
        });
    }
};
