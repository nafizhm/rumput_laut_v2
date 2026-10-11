<?php

namespace App\Services\Finance;

use App\Models\{ChartOfAccount, Farmer, HarvestPayment, HarvestTransaction, MaterialBatch, User};
use App\Services\Transaction\GoodsReceiptService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HarvestFinanceService
{
    public function price(HarvestTransaction $harvest, array $data, User $user): void
    {
        DB::transaction(function () use ($harvest, $data, $user) {
            $harvest = HarvestTransaction::lockForUpdate()->findOrFail($harvest->id);
            $this->assertNewFlow($harvest);
            if ($harvest->priced_at) {
                throw ValidationException::withMessages(['purchase_date' => 'Harga panen sudah dikonfirmasi dan dikunci.']);
            }
            if ($harvest->receipt?->status !== 'confirmed' || ! $harvest->is_received) {
                throw ValidationException::withMessages(['purchase_date' => 'Barang harus sudah diterima sebelum nilai pembelian ditetapkan.']);
            }
            if ($data['purchase_date'] < $harvest->receipt->received_date->format('Y-m-d')) {
                throw ValidationException::withMessages(['purchase_date' => 'Tanggal pembelian tidak boleh sebelum tanggal penerimaan barang.']);
            }
            $gross = (int) round((float) $harvest->total_weight * (int) $data['price_per_kg']);
            $type = $data['sale_deduction_type'] ?? 'none';
            $value = (float) ($data['sale_deduction_value'] ?? 0);
            $discount = (int) round(match ($type) {
                'percentage' => $gross * $value / 100,
                'nominal' => $value,
                'weight' => $value * (int) $data['price_per_kg'],
                default => 0,
            });
            if (($type === 'percentage' && $value > 100) || $discount > $gross || $gross - $discount < 1 || $gross > 9999999999999) {
                throw ValidationException::withMessages(['sale_deduction_value' => 'Potongan harus sesuai nilai pembelian dan nilai bersih harus lebih dari nol.']);
            }
            $batches = MaterialBatch::whereHas('line', fn ($q) => $q->where('goods_receipt_id', $harvest->receipt->id))->lockForUpdate()->get();
            $harvest->update([
                'purchase_date' => $data['purchase_date'], 'price_per_kg' => $data['price_per_kg'],
                'sale_deduction_type' => $type, 'sale_deduction_value' => $type === 'none' ? 0 : $value,
                'sale_deduction_amount' => $discount, 'total_harvest_value' => $gross - $discount,
                'priced_at' => now(), 'priced_by' => $user->id, 'priced_by_name' => $user->name,
            ]);
            app(GoodsReceiptService::class)->valueBatches($harvest->receipt->load('harvest'));
            app(OperationalAccountingService::class)->harvest($harvest);
        }, 3);
    }

    public function pay(HarvestTransaction $harvest, array $data, User $user): HarvestPayment
    {
        return DB::transaction(function () use ($harvest, $data, $user) {
            $harvest = HarvestTransaction::lockForUpdate()->findOrFail($harvest->id);
            $this->assertNewFlow($harvest);
            if (! $harvest->priced_at) {
                throw ValidationException::withMessages(['amount' => 'Tetapkan harga pembelian terlebih dahulu.']);
            }
            if (HarvestPayment::where('submission_key', $data['submission_key'])->exists()) {
                throw ValidationException::withMessages(['amount' => 'Pembayaran ini sudah dicatat. Muat ulang halaman.']);
            }
            if ($data['payment_date'] < $harvest->purchase_date->format('Y-m-d')) {
                throw ValidationException::withMessages(['payment_date' => 'Tanggal pembayaran tidak boleh sebelum tanggal pembelian.']);
            }
            $lastDate = $harvest->payments()->max('payment_date');
            if ($lastDate && $data['payment_date'] < \Illuminate\Support\Carbon::parse($lastDate)->format('Y-m-d')) {
                throw ValidationException::withMessages(['payment_date' => 'Tanggal tidak boleh mendahului pembayaran sebelumnya.']);
            }
            $amount = (int) $data['amount'];
            $deduction = (int) $data['debt_deduction'];
            if ($amount + $deduction < 1 || $amount + $deduction > $harvest->outstanding_payable) {
                throw ValidationException::withMessages(['amount' => 'Jumlah pembayaran dan potongan pinjaman harus lebih dari nol dan tidak melebihi sisa utang pembelian.']);
            }
            $farmer = Farmer::lockForUpdate()->findOrFail($harvest->farmer_id);
            if ($deduction > (int) $farmer->current_debt) {
                throw ValidationException::withMessages(['debt_deduction' => 'Potongan melebihi saldo pinjaman petani.']);
            }
            $cash = null;
            if ($amount > 0) {
                $cash = ChartOfAccount::lockForUpdate()->find($data['cash_account_id'] ?? null);
                if (! $cash || ! $cash->is_active || $cash->category !== 'Harta' || $cash->account_group !== 'Kas dan Bank' || $cash->normal_balance !== 'Debit') {
                    throw ValidationException::withMessages(['cash_account_id' => 'Pilih akun kas/bank aktif dengan saldo normal Debit.']);
                }
            }
            $payment = $harvest->payments()->create([
                'submission_key' => $data['submission_key'], 'payment_date' => $data['payment_date'],
                'amount' => $amount, 'debt_deduction' => $deduction, 'cash_account_id' => $cash?->id,
                'notes' => $data['notes'] ?? null, 'created_by' => $user->id, 'created_by_name' => $user->name,
            ]);
            if ($deduction > 0) {
                $farmer->decrement('current_debt', $deduction);
                $payment->ledgers()->create(['farmer_id' => $farmer->id, 'type' => 'credit', 'amount' => $deduction, 'balance_after' => $farmer->current_debt]);
            }
            $harvest->increment('cash_payout', $amount);
            $harvest->increment('debt_deduction', $deduction);
            app(OperationalAccountingService::class)->harvestPayment($payment);
            return $payment;
        }, 3);
    }

    private function assertNewFlow(HarvestTransaction $harvest): void
    {
        if (! $harvest->separate_payment) {
            throw ValidationException::withMessages(['harvest' => 'Transaksi lama tetap menggunakan riwayat pembayaran sebelumnya dan tidak dapat dibayar ulang.']);
        }
        if (! app(OperationalAccountingService::class)->enabled()) {
            throw ValidationException::withMessages(['accounting' => 'Integrasi jurnal harus tersedia sebelum transaksi keuangan disimpan.']);
        }
    }
}
