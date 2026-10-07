<?php

namespace App\Services\Transaction;

use App\Models\Farmer;
use App\Models\Loan;
use App\Services\Finance\OperationalAccountingService;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class LoanService
{
    use HandlesTransaction;

    /**
     * Get all loans with farmer relationship.
     */
    public function getAllLoans(): Collection
    {
        return Loan::with('farmer')->orderBy('loan_date', 'desc')->get();
    }

    /**
     * Create a new loan and ledger entry.
     */
    public function createLoan(array $data): Loan
    {
        return $this->transaction(function () use ($data) {
            $data['amount'] = (int) round($data['amount']);
            $farmer = Farmer::lockForUpdate()->findOrFail($data['farmer_id']);

            $loan = Loan::create($data);

            $farmer->increment('current_debt', $data['amount']);

            $loan->ledgers()->create([
                'farmer_id' => $farmer->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'balance_after' => $farmer->current_debt,
            ]);

            app(OperationalAccountingService::class)->loan($loan);

            return $loan;
        });
    }

    /**
     * Update a loan and adjust ledger if amount changes.
     */
    public function updateLoan(Loan $loan, array $data): bool
    {
        return $this->transaction(function () use ($loan, $data) {
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);
            if ((int) $data['farmer_id'] !== (int) $loan->farmer_id) {
                throw ValidationException::withMessages(['farmer_id' => 'Petani pada pinjaman tidak dapat diganti. Batalkan transaksi yang belum dilunasi lalu buat pinjaman baru.']);
            }
            $farmer = Farmer::lockForUpdate()->findOrFail($loan->farmer_id);

            $oldAmount = $loan->amount;
            $newAmount = $data['amount'] = (int) round($data['amount']);
            $diff = $newAmount - $oldAmount;
            if ($farmer->current_debt + $diff < 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal tidak dapat dikurangi karena pinjaman sudah dibayar atau dipotong dari panen.']);
            }

            $loan->update($data);

            if ($diff != 0) {
                $farmer->increment('current_debt', $diff);

                $loan->ledgers()->create([
                    'farmer_id' => $farmer->id,
                    'type' => $diff > 0 ? 'debit' : 'credit',
                    'amount' => abs($diff),
                    'balance_after' => $farmer->current_debt,
                ]);
            }

            app(OperationalAccountingService::class)->loan($loan);

            return true;
        });
    }

    /**
     * Delete a loan and create a reversal ledger.
     */
    public function deleteLoan(Loan $loan): bool
    {
        return $this->transaction(function () use ($loan) {
            $loan = Loan::lockForUpdate()->findOrFail($loan->id);
            $farmer = Farmer::lockForUpdate()->findOrFail($loan->farmer_id);
            if ($farmer->current_debt < $loan->amount) {
                throw ValidationException::withMessages(['loan' => 'Pinjaman sudah dibayar atau dipotong dari panen dan tidak dapat dihapus.']);
            }

            $farmer->decrement('current_debt', $loan->amount);

            $loan->ledgers()->create([
                'farmer_id' => $farmer->id,
                'type' => 'credit',
                'amount' => $loan->amount,
                'balance_after' => $farmer->current_debt,
            ]);

            app(OperationalAccountingService::class)->cancel($loan, 'loan');
            $loan->ledgers()->delete();

            return $loan->delete();
        });
    }
}
