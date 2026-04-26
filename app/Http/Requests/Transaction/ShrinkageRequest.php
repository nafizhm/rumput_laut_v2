<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class ShrinkageRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'inventory_id' => 'required|exists:inventories,id',
            'lost_weight_kg' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ];
    }
}
