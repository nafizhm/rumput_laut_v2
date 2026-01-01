<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menus = [
            [
                'name' => 'Dashboard',
                'icon' => 'bi bi-speedometer',
                'route' => 'dashboard',
                'url' => 'dashboard',
                'permission' => 'dashboard.show',
                'parent_id' => null,
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Setting',
                'icon' => 'bi bi-gear',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 2,
                'is_active' => true
            ]
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }

        $parent = Menu::where('name', 'Setting')->first();

        $childMenus = [
            [
                'name' => 'Manajemen Menu',
                'icon' => '',
                'route' => 'manajemen-menu.index',
                'url' => 'manajemen-menu',
                'permission' => 'menu.show',
                'parent_id' => $parent->id,
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Hak Akses',
                'icon' => '',
                'route' => 'manajemen-hak-akses.index',
                'url' => 'manajemen-hak-akses',
                'permission' => 'hak-akses.show',
                'parent_id' => $parent->id,
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Role',
                'icon' => '',
                'route' => 'manajemen-role.index',
                'url' => 'manajemen-role',
                'permission' => 'role.show',
                'parent_id' => $parent->id,
                'order' => 3,
                'is_active' => true
            ]
        ];

        foreach ($childMenus as $child) {
            Menu::create($child);
        }
    }
}
