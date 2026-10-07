<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDeliveryNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sales_transaction_id' => [$this->route('surat_jalan') ? 'nullable' : 'required', 'integer', 'exists:sales_transactions,id'],
            'sack_count' => ['nullable', 'integer', 'min:0'],
            'number' => ['required', 'string', 'max:100', Rule::unique('delivery_notes')->ignore($this->route('surat_jalan'))],
            'delivery_date' => ['required', 'date_format:Y-m-d'],
            'customer_purchase_order_id' => ['nullable', 'integer', 'exists:customer_purchase_orders,id'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'destination_address' => ['required', 'string', 'max:2000'],
            'origin' => ['required', 'string', 'max:255'],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'packaging' => ['nullable', 'string', 'max:255'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['number' => 'nomor surat jalan', 'delivery_date' => 'tanggal',
            'customer_purchase_order_id' => 'PO konsumen', 'recipient_name' => 'penerima',
            'destination_address' => 'alamat tujuan', 'origin' => 'asal barang',
            'item_name' => 'deskripsi barang', 'quantity_kg' => 'jumlah (kg)',
            'packaging' => 'kemasan', 'remarks' => 'keterangan', 'notes' => 'catatan',
            'sender_name' => 'nama pengirim', 'receiver_name' => 'nama penerima'];
    }
}
