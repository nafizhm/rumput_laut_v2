<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'usage' => 'required|in:receiving,finished',
            'source_kind' => 'required|in:area,legacy',
            'from_id' => 'required|integer|min:1',
            'to_area_id' => 'required|integer|exists:warehouse_areas,id',
            'transfer_date' => 'required|date|before_or_equal:today',
            'weight_kg' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999.99',
            'sack_count' => 'required|integer|min:1|max:4294967295',
            'description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'from_inventory_id.required' => 'Gudang asal wajib dipilih.',
            'from_inventory_id.exists' => 'Gudang asal tidak valid.',
            'from_inventory_id.different' => 'Gudang asal dan tujuan harus berbeda.',
            'to_inventory_id.required' => 'Gudang tujuan wajib dipilih.',
            'to_inventory_id.exists' => 'Gudang tujuan tidak valid.',
            'transfer_date.required' => 'Tanggal transfer wajib diisi.',
            'weight_kg.required' => 'Berat wajib diisi.',
            'weight_kg.min' => 'Berat harus lebih dari 0.',
            'sack_count.required' => 'Jumlah karung wajib diisi.',
            'sack_count.min' => 'Jumlah karung tidak boleh negatif.',
        ];
    }
}
