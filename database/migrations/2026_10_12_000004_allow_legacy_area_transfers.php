<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('goods_receipts', fn (Blueprint $table) => $table->unsignedBigInteger('farmer_id')->nullable()->change());
        Schema::table('finished_batches', fn (Blueprint $table) => $table->unsignedBigInteger('production_run_id')->nullable()->change());
    }

    public function down(): void
    {
        Schema::table('goods_receipts', fn (Blueprint $table) => $table->unsignedBigInteger('farmer_id')->nullable(false)->change());
        Schema::table('finished_batches', fn (Blueprint $table) => $table->unsignedBigInteger('production_run_id')->nullable(false)->change());
    }
};
