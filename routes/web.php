<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\TokenMHController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ContingenciaController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\InvalidacionController;

// Rutas públicas (sin autenticación)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Ruta raíz - redirige a login si no está autenticado
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Session::has('auth_token')) {
        return redirect()->route('facturas.index');
    }
    return redirect()->route('login');
})->name('home');

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth.check'])->group(function () {

    // Rutas de perfil
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [LoginController::class, 'showProfile'])->name('show');
    });

// Rutas de facturas
Route::prefix('facturas')->name('facturas.')->group(function () {
    Route::get('/', [FacturaController::class, 'index'])->name('index');
    Route::get('/crear', [FacturaController::class, 'create'])->name('create');
    Route::get('/crear-ccf', [FacturaController::class, 'createCCF'])->name('create_ccf');
    Route::get('/crear-nota-credito', [FacturaController::class, 'createNotaCredito'])->name('create_nota_credito');
    Route::get('/crear-sujeto-excluido', [FacturaController::class, 'createSujetoExcluido'])->name('create_sujeto_excluido');
    Route::post('/guardar', [FacturaController::class, 'store'])->name('store');
    Route::post('/exportar-excel', [FacturaController::class, 'exportarExcel'])->name('exportar_excel');
    Route::post('/buscar-cliente', [FacturaController::class, 'buscarCliente'])->name('buscar_cliente');
    Route::get('/{factura}', [FacturaController::class, 'show'])->name('show');
    Route::delete('/{factura}', [FacturaController::class, 'destroy'])->name('destroy');
    Route::post('/{factura}/enviar-email', [FacturaController::class, 'enviarEmail'])->name('enviarEmail');
});

// Rutas de administración de tokens
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/tokens', [TokenMHController::class, 'index'])->name('tokens.index');
    Route::post('/tokens/renovar', [TokenMHController::class, 'renovar'])->name('tokens.renovar');
});



Route::prefix('contingencias')->name('contingencias.')->group(function () {
    Route::get('/', [ContingenciaController::class, 'index'])->name('index');
    Route::get('/crear', [ContingenciaController::class, 'create'])->name('create');
    Route::post('/guardar', [ContingenciaController::class, 'store'])->name('store');
    Route::get('/{evento}', [ContingenciaController::class, 'show'])->name('show');
    Route::get('/{evento}/debug', [ContingenciaController::class, 'debug'])->name('debug');
    Route::post('/{evento}/cerrar', [ContingenciaController::class, 'cerrar'])->name('cerrar');
    Route::post('/{evento}/procesar', [ContingenciaController::class, 'procesar'])->name('procesar');
    Route::post('/{evento}/consultar-lote', [ContingenciaController::class, 'consultarLote'])->name('consultar-lote');
});

// Rutas de consultas DTE
Route::prefix('consultas')->name('consultas.')->group(function () {
    Route::get('/', [ConsultaController::class, 'index'])->name('index');
    Route::post('/dte', [ConsultaController::class, 'consultarDTE'])->name('dte');
    Route::post('/buscar', [ConsultaController::class, 'buscarYConsultar'])->name('buscar');
    Route::post('/lote', [ConsultaController::class, 'consultarLote'])->name('lote');
    Route::get('/rapida/{factura}', [ConsultaController::class, 'consultaRapida'])->name('rapida');
});

// Rutas de invalidación


Route::prefix('invalidaciones')->name('invalidaciones.')->group(function () {
    Route::get('/', [InvalidacionController::class, 'index'])->name('index');
    Route::get('/seleccionar', [InvalidacionController::class, 'seleccionarFactura'])->name('seleccionar');
    Route::get('/crear', [InvalidacionController::class, 'create'])->name('create');
    Route::post('/guardar', [InvalidacionController::class, 'store'])->name('store');
    Route::get('/{evento}', [InvalidacionController::class, 'show'])->name('show');
    Route::post('/{evento}/reintentar', [InvalidacionController::class, 'reintentar'])->name('reintentar');
});

// Ruta de logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

}); // Cierre del grupo de rutas protegidas