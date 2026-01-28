<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas API para sistema anti-scraping
// Token (sin middleware anti-scraping, solo rate limiting básico)
Route::post('/token', [App\Http\Controllers\Api\TokenController::class, 'generate'])
    ->middleware(['throttle:15,1']); // Rate limit básico para token

// Endpoints protegidos con middleware anti-scraping
// Añadir middlewares de sesión para detectar usuarios autenticados
Route::middleware([
    \App\Http\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    'anti-scraping:ofertas'
])->group(function () {
    Route::get('/ofertas/{productoId}', [App\Http\Controllers\Api\OfertasController::class, 'index']);
    Route::get('/especificaciones/{productoId}', [App\Http\Controllers\Api\EspecificacionesController::class, 'index']);
});

Route::get('/precios-historicos/{productoId}', [App\Http\Controllers\ProductoController::class, 'obtenerPreciosHistoricos'])
    ->middleware([
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        'anti-scraping:historicos'
    ]);
