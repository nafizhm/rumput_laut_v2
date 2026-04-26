<?php

namespace App\Services\Transaction;

use App\Models\Farmer;
use App\Models\Loan;
use App\Traits\HandlesTransaction;
use Illuminate\Database\Eloquent\Collection;

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
            $farmer = Farmer::lockForUpdate()->findOrFail($data['farmer_id']);
            
            $loan = Loan::create($data);
            
            $farmer->increment('current_debt', $data['amount']);
            
            $loan->ledgers()->create([
                'farmer_id' => $farmer->id,
                'type' => 'debit',
                'amount' => $data['amount'],
                'balance_after' => $farmer->current_debt,
            ]);
            
            return $loan;
        });
    }

    /**
     * Update a loan and adjust ledger if amount changes.
     */
    public function updateLoan(Loan $loan, array $data): bool
    {
        return $this->transaction(function () use ($loan, $data) {
            $farmer = Farmer::lockForUpdate()->findOrFail($loan->farmer_id);
            
            $oldAmount = $loan->amount;
            $newAmount = $data['amount'];
            $diff = $newAmount - $oldAmount;
            
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
            
            return true;
        });
    }

    /**
     * Delete a loan and create a reversal ledger.
     */
    public function deleteLoan(Loan $loan): bool
    {
        return $this->transaction(function () use ($loan) {
            $farmer = Farmer::lockForUpdate()->findOrFail($loan->farmer_id);
            
            $farmer->decrement('current_debt', $loan->amount);
            
            $loan->ledgers()->create([
                'farmer_id' => $farmer->id,
                'type' => 'credit',
                'amount' => $loan->amount,
                'balance_after' => $farmer->current_debt,
            ]);
            
            return $loan->delete();
        });
    }
}
