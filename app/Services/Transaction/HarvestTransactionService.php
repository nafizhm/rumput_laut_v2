<?php

namespace App\Services\Transaction;

use App\Models\Farmer;
use App\Models\GoodsReceipt;
use App\Models\HarvestTransaction;
use App\Models\Inventory;
use App\Models\User;
use App\Services\Finance\OperationalAccountingService;
use App\Traits\HandlesTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class HarvestTransactionService
{
    use HandlesTransaction;

    public function createAndReceive(array $data, User $user): HarvestTransaction
    {
        return $this->transaction(function () use ($data, $user) {
            Farmer::lockForUpdate()->findOrFail($data['farmer_id']);
            if (HarvestTransaction::where('submission_key', $data['submission_key'])->exists()) {
                throw ValidationException::withMessages(['confirm' => 'Panen ini sudah dikonfirmasi. Muat ulang daftar untuk melihat hasilnya.']);
            }
            $expectedWeight = round((float) $data['total_weight'] + (int) $data['sack_count'] * (float) $data['extra_per_sack'], 2);
            $actualWeight = round(array_sum(array_column($data['lines'], 'actual_weight_kg')), 2);
            $actualSacks = array_sum(array_column($data['lines'], 'sack_count'));
            if ((int) round($expectedWeight * 100) !== (int) round($actualWeight * 100) || (int) $actualSacks !== (int) $data['sack_count']) {
                throw ValidationException::withMessages(['lines' => 'Total rincian penerimaan harus sama dengan berat gudang dan jumlah karung pada panen.']);
            }
            $receiptData = [
                'farmer_id' => $data['farmer_id'], 'received_date' => $data['harvest_date'],
                'inventory_id' => $data['inventory_id'], 'note_weight_kg' => $expectedWeight,
                'lines' => $data['lines'], 'notes' => $data['notes'] ?? null,
            ];
            unset($data['inventory_id'], $data['lines'], $data['confirm']);
            // Warehouse input never accepts or posts financial values.
            $data = array_intersect_key($data, array_flip(['farmer_id', 'submission_key', 'harvest_date', 'location', 'total_weight', 'sack_count', 'extra_per_sack', 'notes']));
            $harvest = new HarvestTransaction($data + [
                'batch_code' => $this->generateBatchCode(), 'warehouse_weight' => $expectedWeight,
                'separate_payment' => true, 'price_per_kg' => 0, 'total_harvest_value' => 0,
                'cash_payout' => 0, 'debt_deduction' => 0, 'sale_deduction_type' => 'none',
                'sale_deduction_value' => 0, 'sale_deduction_amount' => 0,
            ]);
            $harvest->receipt_mode = 'separate';
            $harvest->save();
            $receiptData['harvest_transaction_id'] = $harvest->id;
            $receiving = app(GoodsReceiptService::class);
            $receipt = $receiving->save($receiptData);
            $receiving->confirm($receipt, $user);
            return $harvest->fresh(['farmer', 'receipt.lines']);
        });
    }

    /**
     * Get all harvest transactions with farmer relationship.
     */
    public function getAllHarvests(): Collection
    {
        return HarvestTransaction::with(['farmer', 'receipt'])->orderBy('harvest_date', 'desc')->get();
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
            $this->assertDebtDeduction($data['debt_deduction'], (int) $farmer->current_debt);
            $harvest = new HarvestTransaction($data);
            $harvest->receipt_mode = 'separate';
            $harvest->is_received = false;
            $harvest->save();

            if ($harvest->debt_deduction > 0) {
                $farmer->decrement('current_debt', $harvest->debt_deduction);

                $harvest->ledgers()->create([
                    'farmer_id' => $farmer->id,
                    'type' => 'credit',
                    'amount' => $harvest->debt_deduction,
                    'balance_after' => $farmer->current_debt,
                ]);
            }

            // New purchases record financial values only. Actual stock is received
            // and weighed through GoodsReceiptService.
            app(OperationalAccountingService::class)->harvest($harvest);

            return $harvest;
        });
    }

    /**
     * Update a harvest transaction and adjust ledger if debt_deduction changes.
     */
    public function updateHarvest(HarvestTransaction $harvest, array $data): bool
    {
        return $this->transaction(function () use ($harvest, $data) {
            $harvest = HarvestTransaction::lockForUpdate()->findOrFail($harvest->id);
            if ((int) $data['farmer_id'] !== (int) $harvest->farmer_id) {
                throw ValidationException::withMessages(['farmer_id' => 'Petani pada pembelian tidak dapat diganti. Batalkan pembelian yang belum diterima lalu buat transaksi baru.']);
            }
            if (GoodsReceipt::where('harvest_transaction_id', $harvest->id)->exists()) {
                throw ValidationException::withMessages(['harvest' => 'Pembelian dengan dokumen penerimaan tidak dapat diubah.']);
            }
            $farmer = Farmer::lockForUpdate()->findOrFail($harvest->farmer_id);

            $oldDeduction = $harvest->debt_deduction;
            $oldWarehouseWeight = $harvest->warehouse_weight;
            $oldSackCount = $harvest->sack_count;

            $data = $this->prepareHarvestData($data);
            $newDeduction = $data['debt_deduction'];
            $this->assertDebtDeduction($newDeduction, (int) $farmer->current_debt + (int) $oldDeduction);
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
            if ($harvest->receipt_mode === 'legacy' && $inventory) {
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

            app(OperationalAccountingService::class)->harvest($harvest);

            return true;
        });
    }

    /**
     * Delete a harvest transaction and create a reversal ledger.
     */
    public function deleteHarvest(HarvestTransaction $harvest): bool
    {
        return $this->transaction(function () use ($harvest) {
            $harvest = HarvestTransaction::lockForUpdate()->findOrFail($harvest->id);
            if (GoodsReceipt::where('harvest_transaction_id', $harvest->id)->exists()) {
                throw ValidationException::withMessages(['harvest' => 'Pembelian dengan dokumen penerimaan tidak dapat dihapus.']);
            }
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
            if ($harvest->receipt_mode === 'legacy' && $inventory) {
                $inventory->decrement('current_stock_kg', $harvest->warehouse_weight);
                $inventory->decrement('current_sack_count', $harvest->sack_count);
            }

            // Delete stock movement (will be handled by the decrement above for consistency,
            // but we should delete the record too)
            $harvest->stockMovement()->delete();

            app(OperationalAccountingService::class)->cancel($harvest, 'purchase');
            $harvest->ledgers()->delete();

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

    private function assertDebtDeduction(int $deduction, int $available): void
    {
        if ($deduction > $available) {
            throw ValidationException::withMessages(['cash_payout' => 'Potongan utang melebihi saldo utang petani. Bayarkan sisa nilai pembelian secara tunai.']);
        }
    }
}
