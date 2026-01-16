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

    public function indexData()
    {
        try {
            $data = Menu::with('parent')->orderBy('order', 'asc')->get();

            return response()->json([
                'status' => true,
                'message' => 'Menu berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Menu gagal diambil' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required',
                'icon' => 'nullable',
                'route' => 'required|nullable',
                'url' => 'required|nullable',
                'parent_id' => 'nullable|exists:menus,id',
                'order' => 'required|integer',
                'is_active' => 'required|boolean',
                'permission' => 'nullable',
            ]);

            Menu::create($data);

            return response()->json([
                'status' => true,
                'message' => 'Menu berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Menu gagal ditambahkan' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, Menu $manajemen_menu)
    {
        $data = $request->validate([
            'name' => 'required',
            'icon' => 'nullable',
            'route' => 'nullable',
            'url' => 'nullable',
            'parent_id' => 'nullable|exists:menus,id',
            'order' => 'nullable|integer',
            'is_active' => 'boolean',
            'permission' => 'nullable',
        ]);

        $manajemen_menu->update($data);

        return response()->json([
            'message' => 'Menu berhasil diperbarui'
        ]);
    }

    public function destroy(Menu $manajemen_menu)
    {
        $manajemen_menu->delete();

        return response()->json([
            'message' => 'Menu berhasil dihapus'
        ]);
    }
}
