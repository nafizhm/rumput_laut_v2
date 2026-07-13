<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'income_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
        ];
    }
}
