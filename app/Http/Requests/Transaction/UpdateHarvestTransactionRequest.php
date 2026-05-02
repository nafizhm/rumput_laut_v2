<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHarvestTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => 'required|exists:farmers,id',
            'harvest_date' => 'required|date',
            'location' => 'required|string|max:255',
            'total_weight' => 'required|numeric|min:0',
            'sack_count' => 'required|integer|min:0',
            'extra_per_sack' => 'required|numeric|min:0',
            'warehouse_weight' => 'required|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:0',
            'sale_deduction_type' => 'nullable|in:none,percentage,nominal',
            'sale_deduction_value' => 'nullable|numeric|min:0',
            'cash_payout' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $totalWeight = $this->input('total_weight');
            $price = $this->input('price_per_kg');
            $cash = $this->input('cash_payout');
            $deductionType = $this->input('sale_deduction_type', 'none');
            $deductionValue = (float) $this->input('sale_deduction_value', 0);

            if (is_numeric($totalWeight) && is_numeric($price)) {
                $baseValue = $totalWeight * $price;
                $deductionAmount = 0;

                if ($deductionType === 'percentage') {
                    if ($deductionValue > 100) {
                        $validator->errors()->add('sale_deduction_value', 'Potongan persentase tidak boleh lebih dari 100%.');
                    }
                    $deductionAmount = $baseValue * ($deductionValue / 100);
                } elseif ($deductionType === 'nominal') {
                    $deductionAmount = $deductionValue;
                }

                if ($deductionAmount > $baseValue) {
                    $validator->errors()->add('sale_deduction_value', 'Potongan penjualan tidak boleh melebihi total nilai panen.');
                }

                $totalValue = max(0, $baseValue - $deductionAmount);
                if (is_numeric($cash) && $cash > $totalValue) {
                    $validator->errors()->add('cash_payout', 'Pembayaran tunai tidak boleh melebihi total nilai panen.');
                }
            }
        });
    }
}
