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
                'name' => 'Laporan Arus Kas',
                'icon' => 'file-text',
                'route' => 'cash-flow.index',
                'url' => 'laporan-arus-kas',
                'permission' => 'cash-flow.read',
                'parent_id' => null,
                'order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Master',
                'icon' => 'database',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'Setting',
                'icon' => 'settings',
                'route' => null,
                'url' => null,
                'permission' => null,
                'parent_id' => null,
                'order' => 5,
                'is_active' => true
            ]
        ];

        foreach ($menus as $menu) {
            Menu::updateOrCreate(['name' => $menu['name']], $menu);
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
            Menu::updateOrCreate(['name' => $child['name']], $child);
        }

        // Master children
        $master = Menu::where('name', 'Master')->first();
        Menu::updateOrCreate(['name' => 'Data Petani'], [
            'icon' => 'user-square',
            'route' => 'manajemen-petani.index',
            'url' => 'manajemen-petani',
            'permission' => 'manajemen-petani.read',
            'parent_id' => $master->id,
            'order' => 1,
            'is_active' => true
        ]);

        Menu::where('name', 'Data Kategori Pengeluaran')->delete();

        Menu::updateOrCreate(['name' => 'Data Kategori'], [
            'icon' => null,
            'route' => 'manajemen-kategori-pengeluaran.index',
            'url' => 'manajemen-kategori-pengeluaran',
            'permission' => 'manajemen-kategori-pengeluaran.read',
            'parent_id' => $master->id,
            'order' => 2,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Data Pabrik'], [
            'icon' => null,
            'route' => 'manajemen-pabrik.index',
            'url' => 'manajemen-pabrik',
            'permission' => 'manajemen-pabrik.read',
            'parent_id' => $master->id,
            'order' => 3,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Data Inventori'], [
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
        Menu::updateOrCreate(['name' => 'Manajemen Pinjaman'], [
            'icon' => null,
            'route' => 'manajemen-pinjaman.index',
            'url' => 'manajemen-pinjaman',
            'permission' => 'manajemen-pinjaman.read',
            'parent_id' => $transaksi->id,
            'order' => 1,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Manajemen Panen'], [
            'icon' => null,
            'route' => 'manajemen-panen.index',
            'url' => 'manajemen-panen',
            'permission' => 'manajemen-panen.read',
            'parent_id' => $transaksi->id,
            'order' => 2,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Pemasukan & Pengeluaran'], [
            'icon' => null,
            'route' => 'manajemen-pemasukan-pengeluaran.index',
            'url' => 'manajemen-pemasukan-pengeluaran',
            'permission' => 'manajemen-pemasukan-pengeluaran.read',
            'parent_id' => $transaksi->id,
            'order' => 3,
            'is_active' => true
        ]);

        // Remove old separate income/expense menu items
        Menu::whereIn('name', ['Manajemen Pemasukan', 'Manajemen Pengeluaran'])->delete();

        Menu::updateOrCreate(['name' => 'Manajemen Gudang'], [
            'icon' => null,
            'route' => 'manajemen-gudang.index',
            'url' => 'manajemen-gudang',
            'permission' => 'manajemen-gudang.read',
            'parent_id' => $transaksi->id,
            'order' => 4,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Gudang Basah'], [
            'icon' => null,
            'route' => 'manajemen-gudang.wet',
            'url' => 'manajemen-gudang/basah',
            'permission' => 'manajemen-gudang.read',
            'parent_id' => $transaksi->id,
            'order' => 5,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Gudang Kering'], [
            'icon' => null,
            'route' => 'manajemen-gudang.dry',
            'url' => 'manajemen-gudang/kering',
            'permission' => 'manajemen-gudang.read',
            'parent_id' => $transaksi->id,
            'order' => 6,
            'is_active' => true
        ]);

        Menu::updateOrCreate(['name' => 'Manajemen Penjualan'], [
            'icon' => null,
            'route' => 'manajemen-penjualan.index',
            'url' => 'manajemen-penjualan',
            'permission' => 'manajemen-penjualan.read',
            'parent_id' => $transaksi->id,
            'order' => 7,
            'is_active' => true
        ]);
    }
}
