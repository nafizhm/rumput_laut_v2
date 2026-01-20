<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index()
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'width' => '200px'],
            ['key' => 'icon', 'label' => 'Icon', 'width' => '150px'],
            ['key' => 'route', 'label' => 'Route', 'width' => '200px'],
            ['key' => 'url', 'label' => 'Url', 'width' => '200px'],
            ['key' => 'parent.name', 'label' => 'Parent Menu'],
            ['key' => 'order', 'label' => 'Order'],
            ['key' => 'is_active', 'label' => 'Status', 'render' => 'status', 'sortable' => true],
            ['key' => 'action', 'label' => 'Action', 'width' => '170px', 'render' => 'action'],
        ];

        $data = Menu::with('parent')->orderBy('order', 'asc')->get();

        $parents = Menu::whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($menu) => [
                'label' => $menu->name,
                'value' => $menu->id,
            ]);

        return view('pages.settings.menu.index', compact('data', 'columns', 'parents'));
    }

    public function data()
    {
        $data = Menu::with('parent')->orderBy('order', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil diambil',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'required|integer',
            'is_active' => 'required|boolean',
            'permission' => 'nullable|string',
        ]);

        Menu::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil ditambahkan'
        ]);
    }

    public function update(Request $request, Menu $manajemen_menu)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string',
            'route' => 'nullable|string',
            'url' => 'nullable|string',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'required|integer',
            'is_active' => 'required|boolean',
            'permission' => 'nullable|string',
        ]);

        $manajemen_menu->update([
            'name' => $request->name,
            'icon' => $request->icon ?? null,
            'route' => $request->route ?? null,
            'url' => $request->url ?? null,
            'parent_id' => $request->parent_id ?? null,
            'order' => $request->order,
            'is_active' => $request->is_active,
            'permission' => $request->permission ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil diperbarui'
        ]);
    }

    public function destroy(Menu $manajemen_menu)
    {
        $manajemen_menu->delete();

        return response()->json([
            'success' => true,
            'message' => 'Menu berhasil dihapus'
        ]);
    }
}
