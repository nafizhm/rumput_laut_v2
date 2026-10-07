<?php

namespace App\Services\Master;

use App\Models\Farmer;
use App\Models\GoodsReceipt;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FarmerService
{
    use HandlesTransaction;

    /**
     * Get all farmers ordered by name.
     */
    public function getAllFarmers(): EloquentCollection
    {
        return Farmer::orderBy('name', 'asc')->get();
    }

    /**
     * Get farmers for select options.
     */
    public function getFarmerOptions(): Collection
    {
        return Farmer::orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(fn ($farmer) => [
                'label' => $farmer->name,
                'value' => $farmer->id,
            ]);
    }

    /**
     * Create a new farmer.
     */
    public function createFarmer(array $data): Farmer
    {
        return $this->transaction(function () use ($data) {
            return Farmer::create($data);
        });
    }

    /**
     * Update an existing farmer.
     */
    public function updateFarmer(Farmer $farmer, array $data): bool
    {
        return $this->transaction(function () use ($farmer, $data) {
            return $farmer->update($data);
        });
    }

    /**
     * Delete a farmer.
     */
    public function deleteFarmer(Farmer $farmer): ?bool
    {
        return $this->transaction(function () use ($farmer) {
            $farmer = Farmer::lockForUpdate()->findOrFail($farmer->id);
            if ($farmer->loans()->exists() || $farmer->harvestTransactions()->exists() || $farmer->payments()->exists()
                || GoodsReceipt::where('farmer_id', $farmer->id)->exists()) {
                throw ValidationException::withMessages(['farmer' => 'Petani dengan transaksi tidak dapat dihapus. Riwayat dan relasi pembukuan harus dipertahankan.']);
            }

            return $farmer->delete();
        });
    }

    /**
     * Get farmer transaction mutation (ledgers).
     */
    public function getFarmerLedgers(int $farmerId): Collection
    {
        $farmer = Farmer::findOrFail($farmerId);

        return $farmer->ledgers()
            ->with('reference')
            ->orderBy('id', 'desc')
            ->get();
    }
}
