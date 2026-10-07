<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'stock_mode' => 'nullable|in:immediate,delivery_note',
            'factory_id' => 'required|exists:factories,id',
            'finished_batch_id' => 'nullable|integer|exists:finished_batches,id',
            'inventory_id' => 'required|exists:inventories,id',
            'sale_date' => 'required|date',
            'sold_weight_kg' => 'required|numeric|min:0.01',
            'price_per_kg' => 'required|numeric|min:0',
            'sold_sacks' => 'nullable|integer|min:0',
            'deduction_type' => 'nullable|in:none,percentage,nominal,weight',
            'deduction_value' => 'nullable|numeric|min:0',
            'deduction_weight_kg' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:unpaid',
        ];
    }
}
