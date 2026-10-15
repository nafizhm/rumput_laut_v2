<?php

namespace App\Services\Transaction;

use App\Models\FinishedBatch;
use App\Models\GoodsReceiptLine;
use App\Models\StockMovement;
use App\Models\WarehouseArea;
use Illuminate\Support\Facades\Schema;

class MovementLocationService
{
    public function capture(StockMovement $movement, bool $inferred = false): void
    {
        if (!Schema::hasTable('stock_movement_locations') || $movement->locations()->exists()) return;

        if ($movement->finished_batch_id && ($batch = FinishedBatch::with(['area', 'grade', 'inventory'])->find($movement->finished_batch_id))) {
            $this->record($movement, $batch->area, $movement->weight_kg, $movement->sack_count,
                $batch->code, $batch->grade?->name ?? $batch->inventory?->item_name, null, $inferred);
        } elseif ($movement->goods_receipt_id && $movement->direction === 'in' && $movement->type !== 'transfer') {
            $lines = GoodsReceiptLine::with(['area', 'grade', 'batch'])->where('goods_receipt_id', $movement->goods_receipt_id)->get();
            if (abs($lines->sum('actual_weight_kg') - $movement->weight_kg) > 0.005 || (int) $lines->sum('sack_count') !== (int) $movement->sack_count) return;
            foreach ($lines as $line) {
                $this->record($movement, $line->area, $line->actual_weight_kg, $line->sack_count,
                    $line->batch?->code, $line->grade?->name, null, $inferred);
            }
        }
    }

    public function record(StockMovement $movement, ?WarehouseArea $area, float $weight, int $count,
        ?string $batch = null, ?string $item = null, ?string $counterpart = null, bool $inferred = false): void
    {
        if (!$area || !Schema::hasTable('stock_movement_locations')) return;
        $movement->locations()->create([
            'warehouse_area_id' => $area->id, 'area_name' => $area->name,
            'counterpart_area_name' => $counterpart, 'batch_code' => $batch, 'item_name' => $item,
            'weight_kg' => $weight, 'sack_count' => $count, 'inferred' => $inferred,
        ]);
    }
}
