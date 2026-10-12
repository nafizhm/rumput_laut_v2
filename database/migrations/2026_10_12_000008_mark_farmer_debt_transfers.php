<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('transfer_from_farmer_id')->nullable()->constrained('farmers')->restrictOnDelete();
        });
        Schema::table('farmer_payments', function (Blueprint $table) {
            $table->foreignId('transfer_to_loan_id')->nullable()->constrained('loans')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('farmer_payments', fn (Blueprint $table) => $table->dropConstrainedForeignId('transfer_to_loan_id'));
        Schema::table('loans', fn (Blueprint $table) => $table->dropConstrainedForeignId('transfer_from_farmer_id'));
    }
};
