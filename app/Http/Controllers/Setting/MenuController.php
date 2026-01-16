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

        return view('pages.settings.menu.index', compact('data', 'columns'));
    }

    public function indexData()
    {
        $data = Menu::with('parent')->orderBy('order', 'asc')->get();

        return response()->json($data);
    }
}
