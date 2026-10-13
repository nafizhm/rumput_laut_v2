<?php

namespace App\Services\Transaction;

use App\Models\Transaction;
use App\Models\TransactionHistory;
use App\Services\Finance\OperationalAccountingService;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    use HandlesTransaction;

    public function getAll(): Collection
    {
        return Transaction::with(['category', 'account', 'cashAccount'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Transaction
    {
        return $this->transaction(function () use ($data) {
            if (empty($data['chart_of_account_id']) || empty($data['cash_account_id'])) {
                throw ValidationException::withMessages(['chart_of_account_id' => 'Pilih akun COA transaksi dan akun kas/bank.']);
            }
            $transaction = Transaction::create($data);
            app(OperationalAccountingService::class)->transaction($transaction);
            $this->record($transaction, 'created', null, $this->snapshot($transaction), null);

            return $transaction;
        });
    }

    public function update(int $id, array $data): Transaction
    {
        return $this->transaction(function () use ($id, $data) {
            $transaction = Transaction::lockForUpdate()->findOrFail($id);
            if (! $transaction->chart_of_account_id || ! $transaction->cash_account_id) {
                throw ValidationException::withMessages(['chart_of_account_id' => 'Transaksi lama belum dipetakan ke COA. Pengubahan data lama akan ditangani pada tahap berikutnya.']);
            }
            $reason = $data['change_reason'] ?? null;
            unset($data['change_reason']);
            $before = $this->snapshot($transaction);
            $transaction->fill($data);
            if (! $transaction->isDirty()) {
                return $transaction;
            }
            $this->requireReason($reason);
            $transaction->save();
            app(OperationalAccountingService::class)->transaction($transaction);
            $this->record($transaction, 'updated', $before, $this->snapshot($transaction), $reason);

            return $transaction;
        });
    }

    public function delete(int $id, ?string $reason = null): bool
    {
        return $this->transaction(function () use ($id, $reason) {
            $transaction = Transaction::lockForUpdate()->findOrFail($id);
            $this->requireReason($reason);
            app(OperationalAccountingService::class)->cancel($transaction, 'cash-transaction');
            $this->record($transaction, 'deleted', $this->snapshot($transaction), null, $reason);

            return $transaction->delete();
        });
    }

    private function requireReason(?string $reason): void
    {
        if (! trim($reason ?? '') || mb_strlen($reason) > 2000) {
            throw ValidationException::withMessages(['change_reason' => 'Isi alasan perubahan atau pembatalan (maksimal 2000 karakter).']);
        }
    }

    private function snapshot(Transaction $transaction): array
    {
        return $transaction->only($transaction->getFillable()) + [
            'account_name' => $transaction->account()->value('name'),
            'cash_account_name' => $transaction->cashAccount()->value('name'),
        ];
    }

    private function record(Transaction $transaction, string $action, ?array $before, ?array $after, ?string $reason): void
    {
        TransactionHistory::create([
            'transaction_id' => $transaction->id, 'action' => $action,
            'actor_id' => auth()->id(), 'actor_name' => auth()->user()?->name ?? 'Sistem',
            'reason' => $reason, 'before' => $before, 'after' => $after,
            'created_at' => now(),
        ]);
    }
}
