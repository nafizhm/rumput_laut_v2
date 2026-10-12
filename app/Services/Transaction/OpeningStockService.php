<?php

namespace App\Services\Transaction;

use App\Models\{FinishedBatch, GoodsReceipt, Inventory, MaterialBatch, ProductionRun, QualityGrade, StockMovement, User, WarehouseArea};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpeningStockService
{
    public function save(array $data, User $user): void
    {
        DB::transaction(function () use ($data, $user) {
            $area = WarehouseArea::lockForUpdate()->findOrFail($data['warehouse_area_id']);
            $inventory = Inventory::lockForUpdate()->findOrFail($area->inventory_id);
            $expectedStage = ['receiving' => 'material', 'finished' => 'finished'][$area->usage] ?? null;
            if (! $area->is_active || ! $expectedStage || $inventory->stock_stage !== $expectedStage) {
                throw ValidationException::withMessages(['warehouse_area_id' => 'Pilih area aktif yang sesuai gudang penerimaan atau gudang ready.']);
            }
            if (! QualityGrade::whereKey($data['quality_grade_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['quality_grade_id' => 'Pilih kualitas yang aktif.']);
            }
            $weight = round((float) $data['weight_kg'], 2);
            $count = (int) $data['package_count'];
            $balance = round((float) $inventory->current_stock_kg + $weight, 2);
            $packages = (int) $inventory->current_sack_count + $count;
            if ($balance > 9999999999.99 || $packages > 2147483647) {
                throw ValidationException::withMessages(['weight_kg' => 'Total stok melebihi kapasitas pencatatan gudang.']);
            }
            $id = DB::table('opening_stock_entries')->insertGetId([
                'token' => $data['token'], 'stock_date' => $data['stock_date'], 'warehouse_area_id' => $area->id,
                'quality_grade_id' => $data['quality_grade_id'], 'weight_kg' => $weight, 'package_count' => $count,
                'notes' => $data['notes'] ?? null, 'created_by' => $user->id, 'created_by_name' => $user->name,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $code = 'AWAL-'.str_pad($id, 6, '0', STR_PAD_LEFT);
            $movement = ['inventory_id' => $inventory->id, 'movement_date' => $data['stock_date'], 'direction' => 'in',
                'type' => 'in', 'weight_kg' => $weight, 'sack_count' => $count, 'balance_after_kg' => $balance,
                'description' => 'Stok awal '.$code.' / '.$area->name.' / '.$user->name];
            if ($area->usage === 'receiving') {
                $receipt = new GoodsReceipt();
                $receipt->forceFill(['number' => $code, 'source_reference' => $code, 'received_date' => $data['stock_date'],
                    'inventory_id' => $inventory->id, 'farmer_id' => null, 'notes' => 'Stok awal. '.($data['notes'] ?? ''),
                    'status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => $user->id, 'confirmed_by_name' => $user->name])->save();
                $line = $receipt->lines()->create(['warehouse_area_id' => $area->id, 'quality_grade_id' => $data['quality_grade_id'],
                    'actual_weight_kg' => $weight, 'sack_count' => $count]);
                MaterialBatch::create(['code' => $code, 'goods_receipt_line_id' => $line->id, 'incoming_weight_kg' => $weight,
                    'incoming_sacks' => $count, 'remaining_weight_kg' => $weight, 'remaining_sacks' => $count, 'status' => 'available']);
                $movement['goods_receipt_id'] = $receipt->id;
            } else {
                // A completed stock-origin record lets existing sales FIFO use the opening batch.
                $run = ProductionRun::create(['number' => $code, 'started_date' => $data['stock_date'], 'completed_date' => $data['stock_date'],
                    'packed_date' => $data['stock_date'], 'warehouse_area_id' => $area->id, 'pic' => $user->name,
                    'work_type' => 'Stok Awal', 'notes' => $data['notes'] ?? null, 'status' => 'packed', 'warehouse_only' => true,
                    'created_by' => $user->id, 'created_by_name' => $user->name]);
                $batch = FinishedBatch::create(['code' => $code, 'production_run_id' => $run->id, 'inventory_id' => $inventory->id,
                    'warehouse_area_id' => $area->id, 'quality_grade_id' => $data['quality_grade_id'], 'weight_kg' => $weight,
                    'package_count' => $count, 'remaining_weight_kg' => $weight, 'remaining_packages' => $count,
                    'cost_cents' => 0, 'remaining_cost_cents' => 0]);
                $movement['finished_batch_id'] = $batch->id;
            }
            $inventory->update(['current_stock_kg' => $balance, 'current_sack_count' => $packages]);
            StockMovement::create($movement);
        });
    }
}
