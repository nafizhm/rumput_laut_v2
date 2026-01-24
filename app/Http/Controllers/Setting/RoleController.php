<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\Role\StoreRoleRequest;
use App\Http\Requests\Setting\Role\UpdateRoleRequest;
use App\Services\Setting\RoleService;
use App\Traits\JsonResponser;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    use JsonResponser;

    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'guard_name', 'label' => 'Guard Name'],
            ['key' => 'permission', 'label' => 'Permissions', 'render' => 'permission'],
            ['key' => 'action', 'label' => 'Action', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->roleService->getRolesWithPermissions();

        $permission = $this->roleService->getAllPermissions()->map(fn($menu) => [
            'label' => $menu->name,
            'value' => $menu->id,
        ]);

        return view('pages.settings.role.index', compact('data', 'columns', 'permission'));
    }

    public function data()
    {
        $data = $this->roleService->getRolesWithPermissions();

        return $this->successResponse('Data berhasil diambil', $data);
    }

    public function store(StoreRoleRequest $request)
    {
        $this->roleService->createRole($request->validated());

        return $this->successResponse('Role berhasil ditambahkan');
    }

    public function update(UpdateRoleRequest $request, Role $manajemen_role)
    {
        $this->roleService->updateRole($manajemen_role, $request->validated());

        return $this->successResponse('Role berhasil diupdate');
    }

    public function destroy(Role $manajemen_role)
    {
        $this->roleService->deleteRole($manajemen_role);

        return $this->successResponse('Role berhasil dihapus');
    }
}
