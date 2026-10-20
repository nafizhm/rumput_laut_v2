<?php

namespace App\Services\Transaction;

use App\Models\FinishedBatch;
use App\Models\Inventory;
use App\Models\ProductionRun;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseArea;
use App\Models\WipTransfer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class WipService
{
    public function start(array $data, User $user): ProductionRun
    {
        return DB::transaction(function () use ($data, $user) {
            WarehouseArea::lockForUpdate()->findOrFail($data['source_area_id']);
            if (ProductionRun::where('wip_token', $data['token'])->exists()) {
                $this->fail('token', 'WIP ini sudah dicatat.');
            }
            // FIFO allocation and stock consumption share the existing production transaction.
            $run = app(ProductionService::class)->recordDaily([
                'date' => $data['date'], 'sources' => [['warehouse_area_id' => $data['source_area_id'], 'sack_count' => $data['sack_count']]],
                'notes' => $data['notes'] ?? null, 'start_wip' => true,
            ], $user);
            $run->forceFill(['wip_mode' => true, 'wip_token' => $data['token'], 'source_area_id' => $data['source_area_id'],
                'work_type' => $data['work_type'], 'pic' => $data['pic']])->save();

            return $run->refresh();
        });
    }

    public function transfer(ProductionRun $run, array $data, User $user): WipTransfer
    {
        return DB::transaction(function () use ($run, $data, $user) {
            $run = ProductionRun::lockForUpdate()->findOrFail($run->id);
            if (WipTransfer::where('token', $data['token'])->exists()) {
                $this->fail('token', 'Pemindahan ini sudah dicatat. Muat ulang halaman sebelum mencatat pemindahan berikutnya.');
            }
            if (! $run->wip_mode || $run->status !== 'wip') {
                $this->fail('production', 'WIP ini sudah selesai atau tidak mendukung pemindahan bertahap.');
            }
            $transfers = $run->transfers()->get();
            $minimum = $transfers->max(fn ($transfer) => $transfer->transfer_date->format('Y-m-d')) ?? $run->started_date->format('Y-m-d');
            if ($data['packed_date'] < $minimum || $data['packed_date'] > now('Asia/Jakarta')->format('Y-m-d')) {
                $this->fail('packed_date', 'Tanggal harus sejak pemindahan terakhir dan tidak boleh di masa depan.');
            }
            $done = (int) $transfers->sum('source_sacks');
            $taken = (int) $data['source_sacks'];
            $remaining = (int) $run->input_sacks - $done;
            if ($taken < 1 || $taken > $remaining) {
                $this->fail('source_sacks', 'Karung bahan selesai melebihi sisa WIP.');
            }
            $referenceTotal = (int) round($run->input_reference_weight_kg * 100);
            // Cumulative rounding ensures the last transfer exhausts the allocation exactly.
            $reference = (int) round($referenceTotal * ($done + $taken) / $run->input_sacks)
                - (int) round($transfers->sum('reference_weight_kg') * 100);
            $totalOutput = 0;
            $packages = 0;
            foreach ($data['outputs'] as $output) {
                $kg = (int) round($output['weight_kg'] * 100);
                if ($kg <= 0 || (int) $output['package_count'] < 1) {
                    $this->fail('outputs', 'Isi berat hasil dan jumlah kemasan lebih dari nol.');
                }
                $totalOutput += $kg;
                $packages += (int) $output['package_count'];
            }
            if (! $totalOutput) {
                $this->fail('outputs', 'Isi hasil timbang aktual.');
            }
            if ($totalOutput > 999999999999) {
                $this->fail('outputs', 'Total berat hasil melebihi kapasitas pencatatan.');
            }
            $transfer = $run->transfers()->create(['token' => $data['token'], 'transfer_date' => $data['packed_date'],
                'source_sacks' => $taken, 'reference_weight_kg' => $reference / 100, 'output_weight_kg' => $totalOutput / 100,
                'package_count' => $packages, 'notes' => $data['notes'] ?? null,
                'created_by' => $user->id, 'created_by_name' => $user->name]);
            $priorCost = (int) $transfers->load('outputs')->sum(fn ($previous) => $previous->outputs->sum('cost_cents'));
            $cost = (int) round($run->material_cost_cents * ($done + $taken) / $run->input_sacks) - $priorCost;
            $allocated = 0;
            $weight = 0;
            foreach ($data['outputs'] as $index => $output) {
                $area = WarehouseArea::lockForUpdate()->findOrFail($output['warehouse_area_id']);
                $inventory = Inventory::lockForUpdate()->findOrFail($area->inventory_id);
                if (! $area->is_active || $area->usage !== 'finished' || $inventory->stock_stage !== 'finished') {
                    $this->fail('outputs', 'Pilih area barang ready aktif yang sesuai gudang barang jadi.');
                }
                $kg = (int) round($output['weight_kg'] * 100);
                $weight += $kg;
                $share = (int) round($cost * $weight / $totalOutput) - $allocated;
                $allocated += $share;
                $batch = new FinishedBatch;
                $batch->forceFill(['code' => 'WIP-'.$run->id.'-'.$transfer->id.'-'.($index + 1),
                    'production_run_id' => $run->id, 'wip_transfer_id' => $transfer->id, 'inventory_id' => $inventory->id,
                    'warehouse_area_id' => $area->id, 'weight_kg' => $kg / 100, 'remaining_weight_kg' => $kg / 100,
                    'package_count' => $output['package_count'], 'remaining_packages' => $output['package_count'],
                    'cost_cents' => $share, 'remaining_cost_cents' => $share])->save();
                if (round($inventory->current_stock_kg * 100) + $kg > 999999999999 || $inventory->current_sack_count + (int) $output['package_count'] > 2147483647) {
                    $this->fail('outputs', 'Saldo hasil melebihi kapasitas gudang.');
                }
                $inventory->increment('current_stock_kg', $kg / 100);
                $inventory->increment('current_sack_count', $output['package_count']);
                $movement = StockMovement::create(['production_run_id' => $run->id, 'finished_batch_id' => $batch->id,
                    'inventory_id' => $inventory->id, 'movement_date' => $data['packed_date'], 'direction' => 'in', 'type' => 'in',
                    'weight_kg' => $kg / 100, 'sack_count' => $output['package_count'], 'cost_cents' => $share,
                    'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => 'WIP '.$run->number.' dari '.$run->sourceArea->name.' ke '.$area->name.' / pemindahan '.$transfer->id]);
                // The movement observer already captured the destination; enrich its snapshot.
                if (Schema::hasTable('stock_movement_locations')) {
                    $movement->locations()->update(['counterpart_area_name' => $run->sourceArea->name]);
                }
            }
            if ($taken === $remaining) {
                $run->update(['status' => 'packed', 'packed_date' => $data['packed_date']]);
            }

            return $transfer;
        });
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
