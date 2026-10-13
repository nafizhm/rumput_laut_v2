<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FinanceNavigationSeeder extends Seeder
{
    public const ROUTES = [
        'coa.index',
        'saldo-awal.index',
        'manajemen-pinjaman.index',
        'harvest-finance.index',
        'manajemen-pemasukan-pengeluaran.index',
        'jurnal.index',
        'cash-flow.index',
        'laba-rugi.index',
        'neraca.index',
    ];

    public function run(): void
    {
        DB::transaction(function () {
            $finance = Menu::whereNull('parent_id')->where('name', 'Keuangan')->first();
            if (! $finance) return;

            foreach (self::ROUTES as $index => $route) {
                Menu::where('parent_id', $finance->id)->where('route', $route)->update(['order' => $index + 1]);
            }
        });
    }
}
