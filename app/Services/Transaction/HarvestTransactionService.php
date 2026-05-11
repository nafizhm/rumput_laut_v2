<?php

namespace App\Services\Transaction;

use App\Models\Farmer;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\StockMovement;
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

            $data = $this->prepareHarvestData($data);
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

            // Update Gudang Basah Stock
            $inventory = Inventory::lockForUpdate()->where('item_name', 'Gudang Basah')->first();
            if ($inventory) {
                $inventory->increment('current_stock_kg', $harvest->warehouse_weight);
                $inventory->increment('current_sack_count', $harvest->sack_count);

                $harvest->stockMovement()->create([
                    'inventory_id' => $inventory->id,
                    'movement_date' => $harvest->harvest_date,
                    'direction' => 'in',
                    'type' => 'in',
                    'weight_kg' => $harvest->warehouse_weight,
                    'note_weight_kg' => $harvest->warehouse_weight,
                    'sack_count' => $harvest->sack_count,
                    'balance_after_kg' => $inventory->current_stock_kg,
                    'description' => "Hasil Panen: {$harvest->batch_code} - {$farmer->name}",
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
            $oldWarehouseWeight = $harvest->warehouse_weight;
            $oldSackCount = $harvest->sack_count;

            $data = $this->prepareHarvestData($data);
            $newDeduction = $data['debt_deduction'];
            $diff = $newDeduction - $oldDeduction;

            $harvest->update($data);

            if ($diff != 0) {
                $farmer->decrement('current_debt', $diff);

                $harvest->ledgers()->create([
                    'farmer_id' => $farmer->id,
                    'type' => $diff > 0 ? 'credit' : 'debit',
                    'amount' => abs($diff),
                    'balance_after' => $farmer->current_debt,
                ]);
            }

            // Update Gudang Basah Stock if weight or sacks changed
            $inventory = Inventory::lockForUpdate()->where('item_name', 'Gudang Basah')->first();
            if ($inventory) {
                $weightDiff = $harvest->warehouse_weight - $oldWarehouseWeight;
                $sackDiff = $harvest->sack_count - $oldSackCount;

                if ($weightDiff != 0 || $sackDiff != 0) {
                    $inventory->increment('current_stock_kg', $weightDiff);
                    $inventory->increment('current_sack_count', $sackDiff);

                    $harvest->stockMovement()->updateOrCreate(
                        ['harvest_transaction_id' => $harvest->id],
                        [
                            'inventory_id' => $inventory->id,
                            'movement_date' => $harvest->harvest_date,
                            'direction' => 'in',
                            'type' => 'in',
                            'weight_kg' => $harvest->warehouse_weight,
                            'note_weight_kg' => $harvest->warehouse_weight,
                            'sack_count' => $harvest->sack_count,
                            'balance_after_kg' => $inventory->current_stock_kg,
                            'description' => "Update Hasil Panen: {$harvest->batch_code} - {$farmer->name}",
                        ]
                    );
                }
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

            // Revert Gudang Basah Stock
            $inventory = Inventory::lockForUpdate()->where('item_name', 'Gudang Basah')->first();
            if ($inventory) {
                $inventory->decrement('current_stock_kg', $harvest->warehouse_weight);
                $inventory->decrement('current_sack_count', $harvest->sack_count);
            }

            // Delete stock movement (will be handled by the decrement above for consistency, 
            // but we should delete the record too)
            $harvest->stockMovement()->delete();
            
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

        while (HarvestTransaction::where('batch_code', $code)->exists()) {
            $count++;
            $sequence = str_pad($count, 4, '0', STR_PAD_LEFT);
            $code = "HRV-{$dateStr}-{$sequence}";
        }

        return $code;
    }

    protected function prepareHarvestData(array $data): array
    {
        $totalWeight = (float) $data['total_weight'];
        $sackCount = (int) $data['sack_count'];
        $extraPerSack = (float) ($data['extra_per_sack'] ?? 1);
        $pricePerKg = (float) $data['price_per_kg'];
        $baseValue = $totalWeight * $pricePerKg;
        $deductionType = $data['sale_deduction_type'] ?? 'none';
        $deductionValue = (float) ($data['sale_deduction_value'] ?? 0);

        $saleDeductionAmount = 0;
        if ($deductionType === 'percentage') {
            $saleDeductionAmount = (int) round($baseValue * ($deductionValue / 100));
        } elseif ($deductionType === 'nominal') {
            $saleDeductionAmount = (int) round($deductionValue);
        } elseif ($deductionType === 'weight') {
            $saleDeductionAmount = (int) round($deductionValue * $pricePerKg);
        }

        $netHarvestValue = max(0, (int) round($baseValue) - $saleDeductionAmount);
        $cashPayout = min((int) round($data['cash_payout']), $netHarvestValue);

        $data['extra_per_sack'] = $extraPerSack;
        $data['warehouse_weight'] = $totalWeight + ($sackCount * $extraPerSack);
        $data['sale_deduction_type'] = $deductionType;
        $data['sale_deduction_value'] = $deductionValue;
        $data['sale_deduction_amount'] = $saleDeductionAmount;
        $data['total_harvest_value'] = $netHarvestValue;
        $data['cash_payout'] = $cashPayout;
        $data['debt_deduction'] = $netHarvestValue - $cashPayout;

        return $data;
    }
}
