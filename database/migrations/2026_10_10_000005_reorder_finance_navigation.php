<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function reorder(array $routes): void
    {
        DB::transaction(function () use ($routes) {
            $finance = DB::table('menus')->whereNull('parent_id')->where('name', 'Keuangan')->first();
            if (! $finance) return;
            foreach ($routes as $index => $route) {
                DB::table('menus')->where('parent_id', $finance->id)->where('route', $route)->update(['order' => $index + 1]);
            }
        });
    }

    public function up(): void
    {
        $this->reorder([
            'manajemen-pemasukan-pengeluaran.index', 'manajemen-pinjaman.index', 'jurnal.index',
            'cash-flow.index', 'laba-rugi.index', 'neraca.index', 'coa.index', 'saldo-awal.index',
        ]);
    }

    public function down(): void
    {
        $this->reorder([
            'manajemen-pinjaman.index', 'cash-flow.index', 'coa.index',
            'manajemen-pemasukan-pengeluaran.index', 'jurnal.index', 'saldo-awal.index', 'neraca.index', 'laba-rugi.index',
        ]);
    }
};
