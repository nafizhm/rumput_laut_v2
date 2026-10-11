<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_transaction_id' => [$this->route('invoice') ? 'nullable' : 'required', 'integer', 'exists:sales_transactions,id'],
            'number' => [$this->route('invoice') ? 'required' : 'nullable', 'string', 'max:100', Rule::unique('invoices')->ignore($this->route('invoice'))],
            'invoice_date' => ['required', 'date_format:Y-m-d'],
            'due_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:invoice_date'],
            'customer_purchase_order_id' => ['required', 'integer', 'exists:customer_purchase_orders,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_address' => ['required', 'string', 'max:2000'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
            'price_per_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:9999999.99'],
            'payment_status' => ['nullable', Rule::in(['paid', 'unpaid', 'partial'])],
            'payment_terms' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['number' => 'nomor invoice', 'invoice_date' => 'tanggal invoice', 'due_date' => 'jatuh tempo',
            'customer_purchase_order_id' => 'nomor PO', 'delivery_note_id' => 'surat jalan', 'customer_name' => 'nama konsumen', 'customer_address' => 'alamat konsumen',
            'item_name' => 'deskripsi barang', 'quantity_kg' => 'jumlah (kg)', 'price_per_kg' => 'harga per kg',
            'payment_status' => 'status pembayaran', 'payment_terms' => 'cara pembayaran', 'notes' => 'catatan',
            'amount' => 'jumlah pembayaran', 'payment_date' => 'tanggal pembayaran'];
    }
}
