<?php

namespace App\Http\Requests\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return !$this->has('items') || $this->user()->can('manajemen-penjualan.create');
    }

    public function rules(): array
    {
        if ($this->has('items')) {
            return [
                'sale_source' => 'required|in:direct,po',
                'customer_purchase_order_id' => 'nullable|required_if:sale_source,po|prohibited_if:sale_source,direct|integer|exists:customer_purchase_orders,id',
                'deduction_type' => 'required|in:none,percentage,nominal,weight',
                'deduction_value' => 'required|numeric|decimal:0,2|min:0|max:999999999.99',
                'deduction_weight_kg' => 'required|numeric|decimal:0,2|min:0|max:1000',
                'factory_id' => 'required|integer|exists:factories,id',
                'sale_date' => 'required|date_format:Y-m-d|before_or_equal:today',
                'items' => 'required|array|min:1|max:50',
                'items.*.item_name' => 'required|string|max:255',
                'items.*.customer_purchase_order_item_id' => 'nullable|prohibited_if:sale_source,direct|integer|exists:customer_purchase_order_items,id',
                'items.*.sold_weight_kg' => 'required|numeric|decimal:0,2|min:0.01|max:999999999.99',
                'items.*.sold_sacks' => 'required|integer|min:1|max:1000000',
                'items.*.price_per_kg' => 'required|numeric|decimal:0,2|min:0|max:999999999.99',
            ];
        }
        return [
            'sale_source' => 'nullable|in:direct,po',
            'customer_purchase_order_id' => 'nullable|required_if:sale_source,po|prohibited_if:sale_source,direct|integer|exists:customer_purchase_orders,id',
            'customer_purchase_order_item_id' => 'nullable|prohibited_if:sale_source,direct|integer|exists:customer_purchase_order_items,id',
            'stock_mode' => 'nullable|in:immediate,delivery_note',
            'factory_id' => 'required|exists:factories,id',
            'finished_batch_id' => 'nullable|integer|exists:finished_batches,id',
            'quality_grade_id' => 'nullable|integer|exists:quality_grades,id',
            'inventory_id' => 'required|exists:inventories,id',
            'sale_date' => 'required|date',
            'sold_weight_kg' => 'required|numeric|min:0.01',
            'price_per_kg' => 'required|numeric|min:0',
            'sold_sacks' => 'nullable|integer|min:0',
            'deduction_type' => 'nullable|in:none,percentage,nominal,weight',
            'deduction_value' => 'nullable|numeric|min:0',
            'deduction_weight_kg' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:unpaid',
        ];
    }
}
