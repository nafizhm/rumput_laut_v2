<?php

namespace App\Services\Transaction;

use App\Models\DeliveryNote;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

class WarehouseHistoryService
{
    public function movements(array $filters): Collection
    {
        return StockMovement::with(['locations', 'inventory', 'deliveryNote', 'salesTransaction'])
            ->when($filters['inventory_id'] ?? null, fn ($q, $id) => $q->where('inventory_id', $id))
            ->when($filters['area_id'] ?? null, fn ($q, $id) => $q->whereHas('locations', fn ($location) => $location->where('warehouse_area_id', $id)))
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('movement_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('movement_date', '<=', $date))
            ->orderByDesc('movement_date')->orderByDesc('id')->get()
            ->flatMap(function ($movement) use ($filters) {
                $locations = $movement->locations->isEmpty() ? collect([null]) : $movement->locations;
                return $locations->filter(fn ($location) => empty($filters['area_id']) || $location?->warehouse_area_id == $filters['area_id'])
                    ->map(function ($location) use ($movement) {
                        $note = $movement->deliveryNote;
                        $type = $note ? 'Barang berangkat' : match ($movement->type) {
                            'transfer' => 'Transfer area', 'adjustment' => 'Penyesuaian', 'shrinkage' => 'Penyusutan',
                            default => $movement->production_run_id ? ($movement->direction === 'in' ? 'Hasil produksi' : 'Bahan ke proses') : ($movement->direction === 'in' ? 'Barang masuk' : 'Barang keluar'),
                        };
                        return [
                            'id' => $movement->id.'-'.($location?->id ?? 'legacy'), 'date' => $movement->movement_date->format('Y-m-d'),
                            'activity' => $type, 'status' => $note ? 'Sudah terpotong' : 'Tercatat', 'direction' => $movement->direction,
                            'inventory' => $movement->inventory?->item_name ?? 'Gudang tidak tersedia',
                            'item' => $movement->salesTransaction?->document_item_name ?? $location?->item_name ?? $movement->inventory?->item_name,
                            'batch' => $location?->batch_code, 'area' => $location?->area_name ?? 'Area belum tercatat',
                            'counterpart' => $location?->counterpart_area_name, 'inferred' => (bool) $location?->inferred,
                            'weight' => (float) ($location?->weight_kg ?? $movement->weight_kg),
                            'packages' => (int) ($location?->sack_count ?? $movement->sack_count),
                            'balance' => (float) $movement->balance_after_kg, 'description' => $movement->description,
                            'document' => $note?->number ?? $movement->salesTransaction?->sale_reference,
                            'url' => $note ? route('surat-jalan.print', $note) : null,
                        ];
                    });
            })->values();
    }

    public function drafts(array $filters): Collection
    {
        $notes = DeliveryNote::with(['items.finishedBatch.area.inventory', 'items.sale.finishedBatch.area.inventory', 'items.sale.inventory',
            'sale.finishedBatch.area.inventory', 'sale.inventory', 'finishedBatch.area.inventory'])
            ->where('status', 'draft')->whereNull('shipped_at')
            ->whereDoesntHave('stockMovements')
            ->when($filters['date_from'] ?? null, fn ($q, $date) => $q->whereDate('delivery_date', '>=', $date))
            ->when($filters['date_to'] ?? null, fn ($q, $date) => $q->whereDate('delivery_date', '<=', $date))
            ->orderByDesc('delivery_date')->orderByDesc('id')->get();

        return $notes->flatMap(function ($note) use ($filters) {
            $items = $note->items->isEmpty() ? collect([$note]) : $note->items;
            return $items->map(function ($item) use ($note) {
                $sale = $item->sale;
                $batch = $item->finishedBatch ?? $sale?->finishedBatch;
                return [
                    'id' => $note->id.'-'.$item->id, 'date' => $note->delivery_date->format('Y-m-d'),
                    'activity' => 'Rencana kirim', 'status' => 'Draft · Belum terpotong', 'direction' => null,
                    'inventory_id' => $batch?->inventory_id ?? $sale?->inventory_id,
                    'area_id' => $batch?->warehouse_area_id,
                    'inventory' => $batch?->area?->inventory?->item_name ?? $sale?->inventory?->item_name ?? 'Gudang belum ditentukan',
                    'item' => $item->item_name, 'batch' => $batch?->code,
                    'area' => $batch?->area?->name ?? 'Area belum ditentukan',
                    'weight' => (float) $item->quantity_kg, 'packages' => (int) $item->sack_count,
                    'document' => $note->number, 'customer' => $note->recipient_name,
                    'sale' => $sale?->sale_reference, 'url' => route('surat-jalan.edit', $note),
                ];
            })->filter(fn ($row) => (empty($filters['inventory_id']) || $row['inventory_id'] == $filters['inventory_id'])
                && (empty($filters['area_id']) || $row['area_id'] == $filters['area_id']));
        })->values();
    }
}
