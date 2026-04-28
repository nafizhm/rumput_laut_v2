<?php

namespace App\Services\Transaction;

use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Traits\HandlesTransaction;
use Exception;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    use HandlesTransaction;

    /**
     * Get all stock movements.
     */
    public function getAllMovements()
    {
        return StockMovement::with(['inventory', 'harvestTransaction', 'salesTransaction'])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Receiving stock from harvest.
     */
    public function receive(array $data): StockMovement
    {
        return $this->transaction(function () use ($data) {
            $movementDate = $data['movement_date'];
            
            // Get all pending harvests for this date
            $harvests = HarvestTransaction::whereDate('harvest_date', $movementDate)
                ->where('is_received', false)
                ->get();

            if ($harvests->isEmpty()) {
                throw new Exception('Tidak ada transaksi panen yang belum diterima pada tanggal ini.');
            }

            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);

            $actualWeight = $data['actual_weight_kg'];
            $actualSacks = $data['actual_sacks'];
            $noteWeight = $data['note_weight_kg'];
            $discrepancy = $noteWeight - $actualWeight;

            $inventory->current_stock_kg += $actualWeight;
            $inventory->current_sack_count += $actualSacks;
            $inventory->save();

            // Mark all transactions as received
            HarvestTransaction::whereDate('harvest_date', $movementDate)
                ->where('is_received', false)
                ->update(['is_received' => true]);

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'movement_date' => $movementDate,
                'direction' => 'in',
                'type' => 'in',
                'weight_kg' => $actualWeight,
                'note_weight_kg' => $noteWeight,
                'discrepancy_kg' => $discrepancy,
                'sack_count' => $actualSacks,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => 'Penerimaan kolektif tanggal ' . $movementDate
            ]);
        });
    }

    /**
     * Record shrinkage.
     */
    public function shrinkage(array $data): StockMovement
    {
        return $this->transaction(function () use ($data) {
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            $lostWeight = $data['lost_weight_kg'];

            if ($inventory->current_stock_kg < $lostWeight) {
                throw new Exception('Stok tidak mencukupi untuk penyusutan ini.');
            }

            $inventory->current_stock_kg -= $lostWeight;
            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'movement_date' => now(),
                'direction' => 'out',
                'type' => 'shrinkage',
                'weight_kg' => $lostWeight,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => $data['description'] ?? 'Penyusutan stok'
            ]);
        });
    }

    /**
     * Record adjustment.
     */
    public function adjustment(array $data): StockMovement
    {
        return $this->transaction(function () use ($data) {
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            $adjWeight = $data['adjustment_weight_kg'];

            $newBalance = $inventory->current_stock_kg + $adjWeight;
            if ($newBalance < 0) {
                throw new Exception('Hasil penyesuaian tidak boleh menyebabkan stok negatif.');
            }

            $inventory->current_stock_kg = $newBalance;
            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'movement_date' => now(),
                'direction' => $adjWeight >= 0 ? 'in' : 'out',
                'type' => 'adjustment',
                'weight_kg' => abs($adjWeight),
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => $data['description']
            ]);
        });
    }
}
