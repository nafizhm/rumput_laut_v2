<?php

namespace App\Services\Transaction;

use App\Models\FinishedBatch;
use App\Models\Inventory;
use App\Models\MaterialBatch;
use App\Models\ProductionRun;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseArea;
use App\Services\Finance\AccountingMoney;
use App\Services\Finance\OperationalAccountingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    public function recordDaily(array $data, User $user): ProductionRun
    {
        return DB::transaction(function () use ($data, $user) {
            $inputs = [];
            foreach (collect($data['sources'])->sortBy('warehouse_area_id') as $source) {
                $area = WarehouseArea::lockForUpdate()->findOrFail($source['warehouse_area_id']);
                if (!$area->is_active || $area->usage !== 'receiving') {
                    $this->fail('sources', 'Pilih area penerimaan yang aktif.');
                }
                $remaining = (int) $source['sack_count'];
                $batches = MaterialBatch::where('status', 'available')->where('remaining_sacks', '>', 0)
                    ->whereHas('line', fn ($q) => $q->where('warehouse_area_id', $area->id))
                    ->whereHas('line.receipt', fn ($q) => $q->where('status', 'confirmed')->whereDate('received_date', '<=', $data['date']))
                    ->orderBy(\App\Models\GoodsReceipt::select('received_date')
                        ->join('goods_receipt_lines', 'goods_receipt_lines.goods_receipt_id', '=', 'goods_receipts.id')
                        ->whereColumn('goods_receipt_lines.id', 'material_batches.goods_receipt_line_id')->limit(1))
                    ->orderBy('id')->lockForUpdate()->get();
                foreach ($batches as $batch) {
                    $taken = min($remaining, (int) $batch->remaining_sacks);
                    if ($taken > 0) $inputs[] = ['material_batch_id' => $batch->id, 'sack_count' => $taken];
                    $remaining -= $taken;
                    if (!$remaining) break;
                }
                if ($remaining > 0) $this->fail('sources', 'Karung tersedia di area asal tidak mencukupi pada tanggal ini.');
            }
            $run = $this->start([
                'started_date' => $data['date'], 'warehouse_area_id' => $data['sources'][0]['warehouse_area_id'],
                'pic' => $user->name, 'work_type' => 'Proses harian dan packing', 'notes' => $data['notes'] ?? null,
                'inputs' => $inputs, 'warehouse_only' => true, 'daily_record' => true,
            ], $user);
            $this->complete($run, $data['date']);
            return $this->pack($run, ['packed_date' => $data['date'], 'outputs' => $data['outputs']]);
        });
    }

    public function updateDetails(ProductionRun $run, array $data): ProductionRun
    {
        return DB::transaction(function () use ($run, $data) {
            $run = ProductionRun::lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'processing') {
                $this->fail('production', 'Hanya proses aktif yang dapat diedit.');
            }
            $run->update([
                'pic' => $data['pic'], 'work_type' => $data['work_type'],
                'notes' => $data['notes'] ?? null,
            ]);

            return $run;
        });
    }

    public function start(array $data, User $user): ProductionRun
    {
        return DB::transaction(function () use ($data, $user) {
            $warehouseOnly = (bool) ($data['warehouse_only'] ?? false);
            $this->date($data['started_date']);
            $area = WarehouseArea::lockForUpdate()->findOrFail($data['warehouse_area_id']);
            if (! $area->is_active || $area->usage !== (($data['daily_record'] ?? false) ? 'receiving' : 'processing')) {
                $this->fail('warehouse_area_id', 'Pilih area proses aktif.');
            }
            $inputs = collect($data['inputs'])->sortBy('material_batch_id')->values();
            if ($inputs->isEmpty() || $inputs->pluck('material_batch_id')->unique()->count() !== $inputs->count()) {
                $this->fail('inputs', 'Isi batch berbeda untuk setiap baris.');
            }
            $run = ProductionRun::create(['started_date' => $data['started_date'], 'warehouse_area_id' => $area->id, 'pic' => $data['pic'],
                'work_type' => $data['work_type'], 'notes' => $data['notes'] ?? null, 'processing_cost_cents' => $warehouseOnly ? 0 : AccountingMoney::cents($data['processing_cost'] ?? '0'),
                'warehouse_only' => $warehouseOnly,
                'created_by' => $user->id, 'created_by_name' => $user->name]);
            $run->number = 'PRD-'.str_replace('-', '', $data['started_date']).'-'.str_pad($run->id, 6, '0', STR_PAD_LEFT);
            $run->save();
            $weight = 0;
            $sacks = 0;
            $cost = 0;
            foreach ($inputs as $input) {
                $batch = MaterialBatch::with('line.receipt.harvest')->lockForUpdate()->findOrFail($input['material_batch_id']);
                $taken = (int) $input['sack_count'];
                if (! $warehouseOnly && (! $batch->is_costed || ! $batch->line->receipt->harvest_transaction_id)) {
                    $this->fail('inputs', 'Penerimaan '.$batch->line->receipt->number.' harus terhubung ke pembelian dengan harga yang sudah ditetapkan oleh keuangan sebelum bahan diproses.');
                }
                if ($batch->status !== 'available' || $batch->line->receipt->status !== 'confirmed' || $taken < 1 || $taken > $batch->remaining_sacks) {
                    $this->fail('inputs', 'Karung batch '.$batch->code.' tidak tersedia atau sudah dipakai.');
                }
                $this->date($data['started_date'], $batch->line->receipt->received_date->format('Y-m-d'));
                if (! $warehouseOnly && $batch->line->receipt->harvest?->separate_payment) {
                    $this->date($data['started_date'], $batch->line->receipt->harvest->purchase_date->format('Y-m-d'));
                }
                // Book weight/cost allocated by source sacks; actual output is weighed at packing.
                $whole = $taken === (int) $batch->remaining_sacks;
                $reference = $whole ? (int) round($batch->remaining_weight_kg * 100) : (int) round($batch->remaining_weight_kg * 100 * $taken / $batch->remaining_sacks);
                $inputCost = $whole ? (int) $batch->remaining_cost_cents : (int) round($batch->remaining_cost_cents * $taken / $batch->remaining_sacks);
                $inventory = Inventory::lockForUpdate()->findOrFail($batch->line->receipt->inventory_id);
                if ($inventory->stock_stage !== 'material' || (int) round($inventory->current_stock_kg * 100) < $reference || $inventory->current_sack_count < $taken) {
                    $this->fail('inputs', 'Saldo gudang bahan tidak sesuai batch.');
                }
                $batch->update(['remaining_sacks' => $batch->remaining_sacks - $taken, 'remaining_weight_kg' => ((int) round($batch->remaining_weight_kg * 100) - $reference) / 100,
                    'remaining_cost_cents' => $batch->remaining_cost_cents - $inputCost, 'status' => $whole ? 'consumed' : 'available']);
                $inventory->decrement('current_stock_kg', $reference / 100);
                $inventory->decrement('current_sack_count', $taken);
                $run->inputs()->create(['material_batch_id' => $batch->id, 'sack_count' => $taken, 'reference_weight_kg' => $reference / 100, 'cost_cents' => $inputCost]);
                StockMovement::create(['production_run_id' => $run->id, 'inventory_id' => $inventory->id, 'movement_date' => $data['started_date'], 'direction' => 'out', 'type' => 'out',
                    'weight_kg' => $reference / 100, 'sack_count' => $taken, 'cost_cents' => $inputCost, 'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => 'Bahan ke proses '.$run->number.' / '.$batch->code.' (alokasi berat datang, bukan timbang hasil)']);
                $weight += $reference;
                $sacks += $taken;
                $cost += $inputCost;
            }
            $run->update(['input_reference_weight_kg' => $weight / 100, 'input_sacks' => $sacks, 'material_cost_cents' => $cost]);
            app(OperationalAccountingService::class)->productionStart($run);

            return $run;
        });
    }

    public function complete(ProductionRun $run, string $date): ProductionRun
    {
        return DB::transaction(function () use ($run, $date) {
            $run = ProductionRun::lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'processing') {
                $this->fail('production', 'Hanya proses aktif yang dapat diselesaikan ke WIP.');
            }
            $this->date($date, $run->started_date->format('Y-m-d'));
            $run->update(['status' => 'wip', 'completed_date' => $date]);

            return $run;
        });
    }

    public function pack(ProductionRun $run, array $data): ProductionRun
    {
        return DB::transaction(function () use ($run, $data) {
            $run = ProductionRun::lockForUpdate()->findOrFail($run->id);
            if ($run->status !== 'wip' || $run->outputs()->exists()) {
                $this->fail('production', 'Hanya WIP yang belum dipacking dapat dikonfirmasi.');
            }
            $this->date($data['packed_date'], $run->completed_date->format('Y-m-d'));
            $outputs = $data['outputs'];
            $totalWeight = 0;
            foreach ($outputs as $output) {
                $kg = (int) round((float) $output['weight_kg'] * 100);
                if ($kg < 1 || (int) $output['package_count'] < 1) {
                    $this->fail('outputs', 'Isi hasil timbang aktual dan jumlah kemasan lebih dari nol.');
                }
                $totalWeight += $kg;
            }
            if (! $totalWeight || count($outputs) > 50) {
                $this->fail('outputs', 'Isi 1–50 baris hasil timbang aktual.');
            }
            $packingCost = $run->warehouse_only ? 0 : AccountingMoney::cents($data['packing_cost'] ?? '0');
            $totalCost = (int) $run->material_cost_cents + (int) $run->processing_cost_cents + $packingCost;
            $allocated = 0;
            $allocatedWeight = 0;
            foreach ($outputs as $index => $output) {
                $area = WarehouseArea::lockForUpdate()->findOrFail($output['warehouse_area_id']);
                $inventory = Inventory::lockForUpdate()->findOrFail($area->inventory_id);
                if (! $area->is_active || $area->usage !== 'finished' || $inventory->stock_stage !== 'finished') {
                    $this->fail('outputs', 'Pilih area barang ready yang aktif dan sesuai gudang barang jadi.');
                }
                $kg = (int) round($output['weight_kg'] * 100);
                // Round cumulative shares so small outputs never inherit a negative remainder.
                $allocatedWeight += $kg;
                $cost = (int) round($totalCost * $allocatedWeight / $totalWeight) - $allocated;
                $allocated += $cost;
                $batch = FinishedBatch::create(['code' => 'FG-'.$run->id.'-'.($index + 1), 'production_run_id' => $run->id, 'inventory_id' => $inventory->id,
                    'warehouse_area_id' => $area->id, 'quality_grade_id' => $output['quality_grade_id'] ?? null, 'weight_kg' => $kg / 100, 'package_count' => $output['package_count'],
                    'remaining_weight_kg' => $kg / 100, 'remaining_packages' => $output['package_count'], 'cost_cents' => $cost, 'remaining_cost_cents' => $cost]);
                $inventory->increment('current_stock_kg', $kg / 100);
                $inventory->increment('current_sack_count', $output['package_count']);
                StockMovement::create(['production_run_id' => $run->id, 'finished_batch_id' => $batch->id, 'inventory_id' => $inventory->id, 'movement_date' => $data['packed_date'],
                    'direction' => 'in', 'type' => 'in', 'weight_kg' => $kg / 100, 'sack_count' => $output['package_count'], 'cost_cents' => $cost, 'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => 'Hasil timbang dan packing '.$run->number.' / '.$batch->code]);
            }
            $run->update(['status' => 'packed', 'packed_date' => $data['packed_date'], 'packing_cost_cents' => $packingCost]);
            app(OperationalAccountingService::class)->productionPack($run);

            return $run;
        });
    }

    public function allocateGrade(int $inventoryId, int $gradeId, float $weight, int $packages, string $date, bool $consume = true): array
    {
        $this->date($date);
        $batches = FinishedBatch::with('run')->where('inventory_id', $inventoryId)->where('quality_grade_id', $gradeId)
            ->where('remaining_weight_kg', '>', 0)->where('remaining_packages', '>', 0)
            ->whereHas('grade', fn ($q) => $q->where('is_active', true))
            ->whereHas('area', fn ($q) => $q->where('is_active', true)->where('usage', 'finished'))
            ->whereHas('run', fn ($q) => $q->where('status', 'packed')->whereDate('packed_date', '<=', $date))
            ->orderBy(ProductionRun::select('packed_date')->whereColumn('production_runs.id', 'finished_batches.production_run_id'))
            ->orderBy('id')->lockForUpdate()->get();
        $remaining = (int) round($weight * 100);
        $parts = [];
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            $available = (int) round($batch->remaining_weight_kg * 100);
            $taken = min($remaining, $available);
            $full = $taken === $available;
            if (! $full && (int) $batch->remaining_packages < 2) {
                $this->fail('sold_sacks', 'Sisa batch hanya satu kemasan. Ambil seluruh beratnya atau sesuaikan jumlah penjualan.');
            }
            $parts[] = ['batch' => $batch, 'weight_kg' => $taken / 100,
                'minimum' => $full ? (int) $batch->remaining_packages : 1,
                'maximum' => $full ? (int) $batch->remaining_packages : (int) $batch->remaining_packages - 1];
            $remaining -= $taken;
        }
        if ($remaining > 0 || ! $parts || $packages < array_sum(array_column($parts, 'minimum')) || $packages > array_sum(array_column($parts, 'maximum'))) {
            $this->fail('sold_sacks', 'Berat atau jumlah kemasan tidak sesuai stok grade dan urutan FIFO. Periksa berat dan kemasan yang tersedia.');
        }
        $extra = $packages - array_sum(array_column($parts, 'minimum'));
        $allocations = [];
        foreach ($parts as $part) {
            $addition = min($extra, $part['maximum'] - $part['minimum']);
            $count = $part['minimum'] + $addition;
            $extra -= $addition;
            $allocation = $consume
                ? $this->consumeFinished($part['batch']->id, $inventoryId, $part['weight_kg'], $count, $date)
                : ['finished_batch_id' => $part['batch']->id];
            $allocations[] = $allocation + ['weight_kg' => $part['weight_kg'], 'sack_count' => $count];
        }

        return $allocations;
    }

    public function consumeFinished(int $batchId, int $inventoryId, float $weight, int $packages, string $date): array
    {
        $batch = FinishedBatch::with(['run', 'area', 'grade'])->lockForUpdate()->findOrFail($batchId);
        if (! $batch->area->is_active || $batch->area->usage !== 'finished') {
            $this->fail('finished_batch_id', 'Area barang ready nonaktif atau tidak sesuai.');
        }
        $kg = (int) round($weight * 100);
        $remaining = (int) round($batch->remaining_weight_kg * 100);
        $this->date($date, $batch->run->packed_date->format('Y-m-d'));
        if ($batch->inventory_id !== $inventoryId || $kg <= 0 || $kg > $remaining || $packages < 1 || $packages > $batch->remaining_packages) {
            $this->fail('finished_batch_id', 'Stok berat atau kemasan batch jadi tidak mencukupi.');
        }
        if (($kg === $remaining) !== ($packages === (int) $batch->remaining_packages)) {
            $this->fail('sold_sacks', 'Pengeluaran terakhir harus menghabiskan berat dan kemasan batch bersamaan.');
        }
        $cost = $kg === $remaining ? (int) $batch->remaining_cost_cents : (int) round($batch->remaining_cost_cents * $kg / $remaining);
        $batch->update(['remaining_weight_kg' => ($remaining - $kg) / 100, 'remaining_packages' => $batch->remaining_packages - $packages, 'remaining_cost_cents' => $batch->remaining_cost_cents - $cost]);

        return ['finished_batch_id' => $batch->id, 'cost_cents' => $cost];
    }

    public function restoreFinished(StockMovement $movement): void
    {
        if (! $movement->finished_batch_id) {
            return;
        }
        $batch = FinishedBatch::lockForUpdate()->findOrFail($movement->finished_batch_id);
        $batch->increment('remaining_weight_kg', $movement->weight_kg);
        $batch->increment('remaining_packages', $movement->sack_count);
        $batch->increment('remaining_cost_cents', $movement->cost_cents);
    }

    private function date(string $date, ?string $minimum = null): void
    {
        $day = Carbon::parse($date)->startOfDay();
        if ($day->gt(now('Asia/Jakarta')->startOfDay()) || ($minimum && $day->lt(Carbon::parse($minimum)->startOfDay()))) {
            $this->fail('date', 'Tanggal harus sejak tahap sebelumnya dan tidak boleh di masa depan.');
        }
    }

    private function fail(string $field,string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
