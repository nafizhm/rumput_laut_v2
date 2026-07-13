<?php

namespace App\Services\Transaction;

use App\Models\Income;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;

class IncomeService
{
    use HandlesTransaction;

    public function getAllIncomes(): Collection
    {
        return Income::orderBy('income_date', 'desc')->get();
    }

    public function createIncome(array $data): Income
    {
        return $this->transaction(function () use ($data) {
            return Income::create($data);
        });
    }

    public function updateIncome(int $id, array $data): Income
    {
        return $this->transaction(function () use ($id, $data) {
            $income = Income::findOrFail($id);
            $income->update($data);
            return $income;
        });
    }

    public function deleteIncome(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $income = Income::findOrFail($id);
            return $income->delete();
        });
    }
}
