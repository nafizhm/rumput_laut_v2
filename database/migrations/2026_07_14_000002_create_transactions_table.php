<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'income' or 'expense'
            $table->date('date');
            $table->bigInteger('amount');
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('expense_category_id')->nullable()->constrained()->onDelete('restrict');
            $table->timestamps();

            $table->index('type');
            $table->index('date');
        });

        // Copy incomes data
        DB::statement("
            INSERT INTO transactions (type, date, amount, description, notes, expense_category_id, created_at, updated_at)
            SELECT 'income', income_date, amount, description, NULL, NULL, created_at, updated_at
            FROM incomes
        ");

        // Copy expenses data
        DB::statement("
            INSERT INTO transactions (type, date, amount, description, notes, expense_category_id, created_at, updated_at)
            SELECT 'expense', expense_date, amount, description, NULL, expense_category_id, created_at, updated_at
            FROM expenses
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
