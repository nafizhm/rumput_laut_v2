<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInventoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'item_name' => 'required|string|max:255|unique:inventories,item_name,' . $this->route('manajemen_inventori'),
        ];
    }
}
