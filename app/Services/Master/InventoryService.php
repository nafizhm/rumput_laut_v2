<?php

namespace App\Services\Master;

use App\Models\Inventory;
use Illuminate\Database\Eloquent\Collection;

class InventoryService
{
    public function getAllInventories(): Collection
    {
        return Inventory::orderBy('item_name')->get();
    }

    public function store(array $data): Inventory
    {
        return Inventory::create([
            'item_name' => $data['item_name'],
            'current_stock_kg' => 0,
            'current_sack_count' => 0
        ]);
    }

    public function update($id, array $data): bool
    {
        $inventory = Inventory::findOrFail($id);
        return $inventory->update($data);
    }

    public function delete($id): bool
    {
        $inventory = Inventory::findOrFail($id);
        // Prevent deletion if there are movements?
        if ($inventory->stockMovements()->exists()) {
            throw new \Exception('Barang ini tidak bisa dihapus karena sudah memiliki riwayat transaksi.');
        }
        if (\App\Models\WarehouseArea::where('inventory_id', $inventory->id)->exists()) {
            throw new \Exception('Gudang ini masih digunakan oleh area penyimpanan.');
        }
        return $inventory->delete();
    }
}
