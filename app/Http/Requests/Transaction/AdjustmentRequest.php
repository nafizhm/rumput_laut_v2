<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'inventory_id' => 'required|exists:inventories,id',
            'adjustment_weight_kg' => 'required|numeric',
            'description' => 'required|string|max:255',
        ];
    }
}
