<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [App\Http\Controllers\Auth\AuthController::class, 'index'])->name('login');
    Route::post('/login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('pages.dashboard.index');
    })->name('dashboard');

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

    Route::resource('manajemen-pinjaman', App\Http\Controllers\Transaction\LoanController::class);
    Route::get('loan-data', [App\Http\Controllers\Transaction\LoanController::class, 'data'])->name('loan-data');

    Route::resource('manajemen-panen', App\Http\Controllers\Transaction\HarvestTransactionController::class);
    Route::get('harvest-data', [App\Http\Controllers\Transaction\HarvestTransactionController::class, 'data'])->name('harvest-data');

    Route::resource('manajemen-pengeluaran', App\Http\Controllers\Transaction\ExpenseController::class);
    Route::get('expense-data', [App\Http\Controllers\Transaction\ExpenseController::class, 'data'])->name('expense-data');

    Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
});

Route::get('/test', function () {
    return view('test');
})->name('test.index');
