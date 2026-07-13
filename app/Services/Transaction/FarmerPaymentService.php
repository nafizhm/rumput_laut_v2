<?php

namespace App\Services\Transaction;

use App\Models\Farmer;
use App\Models\FarmerPayment;
use App\Traits\HandlesTransaction;
use Exception;

class FarmerPaymentService
{
    use HandlesTransaction;

    public function createPayment(array $data): FarmerPayment
    {
        return $this->transaction(function () use ($data) {
            $farmer = Farmer::lockForUpdate()->findOrFail($data['farmer_id']);

            if ($farmer->current_debt <= 0) {
                throw new Exception('Petani ini tidak memiliki hutang.');
            }

            $amount = (int) round($data['amount']);

            if ($amount > $farmer->current_debt) {
                throw new Exception('Jumlah bayar melebihi total hutang petani (Rp ' . number_format($farmer->current_debt, 0, ',', '.') . ').');
            }

            $payment = FarmerPayment::create([
                'farmer_id' => $farmer->id,
                'payment_date' => $data['payment_date'],
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
            ]);

            $farmer->decrement('current_debt', $amount);

            $payment->ledgers()->create([
                'farmer_id' => $farmer->id,
                'type' => 'credit',
                'amount' => $amount,
                'balance_after' => $farmer->current_debt,
            ]);

            return $payment;
        });
    }

    public function deletePayment(FarmerPayment $payment): bool
    {
        return $this->transaction(function () use ($payment) {
            $farmer = Farmer::lockForUpdate()->findOrFail($payment->farmer_id);

            $farmer->increment('current_debt', $payment->amount);

            $payment->ledgers()->create([
                'farmer_id' => $farmer->id,
                'type' => 'debit',
                'amount' => $payment->amount,
                'balance_after' => $farmer->current_debt,
            ]);

            $payment->ledgers()->delete();

            return $payment->delete();
        });
    }
}
