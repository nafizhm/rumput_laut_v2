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
            'wet_weight_kg' => 'required|numeric|min:0',
            'price_per_kg' => 'required|numeric|min:0',
            'sack_count' => 'required|integer|min:0',
            'cash_payout' => 'required|numeric|min:0',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $totalValue = $this->wet_weight_kg * $this->price_per_kg;
            if ($this->cash_payout > $totalValue) {
                $validator->errors()->add('cash_payout', 'Pembayaran tunai tidak boleh melebihi total nilai panen.');
            }
        });
    }
}
