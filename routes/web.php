<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FacturaController;
use App\Http\Controllers\TokenMHController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ContingenciaController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\InvalidacionController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\LibroVentasController;
use App\Http\Controllers\RecordatorioController;
use App\Http\Controllers\CotizacionController;

// Rutas públicas (sin autenticación)
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);

// Ruta de búsqueda de cliente (sin middleware, el controlador maneja la autenticación)
Route::post('/facturas/buscar-cliente', [FacturaController::class, 'buscarCliente'])->name('buscar_cliente');

// Ruta raíz - redirige a login si no está autenticado
Route::get('/', function () {
    if (\Illuminate\Support\Facades\Session::has('auth_token')) {
        return redirect()->route('home');
    }
    return redirect()->route('login');
});

// Rutas protegidas (requieren autenticación)
Route::middleware(['auth.check'])->group(function () {

    // Dashboard / Inicio
    Route::get('/home', [HomeController::class, 'index'])->name('home');

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
    Route::get('/{factura}', [FacturaController::class, 'show'])->name('show');
    Route::delete('/{factura}', [FacturaController::class, 'destroy'])->name('destroy');
    Route::post('/{factura}/enviar-email', [FacturaController::class, 'enviarEmail'])->name('enviarEmail');
    Route::get('/{factura}/descargar-json', [FacturaController::class, 'descargarJSON'])->name('descargarJSON');
    Route::get('/{factura}/descargar-pdf', [FacturaController::class, 'descargarPdf'])->name('descargarPdf');
    Route::post('/{factura}/enviar-json', [FacturaController::class, 'enviarJSON'])->name('enviarJSON');
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
    Route::post('/{evento}/enviar-email', [InvalidacionController::class, 'enviarEmail'])->name('enviarEmail');
    Route::get('/{evento}/descargar-pdf', [InvalidacionController::class, 'descargarPdf'])->name('descargarPdf');
});

// Rutas de Kardex
Route::prefix('kardex')->name('kardex.')->group(function () {
    Route::get('/', [KardexController::class, 'index'])->name('index');
    Route::post('/exportar', [KardexController::class, 'exportar'])->name('exportar');
    Route::get('/debug-api', [KardexController::class, 'debugApi'])->name('debug_api');
});

// Rutas de Clientes (consumo detallado por cliente)
Route::prefix('clientes')->name('clientes.')->group(function () {
    Route::get('/', [ClienteController::class, 'index'])->name('index');
    Route::get('/{documento}', [ClienteController::class, 'show'])->where('documento', '.*')->name('show');
});

// Ruta de Libro de Ventas (reporte mensual de IVA)
Route::prefix('libro-ventas')->name('libro_ventas.')->group(function () {
    Route::get('/', [LibroVentasController::class, 'index'])->name('index');
    Route::get('/exportar', [LibroVentasController::class, 'exportar'])->name('exportar');
});

// Ruta de Recordatorios de Mantenimiento
Route::get('/recordatorios', [RecordatorioController::class, 'index'])->name('recordatorios.index');

// Rutas de Cotizaciones
Route::prefix('cotizaciones')->name('cotizaciones.')->group(function () {
    Route::get('/', [CotizacionController::class, 'index'])->name('index');
    Route::get('/crear', [CotizacionController::class, 'create'])->name('create');
    Route::post('/guardar', [CotizacionController::class, 'store'])->name('store');
    Route::get('/{cotizacion}', [CotizacionController::class, 'show'])->name('show');
    Route::post('/{cotizacion}/estado', [CotizacionController::class, 'cambiarEstado'])->name('cambiarEstado');
    Route::post('/{cotizacion}/convertir', [CotizacionController::class, 'convertirAFactura'])->name('convertir');
    Route::delete('/{cotizacion}', [CotizacionController::class, 'destroy'])->name('destroy');
});

// Ruta de logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

}); // Cierre del grupo de rutas protegidas