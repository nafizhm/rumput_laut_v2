<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoicePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'invoice_id' => ['required', 'integer', 'exists:invoices,id'],
            'payment_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99', 'regex:/^[0-9]+(\\.[0-9]{1,2})?$/'],
            'payment_proof' => ['prohibited'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'invoice_id.required' => 'Invoice wajib dipilih.',
            'invoice_id.exists' => 'Invoice tidak valid.',
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'amount.required' => 'Jumlah pembayaran wajib diisi.',
            'amount.min' => 'Jumlah pembayaran harus lebih dari 0.',
            'amount.regex' => 'Jumlah pembayaran maksimal 2 angka desimal.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa file JPG, JPEG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 4 MB.',
        ];
    }
}
