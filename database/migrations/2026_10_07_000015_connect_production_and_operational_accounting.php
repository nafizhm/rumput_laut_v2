<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $t) {
            $t->nullableMorphs('source');
            $t->boolean('is_automatic')->default(false);
        });
        Schema::create('operational_events', function (Blueprint $t) {
            $t->id();
            $t->string('source_key', 191)->unique();
            $t->string('source_type');
            $t->unsignedBigInteger('source_id');
            $t->string('event');
            $t->json('snapshot');
            $t->foreignId('journal_entry_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedInteger('version')->default(0);
            $t->boolean('cancelled')->default(false);
            $t->timestamps();
        });
        Schema::table('material_batches', function (Blueprint $t) {
            $t->decimal('remaining_weight_kg', 12, 2)->default(0);
            $t->unsignedBigInteger('remaining_cost_cents')->default(0);
            $t->boolean('is_costed')->default(false);
        });
        Schema::create('production_runs', function (Blueprint $t) {
            $t->id();
            $t->string('number')->nullable()->unique();
            $t->date('started_date');
            $t->foreignId('warehouse_area_id')->constrained()->restrictOnDelete();
            $t->string('pic');
            $t->string('work_type');
            $t->text('notes')->nullable();
            $t->string('status', 20)->default('processing');
            $t->decimal('input_reference_weight_kg', 12, 2)->default(0);
            $t->unsignedInteger('input_sacks')->default(0);
            $t->unsignedBigInteger('material_cost_cents')->default(0);
            $t->unsignedBigInteger('processing_cost_cents')->default(0);
            $t->unsignedBigInteger('packing_cost_cents')->default(0);
            $t->date('completed_date')->nullable();
            $t->date('packed_date')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->string('created_by_name');
            $t->timestamps();
        });
        Schema::create('production_inputs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('production_run_id')->constrained()->restrictOnDelete();
            $t->foreignId('material_batch_id')->constrained()->restrictOnDelete();
            $t->unsignedInteger('sack_count');
            $t->decimal('reference_weight_kg', 12, 2);
            $t->unsignedBigInteger('cost_cents');
            $t->timestamps();
            $t->unique(['production_run_id', 'material_batch_id']);
        });
        Schema::create('finished_batches', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('production_run_id')->constrained()->restrictOnDelete();
            $t->foreignId('inventory_id')->constrained()->restrictOnDelete();
            $t->foreignId('warehouse_area_id')->constrained()->restrictOnDelete();
            $t->foreignId('quality_grade_id')->constrained()->restrictOnDelete();
            $t->decimal('weight_kg', 12, 2);
            $t->unsignedInteger('package_count');
            $t->decimal('remaining_weight_kg', 12, 2);
            $t->unsignedInteger('remaining_packages');
            $t->unsignedBigInteger('cost_cents');
            $t->unsignedBigInteger('remaining_cost_cents');
            $t->timestamps();
        });
        Schema::table('sales_transactions', fn (Blueprint $t) => $t->foreignId('finished_batch_id')->nullable()->constrained()->restrictOnDelete());
        Schema::table('stock_movements', function (Blueprint $t) {
            $t->foreignId('production_run_id')->nullable()->constrained()->restrictOnDelete();
            $t->foreignId('finished_batch_id')->nullable()->constrained()->restrictOnDelete();
            $t->unsignedBigInteger('cost_cents')->default(0);
        });

        // Repair only the dedicated receiving inventory, not historical warehouses.
        DB::transaction(function () {
            $candidate = DB::table('inventories')->where('item_name', 'Bahan Masuk')->lockForUpdate()->first();
            if ($candidate && $candidate->stock_stage === 'legacy'
                && ! DB::table('sales_transactions')->where('inventory_id', $candidate->id)->exists()
                && ! DB::table('stock_movements')->where('inventory_id', $candidate->id)->whereNull('goods_receipt_id')->exists()) {
                DB::table('inventories')->where('id', $candidate->id)->update(['stock_stage' => 'material']);
            }
            $material = DB::table('inventories')->where('stock_stage', 'material')->first();
            if (! $material) {
                $id = DB::table('inventories')->insertGetId(['item_name' => 'Bahan Masuk Terverifikasi', 'stock_stage' => 'material', 'current_stock_kg' => 0, 'current_sack_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
                DB::table('warehouse_areas')->where('usage', 'receiving')->whereNotExists(function ($q) {
                    $q->selectRaw('1')->from('goods_receipt_lines')->whereColumn('goods_receipt_lines.warehouse_area_id', 'warehouse_areas.id');
                })->update(['inventory_id' => $id]);
                $material = DB::table('inventories')->where('id', $id)->first();
            }
            $finished = DB::table('inventories')->where('stock_stage', 'finished')->first();
            $finishedId = $finished?->id ?? DB::table('inventories')->insertGetId(['item_name' => 'Barang Jadi', 'stock_stage' => 'finished', 'current_stock_kg' => 0, 'current_sack_count' => 0, 'created_at' => now(), 'updated_at' => now()]);
            foreach ([['PR-A', 'Area Proses', 'processing', $material->id], ['BJ-A', 'Area Barang Jadi', 'finished', $finishedId]] as [$code,$name,$usage,$inventoryId]) {
                $area = DB::table('warehouse_areas')->where('code', $code)->first();
                if (! $area) {
                    $areaId = DB::table('warehouse_areas')->insertGetId(['code' => $code, 'name' => $name, 'usage' => $usage, 'inventory_id' => $inventoryId, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
                    foreach (DB::table('quality_grades')->pluck('id') as $grade) {
                        DB::table('quality_grade_warehouse_area')->insert(['warehouse_area_id' => $areaId, 'quality_grade_id' => $grade]);
                    }
                }
            }
            // Existing physical batches need their purchase linked before production.
            DB::table('material_batches')->update(['remaining_weight_kg' => DB::raw('incoming_weight_kg')]);
        });
        foreach (['production.read', 'production.create', 'production.confirm'] as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            foreach (Role::whereIn('name', ['superadmin', 'owner', 'admin'])->get() as $role) {
                $role->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $t) {
            $t->dropConstrainedForeignId('production_run_id');
            $t->dropConstrainedForeignId('finished_batch_id');
            $t->dropColumn('cost_cents');
        });
        Schema::table('sales_transactions', fn (Blueprint $t) => $t->dropConstrainedForeignId('finished_batch_id'));
        Schema::dropIfExists('finished_batches');
        Schema::dropIfExists('production_inputs');
        Schema::dropIfExists('production_runs');
        Schema::table('material_batches', fn (Blueprint $t) => $t->dropColumn(['remaining_weight_kg', 'remaining_cost_cents', 'is_costed']));
        Schema::dropIfExists('operational_events');
        Schema::table('journal_entries', function (Blueprint $t) {
            $t->dropMorphs('source');
            $t->dropColumn('is_automatic');
        });
    }
};
