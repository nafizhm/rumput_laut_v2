<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\Permission\StorePermissionRequest;
use App\Http\Requests\Setting\Permission\UpdatePermissionRequest;
use App\Services\Setting\PermissionService;
use App\Traits\JsonResponser;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    use JsonResponser;

    protected PermissionService $permissionService;

    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function index()
    {
        $columns = [
            ['key' => 'module_name', 'label' => 'Module Name'],
            ['key' => 'guard_name', 'label' => 'Guard Name'],
            ['key' => 'permission', 'label' => 'Permissions', 'render' => 'permission'],
            ['key' => 'action', 'label' => 'Action', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->permissionService->getGroupedPermissions();

        $menus = $this->permissionService->getMenuOptions()
            ->map(fn($menu) => [
                'label' => ($menu->parent ? $menu->parent->name . ' > ' : '') . $menu->name,
                'value' => $menu->id,
            ]);

        return view('pages.settings.permission.index', compact('data', 'columns', 'menus'));
    }

    public function data()
    {
        $data = $this->permissionService->getGroupedPermissions();

        return $this->successResponse('Data berhasil diambil', $data);
    }

    public function store(StorePermissionRequest $request)
    {
        $this->permissionService->createPermissionGroup($request->validated());

        return $this->successResponse('Permission berhasil ditambahkan');
    }

    public function update(UpdatePermissionRequest $request, Permission $manajemen_permission)
    {
        $this->permissionService->updatePermissionGroup($manajemen_permission, $request->validated());

        return $this->successResponse('Permission berhasil diperbarui');
    }

    public function destroy(Permission $manajemen_permission)
    {
        $this->permissionService->deletePermissionGroup($manajemen_permission);

        return $this->successResponse('Permission berhasil dihapus');
    }
}
