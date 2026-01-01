<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/data-tables', [App\Http\Controllers\TestingController::class, 'dataTable']);
    Route::get('/form', [App\Http\Controllers\TestingController::class, 'form']);
});
