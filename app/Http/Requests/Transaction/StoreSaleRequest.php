<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'factory_id' => 'required|exists:factories,id',
            'inventory_id' => 'required|exists:inventories,id',
            'sale_date' => 'required|date',
            'sold_weight_kg' => 'required|numeric|min:0.01',
            'price_per_kg' => 'required|numeric|min:0',
            'sold_sacks' => 'nullable|integer|min:0',
            'payment_status' => 'nullable|in:unpaid,paid',
        ];
    }
}
