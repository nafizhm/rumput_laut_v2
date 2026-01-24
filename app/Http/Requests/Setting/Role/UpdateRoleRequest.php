<?php

namespace App\Http\Requests\Setting\Role;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Accessing the route parameter 'manajemen_role' which holds the Role model
        $roleId = $this->route('manajemen_role') ? $this->route('manajemen_role')->id : null;

        return [
            'name' => 'required|unique:roles,name,' . $roleId,
            'permissions' => 'nullable|array',
        ];
    }
}
