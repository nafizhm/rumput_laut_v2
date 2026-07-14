<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;

class TransactionService
{
    use HandlesTransaction;

    public function getAll(): Collection
    {
        return Transaction::with('category')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Transaction
    {
        return $this->transaction(function () use ($data) {
            return Transaction::create($data);
        });
    }

    public function update(int $id, array $data): Transaction
    {
        return $this->transaction(function () use ($id, $data) {
            $transaction = Transaction::findOrFail($id);
            $transaction->update($data);
            return $transaction;
        });
    }

    public function delete(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $transaction = Transaction::findOrFail($id);
            return $transaction->delete();
        });
    }
}
