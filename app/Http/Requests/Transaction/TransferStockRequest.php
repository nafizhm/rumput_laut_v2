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
            'from_inventory_id' => 'required|exists:inventories,id|different:to_inventory_id',
            'to_inventory_id' => 'required|exists:inventories,id',
            'transfer_date' => 'required|date',
            'weight_kg' => 'required|numeric|min:0.01',
            'sack_count' => 'required|integer|min:0',
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
