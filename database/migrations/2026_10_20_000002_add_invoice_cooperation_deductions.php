<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('cooperation_deduction')->default(0);
            $table->text('cooperation_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', fn (Blueprint $table) => $table->dropColumn(['cooperation_deduction', 'cooperation_notes']));
    }
};
