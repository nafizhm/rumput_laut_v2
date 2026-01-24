<?php

namespace App\Services\Setting;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Collection;

class MenuService
{
    /**
     * Get all menus with parent relationship, ordered by order.
     */
    public function getAllMenus(): Collection
    {
        return Menu::with('parent')->orderBy('order', 'asc')->get();
    }

    /**
     * Get menus suitable for parent selection (no parent currently).
     */
    public function getParentOptions(): Collection
    {
        return Menu::whereNull('parent_id')
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Create a new menu.
     */
    public function createMenu(array $data): Menu
    {
        return Menu::create($data);
    }

    /**
     * Update an existing menu.
     */
    public function updateMenu(Menu $menu, array $data): bool
    {
        return $menu->update([
            'name' => $data['name'],
            'icon' => $data['icon'] ?? null,
            'route' => $data['route'] ?? null,
            'url' => $data['url'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'order' => $data['order'],
            'is_active' => $data['is_active'],
            'permission' => $data['permission'] ?? null,
        ]);
    }

    /**
     * Delete a menu.
     */
    public function deleteMenu(Menu $menu): ?bool
    {
        return $menu->delete();
    }
}
