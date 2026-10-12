<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class AdjustmentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'movement_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.kind' => 'required|in:material,finished,legacy',
            'items.*.id' => 'required|integer|min:1',
            'items.*.expected_weight' => 'required|numeric|min:0',
            'items.*.expected_count' => 'required|integer|min:0',
            'items.*.actual_weight' => 'required|numeric|decimal:0,2|min:0|max:9999999999.99',
            'items.*.actual_count' => 'required|integer|min:0|max:4294967295',
        ];
    }
}
