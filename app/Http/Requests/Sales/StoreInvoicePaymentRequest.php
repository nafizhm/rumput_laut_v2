<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'regex:/^[0-9]+(\\.[0-9]{1,2})?$/'],
            'cooperation_deduction' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'cooperation_notes' => [Rule::requiredIf(fn () => (float) $this->input('cooperation_deduction', 0) > 0), 'nullable', 'string', 'max:1000'],
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
            'amount.min' => 'Uang diterima tidak boleh negatif.',
            'amount.regex' => 'Jumlah pembayaran maksimal 2 angka desimal.',
            'payment_proof.mimes' => 'Bukti pembayaran harus berupa file JPG, JPEG, PNG, atau PDF.',
            'payment_proof.max' => 'Ukuran bukti pembayaran maksimal 4 MB.',
        ];
    }
}
