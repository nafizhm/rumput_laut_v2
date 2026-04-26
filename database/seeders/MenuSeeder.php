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
                'icon' => 'layout-grid',
                'route' => 'dashboard',
                'url' => 'dashboard',
                'permission' => 'dashboard.read',
                'parent_id' => null,
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Setting',
                'icon' => 'settings',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Master',
                'icon' => 'users-round',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Transaksi',
                'icon' => 'circle-dollar-sign',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 4,
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
                'icon' => 'menu',
                'route' => 'manajemen-menu.index',
                'url' => 'manajemen-menu',
                'permission' => 'menu.read',
                'parent_id' => $parent->id,
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Permission',
                'icon' => 'key',
                'route' => 'manajemen-permission.index',
                'url' => 'manajemen-permission',
                'permission' => 'permission.read',
                'parent_id' => $parent->id,
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Role',
                'icon' => 'users',
                'route' => 'manajemen-role.index',
                'url' => 'manajemen-role',
                'permission' => 'role.read',
                'parent_id' => $parent->id,
                'order' => 3,
                'is_active' => true
            ]
        ];

        foreach ($childMenus as $child) {
            Menu::create($child);
        }

        // Master children
        $master = Menu::where('name', 'Master')->first();
        Menu::create([
            'name' => 'Data Petani',
            'icon' => 'user-square',
            'route' => 'manajemen-petani.index',
            'url' => 'manajemen-petani',
            'permission' => 'farmer-data.read',
            'parent_id' => $master->id,
            'order' => 1,
            'is_active' => true
        ]);

        // Transaksi children
        $transaksi = Menu::where('name', 'Transaksi')->first();
        Menu::create([
            'name' => 'Manajemen Pinjaman',
            'icon' => 'hand-coins',
            'route' => 'manajemen-pinjaman.index',
            'url' => 'manajemen-pinjaman',
            'permission' => 'manajemen-pinjaman.read',
            'parent_id' => $transaksi->id,
            'order' => 1,
            'is_active' => true
        ]);
    }
}
