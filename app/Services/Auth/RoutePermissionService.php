<?php

namespace App\Services\Auth;

use App\Models\User;

class RoutePermissionService
{
    public const MODULES = [
        'users' => 'users', 'company-profile' => 'company-profile',
        'manajemen-menu' => 'menu', 'manajemen-permission' => 'permission', 'manajemen-role' => 'role',
        'manajemen-petani' => 'manajemen-petani', 'manajemen-kategori-pengeluaran' => 'manajemen-kategori-pengeluaran',
        'manajemen-pabrik' => 'manajemen-pabrik', 'manajemen-inventori' => 'manajemen-inventori',
        'manajemen-pinjaman' => 'manajemen-pinjaman', 'manajemen-panen' => 'manajemen-panen',
        'manajemen-pemasukan-pengeluaran' => 'manajemen-pemasukan-pengeluaran',
        'manajemen-gudang' => 'manajemen-gudang', 'manajemen-penjualan' => 'manajemen-penjualan',
        'area-gudang' => 'area', 'area-barang-masuk' => 'area', 'area-barang-ready' => 'area',
        'kualitas-barang' => 'quality', 'penerimaan-barang' => 'receiving', 'produksi' => 'production',
        'stok-awal' => 'opening-stock', 'invoice' => 'invoice', 'surat-jalan' => 'surat-jalan',
        'po-konsumen' => 'po-konsumen', 'harvest-finance' => 'harvest-finance',
        'saldo-awal' => 'journal', 'jurnal' => 'journal', 'neraca' => 'financial-reports',
        'laba-rugi' => 'financial-reports', 'coa' => 'coa', 'cash-flow' => 'cash-flow',
        'proses-bisnis' => 'financial-reports',
    ];

    public static function permission(?string $route): ?string
    {
        $aliases = [
            'invoice.payment.destroy' => 'invoice.update',
            'dashboard' => 'dashboard.read', 'dashboard.stats' => 'dashboard.read', 'dashboard.chart-data' => 'dashboard.read',
            'menu-data' => 'menu.read', 'permission-data' => 'permission.read', 'role-data' => 'role.read',
            'farmer-data' => 'manajemen-petani.read', 'farmer.ledgers' => 'manajemen-petani.read',
            'farmer.pay' => 'manajemen-petani.update', 'farmer.pay.destroy' => 'manajemen-petani.delete',
            'expense-category-data' => 'manajemen-kategori-pengeluaran.read', 'loan-data' => 'manajemen-pinjaman.read',
            'harvest-data' => 'manajemen-panen.read', 'transaction-data' => 'manajemen-pemasukan-pengeluaran.read',
            'saldo-awal.start-zero' => 'journal.post', 'penerimaan-barang.link-purchase' => 'receiving.update',
            'produksi.complete' => 'production.confirm', 'produksi.pack' => 'production.confirm',
            'produksi.activity.create' => 'production.create', 'produksi.activity.store' => 'production.create',
            'manajemen-gudang.receive' => 'manajemen-gudang.create',
            'manajemen-gudang.shrinkage' => 'manajemen-gudang.update',
            'manajemen-gudang.transfer' => 'manajemen-gudang.update',
            'manajemen-gudang.adjustment' => 'manajemen-gudang.update',
            'manajemen-gudang.transfer-data' => 'manajemen-gudang.update',
            'manajemen-gudang.adjustment-data' => 'manajemen-gudang.update',
            'manajemen-penjualan.purchase-orders' => 'manajemen-penjualan.create',
            'manajemen-penjualan.pay' => 'manajemen-penjualan.update', 'invoice.pay' => 'invoice.update',
            'surat-jalan.dispatch' => 'surat-jalan.update',
        ];
        if (isset($aliases[$route])) return $aliases[$route];
        [$module, $action] = array_pad(explode('.', $route ?? '', 2), 2, 'index');
        if (! isset(self::MODULES[$module])) return null;
        $action = match ($action) {
            'create', 'store' => 'create', 'edit', 'update' => 'update', 'destroy' => 'delete',
            'confirm', 'price', 'pay', 'post', 'reverse' => $action,
            default => 'read',
        };

        return self::MODULES[$module].'.'.$action;
    }

    public static function canAccess(User $user, ?string $route): bool
    {
        $permission = self::permission($route);

        return $permission !== null && $user->can($permission);
    }

    public static function landingRoute(User $user): string
    {
        foreach (['dashboard', 'manajemen-gudang.index', 'produksi.index', 'stok-awal.index',
            'manajemen-panen.index', 'manajemen-penjualan.index', 'po-konsumen.index', 'surat-jalan.index', 'invoice.index',
            'harvest-finance.index', 'manajemen-pemasukan-pengeluaran.index', 'manajemen-pinjaman.index',
            'cash-flow.index', 'saldo-awal.index', 'jurnal.index', 'neraca.index', 'laba-rugi.index', 'coa.index',
            'manajemen-petani.index', 'manajemen-pabrik.index', 'manajemen-inventori.index',
            'manajemen-kategori-pengeluaran.index', 'area-barang-masuk.index', 'kualitas-barang.index',
            'users.index', 'company-profile.index', 'manajemen-menu.index', 'manajemen-permission.index', 'manajemen-role.index'] as $route) {
            if (self::canAccess($user, $route)) return $route;
        }

        return 'access.empty';
    }
}
