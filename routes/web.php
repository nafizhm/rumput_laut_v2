<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\AuthController::class, 'index'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [App\Http\Controllers\DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/chart-data', [App\Http\Controllers\DashboardController::class, 'chartData'])->name('dashboard.chart-data');

    Route::resource('manajemen-menu', App\Http\Controllers\Setting\MenuController::class);
    Route::get('menu-data', [App\Http\Controllers\Setting\MenuController::class, 'data'])->name('menu-data');

    Route::resource('manajemen-permission', App\Http\Controllers\Setting\PermissionController::class);
    Route::get('permission-data', [App\Http\Controllers\Setting\PermissionController::class, 'data'])->name('permission-data');

    Route::resource('manajemen-role', App\Http\Controllers\Setting\RoleController::class);
    Route::get('role-data', [App\Http\Controllers\Setting\RoleController::class, 'data'])->name('role-data');

    Route::resource('manajemen-petani', App\Http\Controllers\Master\FarmerController::class);
    Route::get('farmer-data', [App\Http\Controllers\Master\FarmerController::class, 'data'])->name('farmer-data');
    Route::get('manajemen-petani/{id}/ledgers', [App\Http\Controllers\Master\FarmerController::class, 'ledgers'])->name('farmer.ledgers');

    Route::resource('manajemen-kategori-pengeluaran', App\Http\Controllers\Master\ExpenseCategoryController::class);
    Route::get('expense-category-data', [App\Http\Controllers\Master\ExpenseCategoryController::class, 'data'])->name('expense-category-data');

    Route::resource('manajemen-pabrik', App\Http\Controllers\Master\FactoryController::class);
    Route::get('factory-data', [App\Http\Controllers\Master\FactoryController::class, 'data'])->name('manajemen-pabrik.data');

    Route::resource('manajemen-inventori', App\Http\Controllers\Master\InventoryController::class);
    Route::get('inventory-data', [App\Http\Controllers\Master\InventoryController::class, 'data'])->name('manajemen-inventori.data');

    Route::resource('manajemen-pinjaman', App\Http\Controllers\Transaction\LoanController::class);
    Route::get('loan-data', [App\Http\Controllers\Transaction\LoanController::class, 'data'])->name('loan-data');

    Route::resource('manajemen-panen', App\Http\Controllers\Transaction\HarvestTransactionController::class);
    Route::get('harvest-data', [App\Http\Controllers\Transaction\HarvestTransactionController::class, 'data'])->name('harvest-data');

    Route::resource('manajemen-pengeluaran', App\Http\Controllers\Transaction\ExpenseController::class);
    Route::get('expense-data', [App\Http\Controllers\Transaction\ExpenseController::class, 'data'])->name('expense-data');

    // Warehouse
    Route::get('manajemen-gudang', [App\Http\Controllers\Transaction\WarehouseController::class, 'index'])->name('manajemen-gudang.index');
    Route::get('manajemen-gudang/basah', [App\Http\Controllers\Transaction\WarehouseController::class, 'wet'])->name('manajemen-gudang.wet');
    Route::get('manajemen-gudang/kering', [App\Http\Controllers\Transaction\WarehouseController::class, 'dry'])->name('manajemen-gudang.dry');
    Route::get('manajemen-gudang/inventori/{inventory}', [App\Http\Controllers\Transaction\WarehouseController::class, 'inventory'])->name('manajemen-gudang.inventory');
    Route::get('warehouse-data', [App\Http\Controllers\Transaction\WarehouseController::class, 'data'])->name('manajemen-gudang.data');
    Route::post('manajemen-gudang/receive', [App\Http\Controllers\Transaction\WarehouseController::class, 'receive'])->name('manajemen-gudang.receive');
    Route::post('manajemen-gudang/shrinkage', [App\Http\Controllers\Transaction\WarehouseController::class, 'shrinkage'])->name('manajemen-gudang.shrinkage');
    Route::post('manajemen-gudang/transfer', [App\Http\Controllers\Transaction\WarehouseController::class, 'transfer'])->name('manajemen-gudang.transfer');
    Route::post('manajemen-gudang/adjustment', [App\Http\Controllers\Transaction\WarehouseController::class, 'adjustment'])->name('manajemen-gudang.adjustment');

    // Sales
    Route::resource('manajemen-penjualan', App\Http\Controllers\Transaction\SalesController::class);
    Route::get('sales-data', [App\Http\Controllers\Transaction\SalesController::class, 'data'])->name('manajemen-penjualan.data');
    Route::post('manajemen-penjualan/{manajemen_penjualan}/pay', [App\Http\Controllers\Transaction\SalesController::class, 'pay'])->name('manajemen-penjualan.pay');

    // Cash Flow
    Route::get('laporan-arus-kas', [App\Http\Controllers\CashFlow\CashFlowController::class, 'index'])->name('cash-flow.index');
    Route::get('laporan-arus-kas/data', [App\Http\Controllers\CashFlow\CashFlowController::class, 'data'])->name('cash-flow.data');

    Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
});

Route::get('/test', function () {
    return view('test');
})->name('test.index');
