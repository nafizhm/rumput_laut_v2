<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('chart_of_account_id')->nullable()->constrained('chart_of_accounts')->restrictOnDelete();
            $table->foreignId('cash_account_id')->nullable()->constrained('chart_of_accounts')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cash_account_id');
            $table->dropConstrainedForeignId('chart_of_account_id');
        });
    }
};
