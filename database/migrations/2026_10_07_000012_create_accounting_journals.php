<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema, DB};

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->date('start_date')->nullable();
            $table->timestamps();
        });
        DB::table('accounting_settings')->insert(['id' => 1, 'created_at' => now(), 'updated_at' => now()]);
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('number')->nullable()->unique();
            $table->date('entry_date')->index();
            $table->string('kind', 20)->default('general');
            $table->string('status', 20)->default('draft')->index();
            $table->text('description');
            $table->string('source_reference', 150)->nullable()->unique();
            $table->foreignId('reversal_of_id')->nullable()->unique()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('posted_by_name')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('debit_cents')->default(0);
            $table->unsignedBigInteger('credit_cents')->default(0);
            $table->string('account_code', 5);
            $table->string('account_name', 150);
            $table->string('category', 20);
            $table->string('account_group', 100);
            $table->string('normal_balance', 6);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('accounting_settings');
    }
};
