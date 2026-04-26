<?php

namespace App\Services\Master;

use App\Models\Farmer;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;

class FarmerService
{
    use HandlesTransaction;

    /**
     * Get all farmers ordered by name.
     */
    public function getAllFarmers(): Collection
    {
        return Farmer::orderBy('name', 'asc')->get();
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
}
