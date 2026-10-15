<?php

namespace App\Services\Transaction;

use App\Models\FinishedBatch;
use App\Models\Inventory;
use App\Models\MaterialBatch;
use App\Models\StockMovement;
use App\Models\WarehouseArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AreaStockAdjustmentService
{
    public function areas(): array
    {
        $result = [];
        foreach (['receiving', 'finished'] as $usage) {
            foreach (WarehouseArea::where('usage', $usage)->where('is_active', true)->orderBy('id')->get() as $area) {
                $label = $area->name;
                $batches = $usage === 'receiving'
                    ? MaterialBatch::with('line.grade')->whereHas('line', fn ($query) => $query->where('warehouse_area_id', $area->id)->whereHas('receipt', fn ($receipt) => $receipt->where('status', 'confirmed')))->get()
                    : FinishedBatch::with('grade')->where('warehouse_area_id', $area->id)->get();
                $result[] = ['key' => 'area-'.$area->id, 'label' => $label, 'name' => $area->name, 'stage' => $usage === 'receiving' ? 'Barang Masuk' : 'Barang Ready',
                    'items' => $batches->map(fn ($batch) => $this->row($usage === 'receiving' ? 'material' : 'finished', $batch, $label))->values()->all()];
            }
        }
        $legacy = Inventory::where('stock_stage', 'legacy')->get();
        $result[] = ['key' => 'legacy', 'label' => 'Area Lama', 'name' => 'Stok gudang lama', 'stage' => 'Stok Lama',
            'items' => $legacy->map(fn ($inventory) => $this->row('legacy', $inventory, 'Area Lama'))->values()->all()];
        return $result;
    }

    private function row(string $kind, $stock, string $area): array
    {
        $weight = (float) ($kind === 'legacy' ? $stock->current_stock_kg : $stock->remaining_weight_kg);
        $count = (int) ($kind === 'legacy' ? $stock->current_sack_count : ($kind === 'material' ? $stock->remaining_sacks : $stock->remaining_packages));
        return ['kind' => $kind, 'id' => $stock->id, 'area' => $area,
            'name' => $kind === 'legacy' ? $stock->item_name : ($kind === 'material'
                ? ($stock->line?->grade?->name ?? 'Barang masuk tanpa grade')
                : ($stock->grade?->name ?? $stock->inventory?->item_name ?? 'Barang ready tanpa grade')),
            'code' => $kind === 'legacy' ? 'Stok lama' : $stock->code,
            'expected_weight' => $weight, 'expected_count' => $count, 'actual_weight' => $weight, 'actual_count' => $count];
    }

    public function save(array $data): void
    {
        DB::transaction(function () use ($data) {
            $available = collect($this->areas())->flatMap(fn ($area) => $area['items'])->keyBy(fn ($row) => $row['kind'].':'.$row['id']);
            $keys = collect($data['items'])->map(fn ($row) => $row['kind'].':'.$row['id']);
            if ($keys->unique()->count() !== $keys->count()) {
                throw ValidationException::withMessages(['items' => 'Barang yang sama tidak boleh dicatat dua kali.']);
            }
            foreach ($data['items'] as $position => $row) {
                $reference = $available->get($row['kind'].':'.$row['id']);
                if (!$reference) {
                    throw ValidationException::withMessages(['items' => 'Barang tidak tersedia untuk penyesuaian. Muat ulang form.']);
                }
                $model = ['material' => MaterialBatch::class, 'finished' => FinishedBatch::class, 'legacy' => Inventory::class][$row['kind']];
                $stock = $model::lockForUpdate()->findOrFail($row['id']);
                $inventoryId = $row['kind'] === 'material' ? $stock->line->area->inventory_id : ($row['kind'] === 'legacy' ? $stock->id : $stock->inventory_id);
                $inventory = Inventory::lockForUpdate()->findOrFail($inventoryId);
                $weightField = $row['kind'] === 'legacy' ? 'current_stock_kg' : 'remaining_weight_kg';
                $countField = ['material' => 'remaining_sacks', 'finished' => 'remaining_packages', 'legacy' => 'current_sack_count'][$row['kind']];
                $beforeWeight = (float) $stock->$weightField;
                $beforeCount = (int) $stock->$countField;
                if ((int) round($beforeWeight * 100) !== (int) round($row['expected_weight'] * 100) || $beforeCount !== (int) $row['expected_count']) {
                    throw ValidationException::withMessages(['items' => 'Stok berubah sejak form dibuka. Muat ulang agar tidak menimpa transaksi terbaru.']);
                }
                $afterWeight = (float) $row['actual_weight'];
                $afterCount = (int) $row['actual_count'];
                if (($afterWeight > 0) !== ($afterCount > 0)) {
                    throw ValidationException::withMessages(['items.'.$position.'.actual_count' => 'Berat dan jumlah kemasan harus sama-sama nol atau sama-sama lebih dari nol.']);
                }
                $difference = round($afterWeight - $beforeWeight, 2);
                $countDifference = $afterCount - $beforeCount;
                if ((float) $inventory->current_stock_kg + $difference < 0 || (int) $inventory->current_sack_count + $countDifference < 0) {
                    throw ValidationException::withMessages(['items' => 'Saldo gudang tidak konsisten dengan batch. Penyesuaian dibatalkan.']);
                }
                if ($row['kind'] !== 'legacy') {
                    $changes = [$weightField => $afterWeight, $countField => $afterCount];
                    if ($row['kind'] === 'material') $changes['status'] = $afterCount > 0 ? 'available' : 'consumed';
                    $stock->update($changes);
                }
                $inventory->update(['current_stock_kg' => round((float) $inventory->current_stock_kg + $difference, 2),
                    'current_sack_count' => (int) $inventory->current_sack_count + $countDifference]);
                $movement = [
                    'inventory_id' => $inventory->id, 
                    'movement_date' => $data['movement_date'], 'type' => 'adjustment', 'direction' => $difference >= 0 ? 'in' : 'out',
                    'weight_kg' => abs($difference), 'sack_count' => abs($countDifference), 'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => 'Stock opname '.$reference['area'].' / '.$reference['name'].' / '.$reference['code'].
                        ': '.$beforeWeight.' ? '.$afterWeight.' Kg; '.$beforeCount.' ? '.$afterCount.' kemasan. '.$data['description'].' ? '.auth()->user()->name,
                ];
                if ($row['kind'] === 'finished') $movement['finished_batch_id'] = $stock->id;
                $savedMovement = StockMovement::create($movement);
                if ($row['kind'] === 'material') {
                    app(MovementLocationService::class)->record($savedMovement, $stock->line->area,
                        abs($difference), abs($countDifference), $stock->code, $stock->line->grade?->name);
                }
            }
        });
    }
}
