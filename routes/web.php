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
    Route::get('/dashboard', [App\Http\Controllers\TestingController::class, 'dashboard'])->name('dashboard');
    Route::get('/data-table', [App\Http\Controllers\TestingController::class, 'dataTable'])->name('data-table.index');
    Route::get('/form', [App\Http\Controllers\TestingController::class, 'form'])->name('form.index');

    Route::resource('manajemen-menu', App\Http\Controllers\Setting\MenuController::class);
    Route::get('menu-data', [App\Http\Controllers\Setting\MenuController::class, 'data'])->name('menu-data.index');
    Route::resource('manajemen-permission', App\Http\Controllers\Setting\PermissionController::class);
    Route::get('permission-data', [App\Http\Controllers\Setting\PermissionController::class, 'data'])->name('permission-data.index');
    Route::resource('manajemen-role', App\Http\Controllers\Setting\RoleController::class);

    Route::post('/logout', [App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('logout');
});

Route::get('/test', function () {
    return view('test');
})->name('test.index');
