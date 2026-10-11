<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_transactions', function (Blueprint $table) {
            $table->boolean('separate_payment')->default(false)->index();
            $table->date('purchase_date')->nullable();
            $table->timestamp('priced_at')->nullable();
            $table->foreignId('priced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('priced_by_name')->nullable();
        });
        Schema::create('harvest_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('harvest_transaction_id')->constrained()->restrictOnDelete();
            $table->uuid('submission_key')->unique();
            $table->date('payment_date');
            $table->unsignedBigInteger('amount')->default(0);
            $table->unsignedBigInteger('debt_deduction')->default(0);
            $table->foreignId('cash_account_id')->nullable()->constrained('chart_of_accounts')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_payments');
        Schema::table('harvest_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('priced_by');
            $table->dropColumn(['separate_payment', 'purchase_date', 'priced_at', 'priced_by_name']);
        });
    }
};
