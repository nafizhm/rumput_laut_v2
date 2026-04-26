<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
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

            'manajemen-pinjaman.read',
            'manajemen-pinjaman.create',
            'manajemen-pinjaman.update',
            'manajemen-pinjaman.delete',

            'manajemen-panen.read',
            'manajemen-panen.create',
            'manajemen-panen.update',
            'manajemen-panen.delete',

            'manajemen-pengeluaran.read',
            'manajemen-pengeluaran.create',
            'manajemen-pengeluaran.update',
            'manajemen-pengeluaran.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $superAdmin = Role::create(['name' => 'superadmin']);
        $superAdmin->givePermissionTo(Permission::all());

        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo([
            'dashboard.read',
            'manajemen-petani.read',
            'manajemen-petani.create',
            'manajemen-petani.update',
            'manajemen-petani.delete',
            'manajemen-pinjaman.read',
            'manajemen-pinjaman.create',
            'manajemen-pinjaman.update',
            'manajemen-pinjaman.delete',
        ]);
    }
}
