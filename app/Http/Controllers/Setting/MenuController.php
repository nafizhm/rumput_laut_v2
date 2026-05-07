<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Setting\Menu\StoreMenuRequest;
use App\Http\Requests\Setting\Menu\UpdateMenuRequest;
use App\Models\Menu;
use App\Services\Setting\MenuService;
use App\Traits\JsonResponser;

class MenuController extends Controller
{
    use JsonResponser;

    protected MenuService $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }

    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'width' => '200px'],
            ['key' => 'icon', 'label' => 'Icon', 'width' => '150px'],
            ['key' => 'route', 'label' => 'Route', 'width' => '200px'],
            ['key' => 'url', 'label' => 'Url', 'width' => '200px'],
            ['key' => 'parent.name', 'label' => 'Parent Menu'],
            ['key' => 'order', 'label' => 'Order', 'width' => '100px'],
            ['key' => 'is_active', 'label' => 'Status', 'render' => 'status', 'sortable' => true],
            ['key' => 'action', 'label' => 'Action', 'width' => '170px', 'render' => 'action'],
        ];

        $data = $this->menuService->getAllMenus();

        $parents = $this->menuService->getParentOptions()
            ->map(fn($menu) => [
                'label' => $menu->name,
                'value' => $menu->id,
            ]);

        return view('pages.settings.menu.index', compact('data', 'columns', 'parents'));
    }

    public function data()
    {
        $data = $this->menuService->getAllMenus();

        return $this->successResponse('Menu berhasil diambil', $data);
    }

    public function store(StoreMenuRequest $request)
    {
        $this->menuService->createMenu($request->validated());

        return $this->successResponse('Menu berhasil ditambahkan');
    }

    public function update(UpdateMenuRequest $request, Menu $manajemen_menu)
    {
        $this->menuService->updateMenu($manajemen_menu, $request->validated());

        return $this->successResponse('Menu berhasil diperbarui');
    }

    public function destroy(Menu $manajemen_menu)
    {
        $this->menuService->deleteMenu($manajemen_menu);

        return $this->successResponse('Menu berhasil dihapus');
    }
}
