<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\TokenMHController;

Route::get('/', function () {
    return redirect()->route('facturas.index');
});

// Rutas de facturas
Route::prefix('facturas')->name('facturas.')->group(function () {
    Route::get('/', [FacturaController::class, 'index'])->name('index');
    Route::get('/crear', [FacturaController::class, 'create'])->name('create');
    Route::get('/crear-ccf', [FacturaController::class, 'createCCF'])->name('create_ccf');
    Route::post('/guardar', [FacturaController::class, 'store'])->name('store');
    Route::get('/{factura}', [FacturaController::class, 'show'])->name('show');
    Route::delete('/{factura}', [FacturaController::class, 'destroy'])->name('destroy');
});

// Rutas de administración de tokens
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/tokens', [TokenMHController::class, 'index'])->name('tokens.index');
    Route::post('/tokens/renovar', [TokenMHController::class, 'renovar'])->name('tokens.renovar');
});