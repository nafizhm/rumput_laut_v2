<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:income,expense',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:65535',
            'expense_category_id' => 'required_if:type,expense|exists:expense_categories,id',
        ];
    }
}
