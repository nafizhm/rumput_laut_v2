<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Services\Finance\OperationalAccountingService;
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
            $transaction = Transaction::create($data);
            app(OperationalAccountingService::class)->transaction($transaction);

            return $transaction;
        });
    }

    public function update(int $id, array $data): Transaction
    {
        return $this->transaction(function () use ($id, $data) {
            $transaction = Transaction::lockForUpdate()->findOrFail($id);
            $transaction->update($data);
            app(OperationalAccountingService::class)->transaction($transaction);

            return $transaction;
        });
    }

    public function delete(int $id): bool
    {
        return $this->transaction(function () use ($id) {
            $transaction = Transaction::lockForUpdate()->findOrFail($id);
            app(OperationalAccountingService::class)->cancel($transaction, 'cash-transaction');

            return $transaction->delete();
        });
    }
}
