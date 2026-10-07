<?php

namespace App\Services\Transaction;

use App\Models\Inventory;
use App\Models\StockMovement;
use App\Traits\HandlesTransaction;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    use HandlesTransaction;

    /**
     * Get all stock movements.
     */
    public function getAllMovements(?int $inventoryId = null): Collection
    {
        return StockMovement::with(['inventory', 'harvestTransaction', 'salesTransaction'])
            ->when($inventoryId, fn ($query) => $query->where('inventory_id', $inventoryId))
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Receiving stock from harvest.
     */
    public function receive(array $data): StockMovement
    {
        throw ValidationException::withMessages(['receipt' => 'Gunakan menu Penerimaan Barang dan konfirmasi penerimaan untuk menambah stok.']);
    }

    private function assertLegacyInventory(Inventory $inventory): void
    {
        if (in_array($inventory->stock_stage, ['material', 'finished'])) {
            throw ValidationException::withMessages(['inventory' => 'Stok bahan harus dikelola melalui batch dan proses produksi, bukan perubahan saldo gudang manual.']);
        }
    }

    /**
     * Record shrinkage.
     */
    public function shrinkage(array $data): StockMovement
    {
        return $this->transaction(function () use ($data) {
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            $this->assertLegacyInventory($inventory);
            $lostWeight = $data['lost_weight_kg'];

            if ($inventory->current_stock_kg < $lostWeight) {
                throw new Exception('Stok tidak mencukupi untuk penyusutan ini.');
            }

            $inventory->current_stock_kg -= $lostWeight;
            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'movement_date' => $data['movement_date'] ?? now(),
                'direction' => 'out',
                'type' => 'shrinkage',
                'weight_kg' => $lostWeight,
                'sack_count' => 0,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => $data['description'] ?? 'Penyusutan stok',
            ]);
        });
    }

    /**
     * Transfer stock between inventories.
     */
    public function transfer(array $data): array
    {
        return $this->transaction(function () use ($data) {
            $fromInventory = Inventory::lockForUpdate()->findOrFail($data['from_inventory_id']);
            $toInventory = Inventory::lockForUpdate()->findOrFail($data['to_inventory_id']);
            $this->assertLegacyInventory($fromInventory);
            $this->assertLegacyInventory($toInventory);

            $weight = $data['weight_kg'];
            $sacks = (int) ($data['sack_count'] ?? 0);

            if ($fromInventory->current_stock_kg < $weight) {
                throw new Exception('Stok tidak mencukupi untuk transfer ini.');
            }

            if ($fromInventory->current_sack_count < $sacks) {
                throw new Exception('Jumlah karung tidak mencukupi untuk transfer ini.');
            }

            $fromInventory->decrement('current_stock_kg', $weight);
            $fromInventory->decrement('current_sack_count', $sacks);

            $toInventory->increment('current_stock_kg', $weight);
            $toInventory->increment('current_sack_count', $sacks);

            $outMovement = StockMovement::create([
                'inventory_id' => $fromInventory->id,
                'movement_date' => $data['transfer_date'],
                'direction' => 'out',
                'type' => 'transfer',
                'weight_kg' => $weight,
                'sack_count' => $sacks,
                'balance_after_kg' => $fromInventory->current_stock_kg,
                'description' => $data['description'] ?? "Transfer ke {$toInventory->item_name}",
            ]);

            $inMovement = StockMovement::create([
                'inventory_id' => $toInventory->id,
                'movement_date' => $data['transfer_date'],
                'direction' => 'in',
                'type' => 'transfer',
                'weight_kg' => $weight,
                'sack_count' => $sacks,
                'balance_after_kg' => $toInventory->current_stock_kg,
                'description' => $data['description'] ?? "Transfer dari {$fromInventory->item_name}",
            ]);

            return [$outMovement, $inMovement];
        });
    }

    /**
     * Record adjustment.
     */
    public function adjustment(array $data): StockMovement
    {
        return $this->transaction(function () use ($data) {
            $inventory = Inventory::lockForUpdate()->findOrFail($data['inventory_id']);
            $this->assertLegacyInventory($inventory);
            $adjWeight = $data['adjustment_weight_kg'];

            $newBalance = $inventory->current_stock_kg + $adjWeight;
            if ($newBalance < 0) {
                throw new Exception('Hasil penyesuaian tidak boleh menyebabkan stok negatif.');
            }

            $inventory->current_stock_kg = $newBalance;
            $inventory->save();

            return StockMovement::create([
                'inventory_id' => $inventory->id,
                'movement_date' => $data['movement_date'] ?? now(),
                'direction' => $adjWeight >= 0 ? 'in' : 'out',
                'type' => 'adjustment',
                'weight_kg' => abs($adjWeight),
                'sack_count' => 0,
                'balance_after_kg' => $inventory->current_stock_kg,
                'description' => $data['description'],
            ]);
        });
    }
}
