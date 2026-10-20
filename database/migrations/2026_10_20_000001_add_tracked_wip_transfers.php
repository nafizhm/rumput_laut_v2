<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_runs', function (Blueprint $table) {
            $table->boolean('wip_mode')->default(false);
            $table->uuid('wip_token')->nullable()->unique();
            $table->foreignId('source_area_id')->nullable()->constrained('warehouse_areas')->restrictOnDelete();
        });
        Schema::create('wip_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_run_id')->constrained()->restrictOnDelete();
            $table->uuid('token')->unique();
            $table->date('transfer_date');
            $table->unsignedInteger('source_sacks');
            $table->decimal('reference_weight_kg', 12, 2);
            $table->decimal('output_weight_kg', 12, 2);
            $table->unsignedInteger('package_count');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_name');
            $table->timestamps();
        });
        Schema::table('finished_batches', fn (Blueprint $table) => $table->foreignId('wip_transfer_id')->nullable()->constrained()->restrictOnDelete());
        DB::table('menus')->where('route', 'produksi.index')->update(['name' => 'WIP']);
    }

    public function down(): void
    {
        Schema::table('finished_batches', fn (Blueprint $table) => $table->dropConstrainedForeignId('wip_transfer_id'));
        Schema::dropIfExists('wip_transfers');
        Schema::table('production_runs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_area_id');
            $table->dropColumn('wip_mode');
            $table->dropUnique(['wip_token']);
            $table->dropColumn('wip_token');
        });
        DB::table('menus')->where('route', 'produksi.index')->update(['name' => 'Proses Harian']);
    }
};
