<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreHarvestTransactionRequest extends FormRequest
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
            'cash_payout' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $totalWeight = $this->input('total_weight');
            $price = $this->input('price_per_kg');
            $cash = $this->input('cash_payout');

            if (is_numeric($totalWeight) && is_numeric($price)) {
                $totalValue = $totalWeight * $price;
                if (is_numeric($cash) && $cash > $totalValue) {
                    $validator->errors()->add('cash_payout', 'Pembayaran tunai tidak boleh melebihi total nilai panen.');
                }
            }
        });
    }
}
