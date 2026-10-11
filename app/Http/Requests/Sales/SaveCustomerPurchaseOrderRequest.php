<?php

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCustomerPurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'po_number' => ['required', 'string', 'max:100', Rule::unique('customer_purchase_orders')->ignore($this->route('po_konsumen'))],
            'po_date' => ['required', 'date_format:Y-m-d'],
            'factory_id' => ['required', 'integer', 'exists:factories,id'],
            'po_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:4096'],
            'remove_po_file' => ['sometimes', 'boolean'],
            'item_name' => ['required_without:items', 'string', 'max:255'],
            'quantity_kg' => ['required_without:items', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'price_per_kg' => ['required_without:items', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'items' => ['sometimes', 'array', 'min:1', 'max:50'],
            'items.*.id' => ['nullable', 'integer', 'distinct'],
            'items.*.quality_grade_id' => ['nullable', 'integer', 'distinct', Rule::exists('quality_grades', 'id')->where('is_active', true)],
            'items.*.quantity_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0.01', 'max:999999999.99'],
            'items.*.price_per_kg' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999999.99'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:po_date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'po_number' => 'nomor PO', 'po_date' => 'tanggal PO',
            'factory_id' => 'pabrik / konsumen', 'po_file' => 'file PO',
            'customer_name' => 'nama konsumen', 'customer_phone' => 'telepon konsumen',
            'customer_address' => 'alamat konsumen', 'item_name' => 'nama barang',
            'quantity_kg' => 'jumlah (kg)', 'price_per_kg' => 'harga per kg',
            'delivery_date' => 'tanggal pengiriman', 'notes' => 'catatan',
        ];
    }

}
