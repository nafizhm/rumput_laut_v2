<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('manajemen_kategori_pengeluaran');
        
        return [
            'name' => 'required|string|max:255|unique:expense_categories,name,' . $id,
            'type' => 'required|in:income,expense',
            'is_active' => 'required|boolean',
        ];
    }
}
