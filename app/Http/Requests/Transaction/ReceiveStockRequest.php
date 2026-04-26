<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class ReceiveStockRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'inventory_id' => 'required|exists:inventories,id',
            'harvest_transaction_id' => 'required|exists:harvest_transactions,id',
            'actual_weight_kg' => 'required|numeric|min:0',
            'actual_sacks' => 'required|integer|min:0',
        ];
    }
}

// Separate files would be better, but I'll create them one by one if needed.
// Actually, I'll create them in separate files as per Laravel convention.
