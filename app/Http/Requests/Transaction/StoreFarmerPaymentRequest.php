<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreFarmerPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'farmer_id' => 'required|exists:farmers,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'farmer_id.required' => 'Petani wajib dipilih.',
            'farmer_id.exists' => 'Petani tidak valid.',
            'payment_date.required' => 'Tanggal bayar wajib diisi.',
            'amount.required' => 'Jumlah bayar wajib diisi.',
            'amount.min' => 'Jumlah bayar harus lebih dari 0.',
        ];
    }
}
