<?php

namespace App\Services\Master;

use App\Models\Farmer;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

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
            ->map(fn($farmer) => [
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
