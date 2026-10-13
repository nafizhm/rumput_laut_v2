<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\{Role, Permission};
use Spatie\Permission\PermissionRegistrar;

class AccountingMenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            foreach (['journal.read', 'journal.create', 'journal.update', 'journal.delete', 'journal.post', 'journal.reverse', 'financial-reports.read'] as $name) {
                $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
                foreach (Role::whereIn('name', ['owner', 'superadmin'])->get() as $role) $role->givePermissionTo($permission);
            }
            $parent = Menu::whereNull('parent_id')->where('name', 'Keuangan')->firstOrFail();
            foreach ([
                ['jurnal.index', 'Jurnal Umum', 'keuangan/jurnal', 'journal.read', 3],
                ['saldo-awal.index', 'Saldo Awal', 'keuangan/saldo-awal', 'journal.read', 8],
                ['neraca.index', 'Neraca', 'keuangan/neraca', 'financial-reports.read', 6],
                ['laba-rugi.index', 'Laba Rugi', 'keuangan/laba-rugi', 'financial-reports.read', 5],
            ] as [$route, $name, $url, $permission, $order]) {
                Menu::updateOrCreate(['route' => $route], ['name' => $name, 'url' => $url, 'parent_id' => $parent->id, 'permission' => $permission, 'order' => $order, 'is_active' => true]);
            }
        });
        (new FinanceNavigationSeeder)->run();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
