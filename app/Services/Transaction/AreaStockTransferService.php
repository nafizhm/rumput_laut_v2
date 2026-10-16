<?php

namespace App\Services\Transaction;

use App\Models\FinishedBatch;
use App\Models\GoodsReceipt;
use App\Models\Inventory;
use App\Models\MaterialBatch;
use App\Models\StockMovement;
use App\Models\WarehouseArea;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AreaStockTransferService
{
    public function options(): array
    {
        return ['areas' => WarehouseArea::where('is_active', true)->whereIn('usage', ['receiving', 'finished'])->orderBy('id')->get()->map(function ($area) {
            $batches = $area->usage === 'receiving'
                ? MaterialBatch::whereHas('line', fn ($query) => $query->where('warehouse_area_id', $area->id)->whereHas('receipt', fn ($receipt) => $receipt->where('status', 'confirmed')))->get()
                : FinishedBatch::where('warehouse_area_id', $area->id)->get();
            return ['id' => $area->id, 'usage' => $area->usage, 'label' => $area->name,
                'weight' => (float) $batches->sum('remaining_weight_kg'), 'count' => (int) $batches->sum($area->usage === 'receiving' ? 'remaining_sacks' : 'remaining_packages')];
        }), 'legacy' => Inventory::where('stock_stage', 'legacy')->get()->map(fn ($inventory) => ['id' => $inventory->id,
            'label' => 'Area Lama / '.$inventory->item_name, 'weight' => (float) $inventory->current_stock_kg, 'count' => (int) $inventory->current_sack_count])];
    }

    public function transfer(array $data): void
    {
        DB::transaction(function () use ($data) {
            $target = WarehouseArea::lockForUpdate()->findOrFail($data['to_area_id']);
            if (!$target->is_active || $target->usage !== $data['usage']) $this->fail('Area tujuan harus sesuai jenis gudang.');
            $destination = Inventory::lockForUpdate()->findOrFail($target->inventory_id);
            if ($destination->stock_stage !== ($data['usage'] === 'receiving' ? 'material' : 'finished')) $this->fail('Jenis stok area tujuan tidak sesuai.');
            $weight = (int) round($data['weight_kg'] * 100);
            $count = (int) $data['sack_count'];
            if ($data['source_kind'] === 'legacy') {
                $source = Inventory::lockForUpdate()->findOrFail($data['from_id']);
                if ($source->stock_stage !== 'legacy') $this->fail('Asal stok lama tidak valid.');
                $label = 'Area Lama / '.$source->item_name;
                if ($source->current_stock_kg * 100 < $weight || $source->current_sack_count < $count) $this->fail('Stok asal tidak mencukupi.');
                $code = 'TRF-'.Str::uuid();
                $gradeId = $target->grades()->value('quality_grades.id') ?? \App\Models\QualityGrade::value('id');
                if ($data['usage'] === 'receiving') {
                    $receipt = new GoodsReceipt();
                    $receipt->forceFill(['number' => $code, 'received_date' => $data['transfer_date'], 'inventory_id' => $destination->id,
                        'status' => 'confirmed', 'confirmed_at' => now(), 'confirmed_by' => auth()->id(), 'confirmed_by_name' => auth()->user()->name,
                        'notes' => 'Transfer '.$label.'; '.$data['description']])->save();
                    $line = $receipt->lines()->create(['quality_grade_id' => $gradeId, 'warehouse_area_id' => $target->id, 'actual_weight_kg' => $weight / 100, 'sack_count' => $count]);
                    MaterialBatch::create(['code' => $code, 'goods_receipt_line_id' => $line->id, 'incoming_weight_kg' => $weight / 100,
                        'incoming_sacks' => $count, 'remaining_weight_kg' => $weight / 100, 'remaining_sacks' => $count, 'status' => 'available']);
                } else {
                    FinishedBatch::create(['code' => $code, 'production_run_id' => null, 'inventory_id' => $destination->id,
                        'warehouse_area_id' => $target->id, 'quality_grade_id' => $gradeId, 'weight_kg' => $weight / 100,
                        'package_count' => $count, 'remaining_weight_kg' => $weight / 100, 'remaining_packages' => $count, 'cost_cents' => 0, 'remaining_cost_cents' => 0]);
                }
            } else {
                $origin = WarehouseArea::lockForUpdate()->findOrFail($data['from_id']);
                if (!$origin->is_active || $origin->usage !== $target->usage || $origin->id === $target->id) $this->fail('Transfer hanya antar-area berbeda dalam jenis gudang yang sama.');
                $source = Inventory::lockForUpdate()->findOrFail($origin->inventory_id);
                $label = $origin->name;
                $batches = $data['usage'] === 'receiving'
                    ? MaterialBatch::whereHas('line', fn ($query) => $query->where('warehouse_area_id', $origin->id)->whereHas('receipt', fn ($receipt) => $receipt->where('status', 'confirmed')))->where('status', 'available')->orderBy('id')->lockForUpdate()->get()
                    : FinishedBatch::where('warehouse_area_id', $origin->id)->orderBy('id')->lockForUpdate()->get();
                $countField = $data['usage'] === 'receiving' ? 'remaining_sacks' : 'remaining_packages';
                $batches = $batches->filter(fn ($batch) => $batch->remaining_weight_kg > 0 && $batch->$countField > 0)->values();
                $totalWeight = (int) round($batches->sum('remaining_weight_kg') * 100);
                $totalCount = (int) $batches->sum($countField);
                if ($weight > $totalWeight || $count > $totalCount) $this->fail('Stok area asal tidak mencukupi.');
                $weights = $this->allocate($weight, $batches->map(fn ($batch) => (int) round($batch->remaining_weight_kg * 100))->all());
                $counts = $this->allocate($count, $batches->pluck($countField)->all());
                foreach ($batches as $index => $batch) {
                    $takenWeight = $weights[$index];
                    $takenCount = $counts[$index];
                    if (!$takenWeight && !$takenCount) continue;
                    $remainingWeight = (int) round($batch->remaining_weight_kg * 100) - $takenWeight;
                    $remainingCount = (int) $batch->$countField - $takenCount;
                    if (($takenWeight > 0) !== ($takenCount > 0) || ($remainingWeight > 0) !== ($remainingCount > 0)) $this->fail('Kilo dan kemasan tidak seimbang untuk batch asal. Sesuaikan jumlah transfer.');
                    $cost = $remainingWeight === 0 ? (int) $batch->remaining_cost_cents : (int) round($batch->remaining_cost_cents * $takenWeight / ($batch->remaining_weight_kg * 100));
                    $copy = $batch->replicate();
                    $copy->code = 'TRF-'.Str::uuid();
                    $copy->remaining_weight_kg = $takenWeight / 100;
                    $copy->$countField = $takenCount;
                    $copy->remaining_cost_cents = $cost;
                    if ($data['usage'] === 'receiving') {
                        $line = $batch->line->replicate();
                        $line->warehouse_area_id = $target->id;
                        $line->actual_weight_kg = $takenWeight / 100;
                        $line->sack_count = $takenCount;
                        $line->save();
                        $copy->goods_receipt_line_id = $line->id;
                        $copy->incoming_weight_kg = $takenWeight / 100;
                        $copy->incoming_sacks = $takenCount;
                        $copy->status = 'available';
                    } else {
                        $copy->warehouse_area_id = $target->id;
                        $copy->inventory_id = $destination->id;
                        $copy->weight_kg = $takenWeight / 100;
                        $copy->package_count = $takenCount;
                        $copy->cost_cents = $cost;
                    }
                    $copy->save();
                    $changes = ['remaining_weight_kg' => $remainingWeight / 100, $countField => $remainingCount, 'remaining_cost_cents' => $batch->remaining_cost_cents - $cost];
                    if ($data['usage'] === 'receiving') $changes['status'] = $remainingCount ? 'available' : 'consumed';
                    $batch->update($changes);
                }
            }
            if ($source->current_stock_kg * 100 < $weight || $source->current_sack_count < $count) $this->fail('Saldo gudang asal tidak mencukupi.');
            $source->decrement('current_stock_kg', $weight / 100);
            $source->decrement('current_sack_count', $count);
            $destination->refresh();
            $destination->increment('current_stock_kg', $weight / 100);
            $destination->increment('current_sack_count', $count);
            foreach ([['inventory' => $source->fresh(), 'direction' => 'out'], ['inventory' => $destination->fresh(), 'direction' => 'in']] as $entry) {
                $movement = StockMovement::create(['inventory_id' => $entry['inventory']->id, 'movement_date' => $data['transfer_date'],
                    'direction' => $entry['direction'], 'type' => 'transfer', 'weight_kg' => $weight / 100, 'sack_count' => $count,
                    'balance_after_kg' => $entry['inventory']->current_stock_kg,
                    'description' => 'Transfer '.$label.' ke '.$target->name.'. '.$data['description'].' / '.auth()->user()->name]);
                $area = $entry['direction'] === 'in' ? $target : ($origin ?? null);
                $counterpart = $entry['direction'] === 'in' ? $label : $target->name;
                app(MovementLocationService::class)->record($movement, $area, $weight / 100, $count, null, null, $counterpart);
            }
        });
    }

    private function allocate(int $total, array $weights): array
    {
        $sum = array_sum($weights);
        if (!$sum) $this->fail('Area asal tidak memiliki stok.');
        $exact = array_map(fn ($weight) => $total * $weight / $sum, $weights);
        $values = array_map(fn ($value) => (int) floor($value), $exact);
        $remaining = $total - array_sum($values);
        $indexes = array_keys($weights);
        usort($indexes, fn ($first, $second) => ($exact[$second] - $values[$second]) <=> ($exact[$first] - $values[$first]));
        foreach ($indexes as $index) {
            if (!$remaining) break;
            $values[$index]++;
            $remaining--;
        }
        return $values;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['transfer' => $message]);
    }
}
