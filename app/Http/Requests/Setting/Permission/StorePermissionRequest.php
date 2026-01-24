<?php

namespace App\Http\Requests\Setting\Permission;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module_name' => 'required|string|max:255',
            'access' => 'required|array|min:1',
            'menu_id' => 'nullable|exists:menus,id'
        ];
    }
}
