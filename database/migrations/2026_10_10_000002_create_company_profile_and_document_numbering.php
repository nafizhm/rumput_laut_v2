<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->text('address')->nullable();
            $table->string('phone', 60)->nullable();
            $table->string('tagline')->nullable();
            $table->string('tax_number', 60)->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 100)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('signatory_name')->nullable();
            $table->timestamps();
        });
        DB::table('company_profiles')->insert([
            'id' => 1, 'company_name' => 'PT Albagro Indo Sejahtera', 'tagline' => 'Pemasok Rumput Laut Kering',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Schema::create('document_number_rules', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30)->unique();
            $table->string('format', 80);
            $table->unsignedTinyInteger('padding')->default(4);
            $table->unsignedInteger('start_number')->default(1);
            $table->string('reset_period', 10)->default('year');
            $table->timestamps();
        });
        foreach (['delivery_note' => 'SJ', 'invoice' => 'INV'] as $type => $prefix) {
            DB::table('document_number_rules')->insert([
                'document_type' => $type, 'format' => $prefix.'/{SEQ}/{MM}/{YYYY}',
                'padding' => 4, 'start_number' => 1, 'reset_period' => 'year',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        Schema::create('document_number_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_number_rule_id')->constrained()->cascadeOnDelete();
            $table->string('period', 10);
            $table->unsignedBigInteger('next_number');
            $table->unique(['document_number_rule_id', 'period']);
        });
        if (Schema::hasTable('menus') && Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            (new \Database\Seeders\CompanyProfileMenuSeeder)->run();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            DB::table('menus')->where('route', 'company-profile.index')->delete();
        }
        Schema::dropIfExists('document_number_counters');
        Schema::dropIfExists('document_number_rules');
        Schema::dropIfExists('company_profiles');
    }
};
