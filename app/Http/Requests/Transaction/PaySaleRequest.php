<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class PaySaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_date' => 'required|date',
            'paid_amount' => 'required|numeric|min:0',
            'payment_notes' => 'nullable|string|max:1000',
            'payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ];
    }
}
