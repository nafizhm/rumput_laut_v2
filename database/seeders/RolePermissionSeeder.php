<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'dashboard.read',

            'menu.read',
            'menu.create',
            'menu.update',
            'menu.delete',

            'permission.read',
            'permission.create',
            'permission.update',
            'permission.delete',

            'role.read',
            'role.create',
            'role.update',
            'role.delete',

            'manajemen-petani.read',
            'manajemen-petani.create',
            'manajemen-petani.update',
            'manajemen-petani.delete',

            'manajemen-kategori-pengeluaran.read',
            'manajemen-kategori-pengeluaran.create',
            'manajemen-kategori-pengeluaran.update',
            'manajemen-kategori-pengeluaran.delete',

            'manajemen-pabrik.read',
            'manajemen-pabrik.create',
            'manajemen-pabrik.update',
            'manajemen-pabrik.delete',

            'manajemen-inventori.read',
            'manajemen-inventori.create',
            'manajemen-inventori.update',
            'manajemen-inventori.delete',

            'manajemen-pinjaman.read',
            'manajemen-pinjaman.create',
            'manajemen-pinjaman.update',
            'manajemen-pinjaman.delete',

            'manajemen-panen.read',
            'manajemen-panen.create',
            'manajemen-panen.update',
            'manajemen-panen.delete',

            'manajemen-pemasukan-pengeluaran.read',
            'manajemen-pemasukan-pengeluaran.create',
            'manajemen-pemasukan-pengeluaran.update',
            'manajemen-pemasukan-pengeluaran.delete',

            'manajemen-gudang.read',
            'manajemen-gudang.create',
            'manajemen-gudang.update',
            'manajemen-gudang.delete',

            'manajemen-penjualan.read',
            'manajemen-penjualan.create',
            'manajemen-penjualan.update',
            'manajemen-penjualan.delete',

            'cash-flow.read',
        ];

        // Remove old separate income/expense permissions
        Permission::whereIn('name', [
            'manajemen-pemasukan.read', 'manajemen-pemasukan.create', 'manajemen-pemasukan.update', 'manajemen-pemasukan.delete',
            'manajemen-pengeluaran.read', 'manajemen-pengeluaran.create', 'manajemen-pengeluaran.update', 'manajemen-pengeluaran.delete',
        ])->delete();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Super Admin — all permissions
        $superAdmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superAdmin->syncPermissions(Permission::all());

        // Owner — all except menu, permission, role
        $owner = Role::firstOrCreate(['name' => 'owner']);
        $owner->syncPermissions([
            'dashboard.read',

            'manajemen-petani.read',
            'manajemen-petani.create',
            'manajemen-petani.update',
            'manajemen-petani.delete',

            'manajemen-kategori-pengeluaran.read',
            'manajemen-kategori-pengeluaran.create',
            'manajemen-kategori-pengeluaran.update',
            'manajemen-kategori-pengeluaran.delete',

            'manajemen-pabrik.read',
            'manajemen-pabrik.create',
            'manajemen-pabrik.update',
            'manajemen-pabrik.delete',

            'manajemen-inventori.read',
            'manajemen-inventori.create',
            'manajemen-inventori.update',
            'manajemen-inventori.delete',

            'manajemen-pinjaman.read',
            'manajemen-pinjaman.create',
            'manajemen-pinjaman.update',
            'manajemen-pinjaman.delete',

            'manajemen-panen.read',
            'manajemen-panen.create',
            'manajemen-panen.update',
            'manajemen-panen.delete',

            'manajemen-pemasukan-pengeluaran.read',
            'manajemen-pemasukan-pengeluaran.create',
            'manajemen-pemasukan-pengeluaran.update',
            'manajemen-pemasukan-pengeluaran.delete',

            'manajemen-gudang.read',
            'manajemen-gudang.create',
            'manajemen-gudang.update',
            'manajemen-gudang.delete',

            'manajemen-penjualan.read',
            'manajemen-penjualan.create',
            'manajemen-penjualan.update',
            'manajemen-penjualan.delete',

            'cash-flow.read',
        ]);

        // Admin — only non-financial menus (master data & settings)
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'dashboard.read',

            'menu.read',
            'menu.create',
            'menu.update',
            'menu.delete',

            'permission.read',
            'permission.create',
            'permission.update',
            'permission.delete',

            'role.read',
            'role.create',
            'role.update',
            'role.delete',

            'manajemen-petani.read',
            'manajemen-petani.create',
            'manajemen-petani.update',
            'manajemen-petani.delete',

            'manajemen-kategori-pengeluaran.read',
            'manajemen-kategori-pengeluaran.create',
            'manajemen-kategori-pengeluaran.update',
            'manajemen-kategori-pengeluaran.delete',

            'manajemen-pabrik.read',
            'manajemen-pabrik.create',
            'manajemen-pabrik.update',
            'manajemen-pabrik.delete',

            'manajemen-inventori.read',
            'manajemen-inventori.create',
            'manajemen-inventori.update',
            'manajemen-inventori.delete',
        ]);
    }
}
