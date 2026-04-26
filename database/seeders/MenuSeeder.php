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
                'name' => 'Transaksi',
                'icon' => 'circle-dollar-sign',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Master',
                'icon' => 'database',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'Setting',
                'icon' => 'settings',
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
                'icon' => null,
                'route' => 'manajemen-menu.index',
                'url' => 'manajemen-menu',
                'permission' => 'menu.read',
                'parent_id' => $parent->id,
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Permission',
                'icon' => null,
                'route' => 'manajemen-permission.index',
                'url' => 'manajemen-permission',
                'permission' => 'permission.read',
                'parent_id' => $parent->id,
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'Manajemen Role',
                'icon' => null,
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
            'permission' => 'manajemen-petani.read',
            'parent_id' => $master->id,
            'order' => 1,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Data Kategori Pengeluaran',
            'icon' => null,
            'route' => 'manajemen-kategori-pengeluaran.index',
            'url' => 'manajemen-kategori-pengeluaran',
            'permission' => 'manajemen-kategori-pengeluaran.read',
            'parent_id' => $master->id,
            'order' => 2,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Manajemen Pabrik',
            'icon' => null,
            'route' => 'manajemen-pabrik.index',
            'url' => 'manajemen-pabrik',
            'permission' => 'manajemen-pabrik.read',
            'parent_id' => $master->id,
            'order' => 3,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Data Inventori',
            'icon' => null,
            'route' => 'manajemen-inventori.index',
            'url' => 'manajemen-inventori',
            'permission' => 'manajemen-inventori.read',
            'parent_id' => $master->id,
            'order' => 4,
            'is_active' => true
        ]);

        // Transaksi children
        $transaksi = Menu::where('name', 'Transaksi')->first();
        Menu::create([
            'name' => 'Manajemen Pinjaman',
            'icon' => null,
            'route' => 'manajemen-pinjaman.index',
            'url' => 'manajemen-pinjaman',
            'permission' => 'manajemen-pinjaman.read',
            'parent_id' => $transaksi->id,
            'order' => 1,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Manajemen Panen',
            'icon' => null,
            'route' => 'manajemen-panen.index',
            'url' => 'manajemen-panen',
            'permission' => 'manajemen-panen.read',
            'parent_id' => $transaksi->id,
            'order' => 2,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Manajemen Pengeluaran',
            'icon' => null,
            'route' => 'manajemen-pengeluaran.index',
            'url' => 'manajemen-pengeluaran',
            'permission' => 'manajemen-pengeluaran.read',
            'parent_id' => $transaksi->id,
            'order' => 3,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Manajemen Gudang',
            'icon' => null,
            'route' => 'manajemen-gudang.index',
            'url' => 'manajemen-gudang',
            'permission' => 'manajemen-gudang.read',
            'parent_id' => $transaksi->id,
            'order' => 4,
            'is_active' => true
        ]);

        Menu::create([
            'name' => 'Manajemen Penjualan',
            'icon' => null,
            'route' => 'manajemen-penjualan.index',
            'url' => 'manajemen-penjualan',
            'permission' => 'manajemen-penjualan.read',
            'parent_id' => $transaksi->id,
            'order' => 5,
            'is_active' => true
        ]);
    }
}
