<?php

namespace App\Services\Master;

use App\Models\Factory;
use Illuminate\Database\Eloquent\Collection;

class FactoryService
{
    public function getAllFactories(): Collection
    {
        return Factory::orderBy('name')->get();
    }

    public function store(array $data): Factory
    {
        return Factory::create($data);
    }

    public function update($id, array $data): bool
    {
        $factory = Factory::findOrFail($id);
        return $factory->update($data);
    }

    public function delete($id): bool
    {
        $factory = Factory::findOrFail($id);
        return $factory->delete();
    }
}
