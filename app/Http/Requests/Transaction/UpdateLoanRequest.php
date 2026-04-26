<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLoanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => 'required|exists:farmers,id',
            'loan_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'description' => 'nullable|string|max:255',
        ];
    }
}
