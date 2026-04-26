<?php

namespace App\Services\Transaction;

use App\Models\Farmer;
use App\Models\HarvestTransaction;
use App\Traits\HandlesTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class HarvestTransactionService
{
    use HandlesTransaction;

    /**
     * Get all harvest transactions with farmer relationship.
     */
    public function getAllHarvests(): Collection
    {
        return HarvestTransaction::with('farmer')->orderBy('harvest_date', 'desc')->get();
    }

    /**
     * Create a new harvest transaction and ledger entry.
     */
    public function createHarvest(array $data): HarvestTransaction
    {
        return $this->transaction(function () use ($data) {
            $farmer = Farmer::lockForUpdate()->findOrFail($data['farmer_id']);
            
            $data['batch_code'] = $this->generateBatchCode();
            
            // Recalculate server-side to be safe
            $data['total_harvest_value'] = $data['wet_weight_kg'] * $data['price_per_kg'];
            $data['debt_deduction'] = $data['total_harvest_value'] - $data['cash_payout'];
            
            $harvest = HarvestTransaction::create($data);
            
            if ($harvest->debt_deduction > 0) {
                $farmer->decrement('current_debt', $harvest->debt_deduction);
                
                $harvest->ledgers()->create([
                    'farmer_id' => $farmer->id,
                    'type' => 'credit',
                    'amount' => $harvest->debt_deduction,
                    'balance_after' => $farmer->current_debt,
                ]);
            }
            
            return $harvest;
        });
    }

    /**
     * Update a harvest transaction and adjust ledger if debt_deduction changes.
     */
    public function updateHarvest(HarvestTransaction $harvest, array $data): bool
    {
        return $this->transaction(function () use ($harvest, $data) {
            $farmer = Farmer::lockForUpdate()->findOrFail($harvest->farmer_id);
            
            $oldDeduction = $harvest->debt_deduction;
            
            // Recalculate server-side
            $data['total_harvest_value'] = $data['wet_weight_kg'] * $data['price_per_kg'];
            $data['debt_deduction'] = $data['total_harvest_value'] - $data['cash_payout'];
            
            $newDeduction = $data['debt_deduction'];
            $diff = $newDeduction - $oldDeduction; // Positive means more debt reduced (more credit)
            
            $harvest->update($data);
            
            if ($diff != 0) {
                // If diff > 0, we reduce debt more (credit)
                // If diff < 0, we increase debt back (debit)
                $farmer->decrement('current_debt', $diff);
                
                $harvest->ledgers()->create([
                    'farmer_id' => $farmer->id,
                    'type' => $diff > 0 ? 'credit' : 'debit',
                    'amount' => abs($diff),
                    'balance_after' => $farmer->current_debt,
                ]);
            }
            
            return true;
        });
    }

    /**
     * Delete a harvest transaction and create a reversal ledger.
     */
    public function deleteHarvest(HarvestTransaction $harvest): bool
    {
        return $this->transaction(function () use ($harvest) {
            $farmer = Farmer::lockForUpdate()->findOrFail($harvest->farmer_id);
            
            if ($harvest->debt_deduction > 0) {
                $farmer->increment('current_debt', $harvest->debt_deduction);
                
                $harvest->ledgers()->create([
                    'farmer_id' => $farmer->id,
                    'type' => 'debit',
                    'amount' => $harvest->debt_deduction,
                    'balance_after' => $farmer->current_debt,
                ]);
            }
            
            return $harvest->delete();
        });
    }

    /**
     * Generate automatic batch code: HRV-YYYYMMDD-XXXX
     */
    protected function generateBatchCode(): string
    {
        $today = Carbon::today();
        $dateStr = $today->format('Ymd');
        
        $count = HarvestTransaction::whereDate('created_at', $today)->count() + 1;
        $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
        
        $code = "HRV-{$dateStr}-{$sequence}";
        
        // Ensure uniqueness just in case
        while (HarvestTransaction::where('batch_code', $code)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $code = "HRV-{$dateStr}-{$sequence}";
        }
        
        return $code;
    }
}
