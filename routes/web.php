<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\OfertaProductoController;
use App\Http\Controllers\BuscadorController;
use App\Http\Controllers\TiendaController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\AvisoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CholloController;
use App\Http\Controllers\ClickController;
use App\Http\Controllers\CholloPublicController;
use App\Http\Controllers\Admin\ImagenController;
use App\Http\Controllers\PruebasController;
use App\Models\Producto;
use App\Models\Categoria;
use App\Models\EjecucionHistoricoPrecioProducto;
use Carbon\Carbon;
use App\Models\HistoricoPrecioProducto;
use Illuminate\Support\Facades\RateLimiter;
use App\Services\SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos;

require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// RUTAS PÚBLICAS CON HEADERS DE SEGURIDAD Y BLOQUEO DE DELETE (excluyendo crons)
// Este grupo incluye todas las rutas públicas hasta la ruta dinámica /{categorias}/{slug} (línea 1060)
Route::middleware(['security.headers', 'block.public.deletes'])->group(function () {
    Route::get('/', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
    // Ruta que lleva a la vista: resources/views/chollos/listado_chollos.blade.php
    //Route::get('/chollos', [CholloController::class, 'listadoPublico'])->name('chollos.listado');
    // Ruta que lleva a la vista: resources/views/chollos/vistaChollo.blade.php
    Route::get('/chollo/{slug}', [CholloController::class, 'show'])->name('chollos.show');

    // SITEMAPS
    Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index']);
    Route::get('/sitemap-categorias.xml', [App\Http\Controllers\SitemapController::class, 'categorias']);
    Route::get('/sitemap-productos.xml', [App\Http\Controllers\SitemapController::class, 'productos']);

    //GENERAR CONTENIDO DE PRODUCTOS AUTOMATICAMENTE CON CHATGPT - LO PONGO FUERA PORQUE SI LO DEJO DENTRO DEL AUTH NO FUNCIONA
    Route::middleware('throttle:4,1')->group(function () {
        Route::post('productos/generar-contenido', [ProductoController::class, 'generarContenido'])->name('productos.generar-contenido');
    });

    // RUTAS PARA POLÍTICAS Y LEGAL
    Route::prefix('politicas')->name('politicas.')->group(function () {
        Route::get('/contacto', function () {
            return view('politicas.contacto');
        })->name('contacto');
        
        Route::get('/aviso-legal', function () {
            return view('politicas.aviso-legal');
        })->name('aviso-legal');
        
        Route::get('/privacidad', function () {
            return view('politicas.politica-privacidad');
        })->name('privacidad');
        
        Route::get('/cookies', function () {
            return view('politicas.politica-cookies');
        })->name('cookies');
    });

    //PARA REDIRIGIR AL USUARIO A LA TIENDA - AQUI CARGA LA PAGINA INTERMEDIA
    Route::get('/redirigir/{ofertaId}', [ClickController::class, 'redirigir'])->name('click.redirigir');

    Route::middleware('throttle:30,1')->group(function () {
        // BÚSQUEDA PÚBLICA
        Route::get('/buscar', [BuscadorController::class, 'buscar'])->name('buscar');
        Route::get('/api/buscar-productos', [BuscadorController::class, 'productos'])->name('api.buscar.productos');
    });
    
    // RUTA API PARA OBTENER SUBCATEGORÍAS (con rate limit aumentado)
    Route::middleware(['security.headers', 'block.public.deletes', 'throttle:30,1'])->get('/api/categorias/{id}/subcategorias', function($id) {
        $categoria = \App\Models\Categoria::with(['subcategorias' => function($query) {
            $query->with(['subcategorias' => function($q) {
                $q->orderBy('nombre');
            }])->orderBy('nombre');
        }])->find($id);
        
        if (!$categoria) {
            return response()->json([], 404);
        }
        
        return response()->json($categoria->subcategorias->map(function($sub) {
            return [
                'id' => $sub->id,
                'nombre' => $sub->nombre,
                'slug' => $sub->slug,
                'subcategorias' => $sub->subcategorias->map(function($subsub) {
                    return [
                        'id' => $subsub->id,
                        'nombre' => $subsub->nombre,
                        'slug' => $subsub->slug,
                        'subcategorias' => []
                    ];
                })
            ];
        }));
    })->name('api.categorias.subcategorias');
    
    // RUTA DINÁMICA PARA PRODUCTOS CON MÚLTIPLES CATEGORÍAS (DEBE IR AL FINAL DEL GRUPO)
    // Esta ruta renderiza comparador.unidades.blade.php
    // NOTA: El cierre del grupo está después de esta ruta (línea ~1064)

// Scraper de ofertas en segundo plano
Route::get('ofertas/scraper/ejecutar-segundo-plano', function (Request $request) {
    if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
        abort(403, 'Token inválido');
    }
    return app(\App\Http\Controllers\OfertaProductoController::class)->ejecutarScraperOfertasSegundoPlano($request);
});

// Actualizar primera oferta (para cron jobs) en segundo plano
    Route::get('actualizar-primera-oferta/ejecutar-segundo-plano', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class)->ejecutarSegundoPlano($request);
    });

// Comprobar chollos y ofertas finalizadas (para cron jobs) en segundo plano
Route::get('chollos/comprobar-finalizados/ejecutar-segundo-plano', function (Request $request) {
    if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
        abort(403, 'Token inválido');
    }
    return app(\App\Http\Controllers\CholloController::class)->comprobarChollosYOfertasFinalizadas($request);
});

// Rutas para scraping de ofertas
Route::middleware(['security.headers', 'block.public.deletes'])->prefix('scraping')->name('scraping.')->group(function () {
    Route::post('/obtener-precio', [App\Http\Controllers\Scraping\ScrapingController::class, 'obtenerPrecio'])->name('obtener-precio');
});

// Rutas para alertas de precio
Route::middleware(['security.headers', 'block.public.deletes', 'throttle:2,1'])->prefix('alertas')->name('alertas.')->group(function () {
    Route::post('/guardar', [App\Http\Controllers\AlertaPrecioController::class, 'guardarAlerta'])->name('guardar');
});

// Rutas para cancelar alertas (excepción: permite DELETE porque usa token único)
Route::middleware(['security.headers', 'block.public.deletes'])->group(function () {
    Route::get('/cancelar-alerta/{token}', [App\Http\Controllers\AlertaPrecioController::class, 'mostrarCancelarAlerta'])->name('alertas.cancelar');
    Route::post('/cancelar-alerta', [App\Http\Controllers\AlertaPrecioController::class, 'cancelarAlerta'])->name('alertas.cancelar-procesar');
});

// Ruta para enviar alertas manualmente (solo para administradores)
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/avisos/info-alertas', [App\Http\Controllers\AvisoController::class, 'obtenerInfoAlertasProducto'])->name('avisos.info-alertas');
    Route::post('/avisos/enviar-alertas', [App\Http\Controllers\AvisoController::class, 'enviarAlertasProducto'])->name('avisos.enviar-alertas');
});


// RUTA PARA CATEGORÍAS INDIVIDUALES (MÁS ESPECÍFICA) - Con filtros opcionales y rate limiting
Route::middleware(['security.headers', 'block.public.deletes', 'throttle:40,1'])->group(function () {
    Route::get('/categoria/{slug}/{filtros?}', [App\Http\Controllers\HomeController::class, 'showCategoria'])
        ->where('filtros', '.*')
        ->name('categoria.show');
});

// Rutas para testing de scraping (solo admin)
Route::middleware(['auth', 'verified'])->prefix('admin/scraping')->name('admin.scraping.')->group(function () {
    // Vistas
    Route::get('/ejecucion-tiempo-real', [App\Http\Controllers\Scraping\EjecucionTiempoRealController::class, 'index'])->name('ejecucion-tiempo-real');
    
    Route::get('/test', [App\Http\Controllers\Scraping\TestController::class, 'index'])->name('test');
    Route::get('/test-precio', [App\Http\Controllers\Scraping\TestPrecioController::class, 'index'])->name('test.precio');
    Route::get('/diagnostico', [App\Http\Controllers\Scraping\DiagnosticoController::class, 'index'])->name('diagnostico');
    
    // Rutas para Verificar URLs
    Route::get('/verificar-urls', [App\Http\Controllers\Scraping\VerificarUrlsController::class, 'index'])->name('verificar-urls');
    Route::post('/verificar-urls/procesar', [App\Http\Controllers\Scraping\VerificarUrlsController::class, 'verificarUrls'])->name('verificar-urls.procesar');
    
    // Rutas para Comprobar Ofertas API
    Route::get('/comprobar-ofertas-api/test-bulk', [App\Http\Controllers\Scraping\ComprobarOfertasApiController::class, 'test'])->name('comprobar-ofertas-api.test-bulk');
    Route::post('/comprobar-ofertas-api/buscar', [App\Http\Controllers\Scraping\ComprobarOfertasApiController::class, 'buscarOfertas'])->name('comprobar-ofertas-api.buscar');
    Route::post('/comprobar-ofertas-api/procesar', [App\Http\Controllers\Scraping\ComprobarOfertasApiController::class, 'procesarOferta'])->name('comprobar-ofertas-api.procesar');
    Route::post('/comprobar-ofertas-api/guardar-precio', [App\Http\Controllers\Scraping\ComprobarOfertasApiController::class, 'guardarPrecio'])->name('comprobar-ofertas-api.guardar-precio');
    Route::post('/comprobar-ofertas-api/avisos', [App\Http\Controllers\Scraping\ComprobarOfertasApiController::class, 'obtenerAvisosOferta'])->name('comprobar-ofertas-api.avisos');
    
    // Rutas para Actualizar Primera Oferta
    Route::get('/actualizar-primera-oferta', [App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class, 'index'])->name('actualizar-primera-oferta.index');
    Route::get('/actualizar-primera-oferta/historial', [App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class, 'historialEjecuciones'])->name('actualizar-primera-oferta.historial');
    Route::get('/actualizar-primera-oferta/detalles/{id}', [App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class, 'obtenerDetallesEjecucion'])->name('actualizar-primera-oferta.detalles');
    Route::get('/actualizar-primera-oferta/errores-tienda', [App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class, 'obtenerErroresPorTienda'])->name('actualizar-primera-oferta.errores.tienda');
    
    // APIs
    Route::post('/test/procesar', [App\Http\Controllers\Scraping\TestController::class, 'procesarUrl'])->name('test.procesar');
    Route::post('/test-precio/procesar', [App\Http\Controllers\Scraping\TestPrecioController::class, 'procesarUrl'])->name('test.precio.procesar');
    Route::get('/ejecucion-tiempo-real/iniciar', [App\Http\Controllers\Scraping\EjecucionTiempoRealController::class, 'iniciar'])->name('ejecucion-tiempo-real.iniciar');
    Route::post('/ejecucion-tiempo-real/procesar-siguiente', [App\Http\Controllers\Scraping\EjecucionTiempoRealController::class, 'procesarSiguiente'])->name('ejecucion-tiempo-real.procesar-siguiente');
    Route::get('/ejecucion-tiempo-real/estado', [App\Http\Controllers\Scraping\EjecucionTiempoRealController::class, 'obtenerEstado'])->name('ejecucion-tiempo-real.estado');
    Route::post('/ejecucion-tiempo-real/marcar-completada', [App\Http\Controllers\Scraping\EjecucionTiempoRealController::class, 'marcarCompletada'])->name('ejecucion-tiempo-real.marcar-completada');
    
    // APIs para Actualizar Primera Oferta
    Route::post('/actualizar-primera-oferta/iniciar', [App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class, 'iniciar'])->name('actualizar-primera-oferta.iniciar');
    Route::post('/actualizar-primera-oferta/procesar', [App\Http\Controllers\Scraping\ActualizarPrimeraOfertaController::class, 'procesarSiguiente'])->name('actualizar-primera-oferta.procesar');
    
    Route::get('/ofertas-errores-exitos', [App\Http\Controllers\Scraping\DiagnosticoController::class, 'ofertasErroresExitos'])->name('ofertas-errores-exitos');
    Route::get('/test-api', [App\Http\Controllers\Scraping\TestController::class, 'test'])->name('test.api');
    Route::get('/test-error', [App\Http\Controllers\Scraping\TestController::class, 'testError'])->name('test.error');
});


// RUTAS PARA CRON (sin autenticación, protegidas por token)
Route::prefix('admin')->group(function () {
    // Actualizar clicks de ofertas
    Route::get('clicks/actualizar-ofertas', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\OfertaProductoController::class)->actualizarClicksOfertas();
    });

    // Guardar histórico de precios de productos
    Route::get('historico/guardar-productos', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\ProductoController::class)->guardarHistoricoPrecios();
    });

    // Actualizar oferta más barata de cada producto
    Route::get('productos/actualizar-oferta-mas-barata', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\ProductoController::class)->actualizarOfertaMasBarataPorProducto($request);
    });

    // Calcular precios hot
    // Calcula tambien precio producto
    // Calcula tambien cada sublinea de cada producto marcada como mostrar, busca las ofertas que convan con cada sublinea
    // y guarda en el campo especificaciones_busqueda el precio de la oferta más barata..
    Route::get('precios-hot/calcular', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\ProductoController::class)->calcularPreciosHot();
    });

    // Actualizar clicks de categorías
    Route::get('categorias/actualizar-clicks/procesar', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\CategoriaClicksController::class)->procesar();
    });

    // Actualizar clicks de productos
    Route::get('productos/actualizar-clicks/procesar', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\ProductoController::class)->actualizarClicks();
    });

    // Histórico de precios de ofertas
    Route::get('ofertas/historico-precios/ejecutar', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\OfertaProductoController::class)->ejecutarHistoricoPrecios();
    });

    // RUTAS PARA PRECIOS HOT
    // Ejecución en segundo plano (para cron jobs)
    Route::get('precios-hot/ejecutar-segundo-plano', [App\Http\Controllers\PrecioHotController::class, 'ejecutarSegundoPlano'])->name('precios-hot.ejecutar.segundo-plano');

    // Procesar geolocalización de clicks pendientes
    Route::get('clicks/procesar-geolocalizacion', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        return app(\App\Http\Controllers\ClickController::class)->procesarGeolocalizacion($request);
    });

    // Actualizar contador de ofertas por especificaciones internas
    Route::get('ofertas/actualizar-contador-especificaciones', function (Request $request) {
        if ($request->get('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }
        Artisan::call('ofertas:actualizar-contador-especificaciones');
        return response()->json([
            'status' => 'ok',
            'message' => 'Contador de ofertas por especificaciones actualizado correctamente'
        ]);
    });
    
});

// PARA PANEL ADMIN - NUEVO GRUPO CON MIDDLEWARE
Route::middleware(['web', 'auth', 'ensure_session'])->prefix('panel-privado')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('productos', ProductoController::class)->except(['destroy']); // destroy comentado por seguridad
    Route::resource('chollos', CholloController::class)->except(['show', 'destroy']); // destroy comentado por seguridad
    Route::post('chollos/verificar-url', [CholloController::class, 'verificarUrl'])->name('chollos.verificar.url');
    Route::get('chollos/comprobar', [CholloController::class, 'comprobarChollos'])->name('chollos.comprobar');
    Route::post('chollos/ofertas/{oferta}/marcar-comprobada', [CholloController::class, 'marcarComprobada'])->name('chollos.ofertas.marcar-comprobada');
    Route::post('chollos/ofertas/ocultar-multiples', [CholloController::class, 'ocultarMultiples'])->name('chollos.ofertas.ocultar-multiples');
    Route::post('chollos/{chollo}/aplicar-fechas-cupones', [CholloController::class, 'aplicarFechasYCupones'])->name('chollos.aplicar.fechas-cupones');
    Route::post('productos/verificar-slug', [ProductoController::class, 'verificarSlugExistente'])->name('productos.verificar.slug');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    //PARA LA GRAFICA DE CLICK EN PRODUCTO
    Route::get('productos/{producto}/estadisticas/clicks', [ProductoController::class, 'datosClicks'])->name('productos.estadisticas.clicks');
    //PANEL ESTADISTICAS DE CLICKS PARA PRODUCTO
    Route::get('productos/{producto}/estadisticas-avanzadas', [ClickController::class, 'estadisticasAvanzadas'])
        ->name('productos.estadisticas.avanzadas');
    Route::get('productos/{producto}/clicks/rango-precio', [ClickController::class, 'rangoPrecio']);
    Route::get('productos/{producto}/clicks/por-hora', [ClickController::class, 'porHora']);
    Route::get('productos/{producto}/clicks/tiendas', [ClickController::class, 'tiendas']);
    Route::get('clics', [ClickController::class, 'index'])->name('clics.index');
    
    // DASHBOARD DE CLICKS
    Route::get('clicks/dashboard', [ClickController::class, 'dashboard'])->name('clicks.dashboard');
    Route::get('clicks/posiciones-tienda', [ClickController::class, 'posicionesTienda'])->name('clicks.posiciones-tienda');
    Route::get('clicks/verificar-posiciones/{productoId}', [ClickController::class, 'verificarPosiciones'])->name('clicks.verificar-posiciones');
    Route::delete('clicks/{id}', [ClickController::class, 'destroy'])->name('clicks.destroy');
    Route::post('clicks/regeolocalizar-ip', [ClickController::class, 'regeolocalizarIP'])->name('clicks.regeolocalizar-ip');



    // OFERTAS - LISTADOS Y FORMULARIOS

    // Ruta para ver todas las ofertas (sin producto asociado)
    Route::get('ofertas', [OfertaProductoController::class, 'todas'])->name('ofertas.todas');

    // Ruta para crear una oferta sin producto
    Route::get('ofertas/create', [OfertaProductoController::class, 'createGeneral'])->name('ofertas.create.formularioGeneral');

    // Ruta para ver las ofertas de un producto concreto
    Route::get('productos/{producto}/ofertas', [OfertaProductoController::class, 'index'])->name('ofertas.index');

    // Formulario para crear una nueva oferta para un producto concreto
    Route::get('productos/{producto}/ofertas/create', [OfertaProductoController::class, 'create'])->name('ofertas.create');

    // Envía el formulario de creación de una nueva oferta
    Route::post('ofertas', [OfertaProductoController::class, 'store'])->name('ofertas.store');

    // Formulario para editar una oferta ya existente
    Route::get('ofertas/{oferta}/edit', [OfertaProductoController::class, 'edit'])->name('ofertas.edit');

    // Envía el formulario de edición de una oferta
    Route::put('ofertas/{oferta}', [OfertaProductoController::class, 'update'])->name('ofertas.update');

    // Elimina una oferta concreta - COMENTADO POR SEGURIDAD
    // Route::delete('ofertas/{oferta}', [OfertaProductoController::class, 'destroy'])->name('ofertas.destroy');

    // Actualiza solo el campo mostrar de una oferta
    Route::put('ofertas/{oferta}/mostrar', [OfertaProductoController::class, 'actualizarMostrar'])->name('ofertas.mostrar');
    
    // Obtener historial de tiempos de actualización de precios
    Route::get('ofertas/{oferta}/historial-tiempos-actualizacion', [OfertaProductoController::class, 'historialTiemposActualizacion'])->name('ofertas.historial.tiempos-actualizacion');
    
    // Historial global de tiempos de actualización de precios
    Route::get('ofertas/historico-tiempos-actualizacion', [OfertaProductoController::class, 'historialTiemposActualizacionGlobal'])->name('ofertas.historico.tiempos-actualizacion');

    // BÚSQUEDA INTERACTIVA PARA FORMULARIOS (no eliminar)

    // Devuelve listado JSON de productos para el selector
    Route::get('buscador-producto', [BuscadorController::class, 'productos']);

    // Devuelve listado JSON de tiendas para el selector
    Route::get('buscador-tienda', [BuscadorController::class, 'tiendas']);

    //TIENDAS

    Route::get('tiendas', [TiendaController::class, 'index'])->name('tiendas.index');

    Route::get('tiendas/create', [TiendaController::class, 'create'])->name('tiendas.create');

    Route::get('tiendas/{tienda}/edit', [TiendaController::class, 'edit'])->name('tiendas.edit');

    Route::post('tiendas', [TiendaController::class, 'store'])->name('tiendas.store');

    Route::put('tiendas/{tienda}', [TiendaController::class, 'update'])->name('tiendas.update');

    // Route::delete('tiendas/{tienda}', [TiendaController::class, 'destroy'])->name('tiendas.destroy'); // COMENTADO POR SEGURIDAD

    //Listar ofertas de una tienda en especifico
    Route::get('tiendas/{tienda}/ofertas', [TiendaController::class, 'ofertas'])->name('tiendas.ofertas');
    
    // Gestión de tiempos de actualización de ofertas por tienda (DEBE IR ANTES de la ruta dinámica {tienda})
    Route::get('tiendas/tiempos-actualizacion', [TiendaController::class, 'tiemposActualizacion'])->name('tiendas.tiempos-actualizacion');
    Route::get('tiendas/{tienda}/desglose-tiempos', [TiendaController::class, 'obtenerDesgloseTiempos'])->name('tiendas.desglose-tiempos');
    Route::post('tiendas/{tienda}/actualizar-tiempos', [TiendaController::class, 'actualizarTiempos'])->name('tiendas.actualizar-tiempos');
    
    // Obtener tienda por ID (JSON) - DEBE IR AL FINAL para que no capture rutas más específicas
    Route::get('tiendas/{tienda}', [TiendaController::class, 'obtener'])->name('tiendas.obtener');
    
    // Reorganizar update_at de ofertas
    Route::get('ofertas/reorganizar-update-at', [OfertaProductoController::class, 'reorganizarUpdateAt'])->name('ofertas.reorganizar.update-at');
    Route::post('ofertas/reorganizar-update-at/ejecutar', [OfertaProductoController::class, 'ejecutarReorganizarUpdateAt'])->name('ofertas.reorganizar.update-at.ejecutar');
    Route::post('ofertas/reorganizar-update-at/distribucion', [OfertaProductoController::class, 'obtenerDistribucionOfertas'])->name('ofertas.reorganizar.update-at.distribucion');
    Route::post('ofertas/reorganizar-update-at/distribucion-despues', [OfertaProductoController::class, 'obtenerDistribucionDespues'])->name('ofertas.reorganizar.update-at.distribucion-despues');

    //CATEGORIAS SUBCATEGORIAS SUBSUBCATEGORIAS
    Route::get('categorias/{parentId}/subcategorias', [CategoriaController::class, 'subcategorias'])
        ->name('categorias.subcategorias');
    Route::get('categorias/{categoriaId}/jerarquia', [CategoriaController::class, 'jerarquia'])
        ->name('categorias.jerarquia');
    
    Route::get('categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::get('categorias/create', [CategoriaController::class, 'create'])->name('categorias.create');
    Route::post('categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::get('categorias/{categoria}/edit', [CategoriaController::class, 'edit'])->name('categorias.edit');
    Route::put('categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    
    // Ruta deshabilitada para evitar eliminaciones accidentales de categorías
    // Route::delete('categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');
    Route::post('categorias/{categoria}/editar-nombre', [CategoriaController::class, 'updateNombre'])->name('categorias.updateNombre');
    Route::post('categorias/verificar-slug', [CategoriaController::class, 'verificarSlug'])->name('categorias.verificar-slug');
    Route::get('categorias/{categoriaId}/info-chatgpt', [CategoriaController::class, 'obtenerInfoChatgpt'])->name('categorias.info-chatgpt');

    //Historico de productos
    Route::get('productos/{producto}/estadisticas', [ProductoController::class, 'estadisticas'])->name('productos.estadisticas');
    Route::get('productos/{producto}/estadisticas/datos', [ProductoController::class, 'datosHistorico'])->name('productos.estadisticas.datos');
    Route::get('productos/{producto}/estadisticas/info', [ProductoController::class, 'estadisticasInfo'])->name('productos.estadisticas.info');
    Route::post('productos/{producto}/ocultar-precio-elevado', [ProductoController::class, 'ocultarOfertasPrecioElevado'])->name('productos.ocultar.precio.elevado');

    // ACTUALIZACIÓN DE CLICKS DE PRODUCTOS
    Route::get('productos/actualizar-clicks/ejecutar', [ProductoController::class, 'ejecucionActualizarClicks'])->name('productos.actualizar.clicks.ejecutar');
    Route::post('productos/actualizar-clicks/procesar', [ProductoController::class, 'actualizarClicks'])->name('productos.actualizar.clicks.procesar');
    Route::get('productos/actualizar-clicks/ejecuciones', [ProductoController::class, 'indexEjecucionesClicks'])->name('productos.actualizar.clicks.ejecuciones');

    // GUARDAR PRECIO MÁS BAJO DE PRODUCTOS
    Route::get('productos/precio-bajo/ejecutar', [ProductoController::class, 'ejecucionPrecioBajo'])->name('productos.precio-bajo.ejecutar');
    Route::post('productos/precio-bajo/procesar', [ProductoController::class, 'procesarPrecioBajo'])->name('productos.precio-bajo.procesar');

    // ACTUALIZAR OFERTA MÁS BARATA POR PRODUCTO
    Route::get('productos/oferta-mas-barata/ejecutar', [ProductoController::class, 'ejecucionOfertaMasBarata'])->name('productos.oferta-mas-barata.ejecutar');
    Route::post('productos/oferta-mas-barata/procesar', [ProductoController::class, 'procesarOfertaMasBarata'])->name('productos.oferta-mas-barata.procesar');

    // GESTIÓN DE IMÁGENES
    Route::middleware('verificar.imagenes')->group(function () {
        Route::post('imagenes/subir', [App\Http\Controllers\ImagenController::class, 'subir'])->name('imagenes.subir');
        Route::post('imagenes/subir-simple', [App\Http\Controllers\ImagenController::class, 'subirSimple'])->name('imagenes.subir-simple');
        Route::get('imagenes/listar', [App\Http\Controllers\ImagenController::class, 'listar'])->name('imagenes.listar');
        Route::delete('imagenes/eliminar', [App\Http\Controllers\ImagenController::class, 'eliminar'])->name('imagenes.eliminar');
        Route::get('imagenes/carpetas', [App\Http\Controllers\ImagenController::class, 'carpetasDisponibles'])->name('imagenes.carpetas');
        Route::get('imagenes/proxy', [App\Http\Controllers\ImagenController::class, 'servirImagenProxy'])->name('imagenes.proxy');
        Route::post('imagenes/descargar-url', [App\Http\Controllers\ImagenController::class, 'descargarDesdeUrl'])->name('imagenes.descargar-url');
        Route::post('imagenes/procesar-recorte', [App\Http\Controllers\ImagenController::class, 'procesarRecorte'])->name('imagenes.procesar-recorte');
    });
    Route::get('productos/actualizar-clicks/ejecuciones/{id}/json', [ProductoController::class, 'obtenerJsonEjecucionClicks'])->name('productos.actualizar.clicks.ejecucion.json');
    Route::delete('productos/actualizar-clicks/ejecuciones/{id}', [ProductoController::class, 'eliminarEjecucionClicks'])->name('productos.actualizar.clicks.ejecucion.eliminar');

    // ACTUALIZACIÓN DE CLICKS DE CATEGORÍAS
    Route::get('categorias/actualizar-clicks/ejecutar', [App\Http\Controllers\CategoriaClicksController::class, 'ejecutar'])->name('categorias.actualizar.clicks.ejecutar');
    Route::post('categorias/actualizar-clicks/procesar', [App\Http\Controllers\CategoriaClicksController::class, 'procesar'])->name('categorias.actualizar.clicks.procesar');
    Route::get('categorias/actualizar-clicks/ejecuciones', [App\Http\Controllers\CategoriaClicksController::class, 'ejecuciones'])->name('categorias.actualizar.clicks.ejecuciones');

    // ACTUALIZACIÓN DE CLICKS DE OFERTAS
    Route::get('ofertas/actualizar-clicks/ejecutar', [OfertaProductoController::class, 'ejecutarActualizarClicksOfertas'])->name('ofertas.actualizar.clicks.ejecutar');
    Route::post('ofertas/actualizar-clicks/procesar', [OfertaProductoController::class, 'procesarClicksOfertas'])->name('ofertas.actualizar.clicks.procesar');
    Route::get('ofertas/actualizar-clicks/ejecuciones', [OfertaProductoController::class, 'ejecucionesClicksOfertas'])->name('ofertas.actualizar.clicks.ejecuciones');
    Route::get('productos/productos/guardadohistoricoprecio', [ProductoController::class, 'indexEjecucionesHistorico'])
        ->name('productos.historico.ejecuciones');
    Route::get('productos/historico-precios/ejecuciones/{id}/log', function ($id) {
        $ejecucion = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_productos')
            ->findOrFail($id);
        return response()->json([
            'log' => $ejecucion->log ?? [],
        ]);
    })->name('productos.historico.ejecucion.log');
    Route::get('productos/historico-precios/lista', function () {
        $productos = Producto::select('id', 'nombre', 'precio')->get();

        return response()->json([
            'productos' => $productos,
        ]);
    })->name('productos.historico.lista');
    Route::get('productos/historico-precios/ejecutar/ver', function () {
        return view('admin.productos.ejecucionGuardadoPrecios');
    })->name('precios.actualizar.ver');
    Route::post('productos/historico-precios/ejecuciones/eliminar-antiguas', [ProductoController::class, 'eliminarAntiguas'])->name('ejecuciones.eliminar.antiguas');
    Route::delete('productos/historico-precios/ejecuciones/{ejecucion}', [ProductoController::class, 'eliminar'])->name('ejecuciones.eliminar');
    Route::get('productos/{producto}/historial-mes', [ProductoController::class, 'historialMes'])->name('productos.historial.mes');
    Route::post('productos/{producto}/historial-guardar', [ProductoController::class, 'historialGuardar'])->name('productos.historial.guardar');
    Route::post('productos/buscar-relacionados', [ProductoController::class, 'buscarRelacionados']);
    Route::get('productos/buscar/categorias', [ProductoController::class, 'buscarCategorias'])->name('admin.productos.buscar.categorias');
    Route::get('productos/categoria/{categoria}/especificaciones-internas', [ProductoController::class, 'obtenerEspecificacionesInternas'])->name('admin.productos.categoria.especificaciones-internas');
    Route::get('productos/categoria/{categoria}/palabras-clave-relacionadas', [ProductoController::class, 'obtenerPalabrasClaveRelacionadas'])->name('admin.productos.categoria.palabras-clave-relacionadas');
    Route::get('productos/categoria/{categoria}/palabra-clave/{palabraClave}/productos', [ProductoController::class, 'obtenerProductosPorPalabraClave'])->name('admin.productos.categoria.palabra-clave.productos');
    Route::get('productos/{producto}', [ProductoController::class, 'obtenerProducto'])->name('admin.productos.obtener');
    Route::post('productos/{producto}/grupos-ofertas/actualizar', [ProductoController::class, 'actualizarGruposOfertas'])->name('admin.productos.grupos-ofertas.actualizar');
    
    // Obtener ofertas de un producto para gestión de grupos
    Route::get('ofertas/producto/{productoId}', [OfertaProductoController::class, 'obtenerOfertasPorProducto'])->name('admin.ofertas.por-producto');

    //Historico de ofertas

    Route::get('ofertas/{oferta}/estadisticas', [OfertaProductoController::class, 'estadisticas'])->name('ofertas.estadisticas');
    Route::get('ofertas/{oferta}/estadisticas/datos', [OfertaProductoController::class, 'estadisticasDatos'])->name('ofertas.estadisticas.datos');
    Route::get('ofertas/{oferta}/estadisticas/info', [OfertaProductoController::class, 'estadisticasInfo'])->name('ofertas.estadisticas.info');
    Route::post('ofertas/{oferta}/ocultar-precio-elevado', [OfertaProductoController::class, 'ocultarOfertaPrecioElevado'])->name('ofertas.ocultar.precio.elevado');
    Route::get('ofertas/detectar-precio-elevado', [OfertaProductoController::class, 'detectarOfertasPrecioElevado'])->name('ofertas.detectar.precio.elevado');
    Route::post('ofertas/detectar-precio-elevado', [OfertaProductoController::class, 'procesarDetectarOfertasPrecioElevado'])->name('ofertas.detectar.precio.elevado');
    // Ver modal de ejecución en tiempo real
    Route::get('ofertas/historico-precios/ejecutar/ver', function () {
        return view('admin.ofertas.ejecucionGuardadoPrecios');
    })->name('ofertas.historico.ver');
    //////// Lista de todas las ofertas a procesar
    Route::get('ofertas/historico-precios/lista', [OfertaProductoController::class, 'listaOfertas'])->name('ofertas.historico.lista');
    //////// Guardar resultado de la ejecución
    Route::post('ofertas/historico-precios/finalizar', [OfertaProductoController::class, 'finalizarEjecucion'])->name('ofertas.historico.finalizar');
    //////// Procesar una oferta individual
    Route::post('ofertas/historico-precios/procesar', [OfertaProductoController::class, 'procesarOferta'])->name('ofertas.historico.procesar');
    //////// Ver historial de ejecuciones
    Route::get('ofertas/historico-precios/ejecuciones', [OfertaProductoController::class, 'indexEjecucionesHistorico'])->name('ofertas.historico.ejecuciones');
    //////// Ver log JSON de una ejecución concreta
    Route::get('ofertas/historico-precios/ejecuciones/{id}/log', function ($id) {
        $ejecucion = \App\Models\EjecucionGlobal::where('nombre', 'ejecuciones_historico_precios_ofertas')
            ->findOrFail($id);
        return response()->json(['log' => $ejecucion->log ?? []]);
    })->name('ofertas.historico.ejecucion.log');
    ////////// Eliminar ejecuciones antiguas
    Route::post('ofertas/historico-precios/ejecuciones/eliminar-antiguas', [OfertaProductoController::class, 'eliminarAntiguas'])->name('ofertas.ejecuciones.eliminar.antiguas');
    //////// Eliminar una ejecución concreta
    Route::delete('ofertas/historico-precios/ejecuciones/{ejecucion}', [OfertaProductoController::class, 'eliminar'])->name('ofertas.ejecuciones.eliminar');
    Route::get('ofertas/historico-precios/ejecutar', [OfertaProductoController::class, 'segundoPlanoGuardarPrecioHistoricoHoy'])
        ->name('ofertas.historico.ejecutar');
    //////// Mostrar modal calendario de historial mensual de una oferta
    Route::get('ofertas/{oferta}/historial-mes', [OfertaProductoController::class, 'historialMes'])->name('ofertas.historial.mes');
    //////// Guardar cambios en el historial de precios de una oferta
    Route::post('ofertas/{oferta}/historial-guardar', [OfertaProductoController::class, 'historialGuardar'])->name('ofertas.historial.guardar');

    //Listo de avisos
    // Rutas para Anti-Scraping
    Route::prefix('anti-scraping')->name('anti-scraping.')->group(function () {
        Route::get('fingerprints', [App\Http\Controllers\Admin\AntiScrapingController::class, 'fingerprintsProblematicos'])->name('fingerprints');
        Route::get('fingerprints/{fingerprint}', [App\Http\Controllers\Admin\AntiScrapingController::class, 'fingerprintDetalle'])->name('fingerprints.detalle');
    });

    Route::prefix('avisos')->name('avisos.')->group(function () {
        Route::get('/', [AvisoController::class, 'index'])->name('index');
        Route::post('/', [AvisoController::class, 'store'])->name('store');
        Route::post('/interno', [AvisoController::class, 'storeInterno'])->name('store.interno');
        Route::put('/{aviso}', [AvisoController::class, 'update'])->name('update');
        Route::get('/{aviso}/texto', [AvisoController::class, 'getTextoAviso'])->name('get.texto');
        Route::delete('/{aviso}', [AvisoController::class, 'destroy'])->name('destroy');
        Route::post('/{aviso}/aplazar', [AvisoController::class, 'aplazar'])->name('aplazar');
        Route::get('/elemento', [AvisoController::class, 'getAvisosElemento'])->name('get.elemento');
        Route::get('/oferta-mas-barata', [AvisoController::class, 'obtenerOfertaMasBarata'])->name('oferta-mas-barata');
        
        // Ejecuciones de comprobaciones
        Route::post('/ejecutar/comprobacion/productos-sin-ofertas', [AvisoController::class, 'ejecutarComprobacionProductosSinOfertas'])->name('ejecutar.comprobacion.productos-sin-ofertas');
        Route::post('/ejecutar/comprobacion/ofertas-vencidas', [AvisoController::class, 'ejecutarComprobacionOfertasVencidas'])->name('ejecutar.comprobacion.ofertas-vencidas');
    });

    // Rutas para Users
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [App\Http\Controllers\UsersController::class, 'index'])->name('index');
    });

    // RUTAS PARA SCRAPER DE OFERTAS
    // Ejecución en segundo plano (para cron jobs)
    Route::get('ofertas/scraper/ejecutar-segundo-plano', [OfertaProductoController::class, 'ejecutarScraperOfertasSegundoPlano'])->name('ofertas.scraper.ejecutar.segundo-plano');
    
    // Ejecución de ofertas de una tienda específica
    Route::post('ofertas/scraper/ejecutar-tienda', [OfertaProductoController::class, 'ejecutarScraperOfertasTienda'])->name('ofertas.scraper.ejecutar.tienda');

    // Historial de ejecuciones
    Route::get('ofertas/scraper/ejecuciones', [OfertaProductoController::class, 'indexEjecucionesScraper'])->name('ofertas.scraper.ejecuciones');
    Route::get('ofertas/scraper/ejecuciones/estadisticas-avanzadas', [OfertaProductoController::class, 'obtenerEstadisticasAvanzadas'])->name('ofertas.scraper.ejecuciones.estadisticas-avanzadas');
    Route::delete('ofertas/scraper/ejecuciones/{ejecucion}', [OfertaProductoController::class, 'eliminarEjecucionScraper'])->name('ofertas.scraper.ejecuciones.eliminar');
    Route::get('ofertas/scraper/ejecuciones/{ejecucion}/json', [OfertaProductoController::class, 'obtenerJsonEjecucionScraper'])->name('ofertas.scraper.ejecuciones.json');
    Route::get('ofertas/scraper/errores-tienda', [OfertaProductoController::class, 'obtenerErroresPorTienda'])->name('ofertas.scraper.errores.tienda');

    // Obtener precio individual desde formulario
    Route::post('ofertas/scraper/obtener-precio', [OfertaProductoController::class, 'procesarOfertaScraper'])->name('ofertas.scraper.obtener-precio');
    
    // Obtener tiendas disponibles para el formulario de editar oferta
    Route::get('ofertas/tiendas-disponibles', [OfertaProductoController::class, 'obtenerTiendasDisponibles'])->name('ofertas.tiendas.disponibles');
    
    // Buscar productos en tiempo real para el formulario
    Route::get('ofertas/buscar-productos', [OfertaProductoController::class, 'buscarProductos'])->name('ofertas.buscar.productos');
    Route::get('ofertas/buscar-chollos', [OfertaProductoController::class, 'buscarChollos'])->name('ofertas.buscar.chollos');
    Route::post('ofertas/calcular-precio-unidad', [OfertaProductoController::class, 'calcularPrecioUnidad'])->name('ofertas.calcular.precio-unidad');
    
    // Verificar si una URL ya existe
    Route::post('ofertas/verificar-url', [OfertaProductoController::class, 'verificarUrlExistente'])->name('ofertas.verificar.url');
    
    // Verificar si existe una oferta con chollo para el mismo producto y tienda
    Route::post('ofertas/verificar-chollo-existente', [OfertaProductoController::class, 'verificarOfertaCholloExistente'])->name('ofertas.verificar.chollo-existente');
    
    // Obtener información de variantes para una tienda específica
    Route::get('ofertas/variantes-tienda/{tienda}', [OfertaProductoController::class, 'obtenerVariantesTienda'])->name('ofertas.variantes.tienda');

    // RUTAS PARA PRUEBAS
    Route::prefix('pruebas')->name('pruebas.')->group(function () {
        Route::get('listar-ofertas', [PruebasController::class, 'index'])->name('listar-ofertas');
        Route::get('buscar-productos', [PruebasController::class, 'buscarProductos'])->name('buscar-productos');
        Route::post('obtener-ofertas', [PruebasController::class, 'obtenerOfertasProducto'])->name('obtener-ofertas');
    });

// Ejecutar guardar precio más bajo en segundo plano
Route::get('productos/precio-bajo/ejecutar-segundo-plano', [ProductoController::class, 'ejecutarPrecioBajoSegundoPlano'])->name('productos.precio-bajo.ejecutar.segundo-plano');

// Ejecutar actualizar oferta más barata en segundo plano
Route::get('productos/oferta-mas-barata/ejecutar-segundo-plano', [ProductoController::class, 'ejecutarOfertaMasBarataSegundoPlano'])->name('productos.oferta-mas-barata.ejecutar.segundo-plano');



    // Ejecución en tiempo real (con interfaz)
    Route::get('precios-hot/ejecutar', [App\Http\Controllers\PrecioHotController::class, 'verEjecucion'])->name('precios-hot.ejecutar');

    // Historial de ejecuciones
    Route::get('precios-hot/ejecuciones', [App\Http\Controllers\PrecioHotController::class, 'ejecuciones'])->name('precios-hot.ejecuciones');

    //Guardar los precios de todos los productos en segundo plano
    Route::get('productos/historico-precios/ejecutar', function (Request $request) {
        if ($request->query('token') !== env('TOKEN_ACTUALIZAR_PRECIOS')) {
            abort(403, 'Token inválido');
        }

        // Crear ejecución
        $ejecucion = \App\Models\EjecucionGlobal::create([
            'inicio' => now(),
            'nombre' => 'ejecuciones_historico_precios_productos',
            'log' => [],
        ]);

        $productos = \App\Models\Producto::all();
        $guardados = 0;
        $errores = 0;
        $log = [];

        foreach ($productos as $producto) {
            try {
                // Simulación: obtiene un precio aleatorio
                $precioOriginal = $producto->precio ?? rand(10, 100);
                $decimales = $producto->unidadDeMedida === 'unidadMilesima' ? 3 : 2;
                $precio = round((float) $precioOriginal, $decimales);

                DB::table('historico_precios_productos')->updateOrInsert(
                    [
                        'producto_id' => $producto->id,
                        'fecha' => now()->toDateString(),
                    ],
                    [
                        'precio_minimo' => $precio,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $guardados++;
            } catch (\Throwable $e) {
                $errores++;
                $log[] = [
                    'producto_id' => $producto->id,
                    'nombre' => $producto->nombre,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $ejecucion->update([
            'fin' => now(),
            'total' => count($productos),
            'total_guardado' => $guardados,
            'total_errores' => $errores,
            'log' => $log,
        ]);

        return response()->json([
            'status' => 'ok',
            'guardados' => $guardados,
            'errores' => $errores,
        ]);
    });

    Route::post('productos/historico-precios/procesar', function (Request $request) {
        $producto = Producto::findOrFail($request->input('id'));
        $forzar = $request->boolean('forzar', false); // si se permite actualizar un precio ya registrado
        $fecha = now()->toDateString();

        // Verificar si ya existe
        $existe = DB::table('historico_precios_productos')
            ->where('producto_id', $producto->id)
            ->where('fecha', $fecha)
            ->exists();

        if ($existe && !$forzar) {
            return response()->json([
                'status' => 'existe',
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
            ]);
        }

        try {
            $precioBase = $producto->precio ?? 0;
            $decimales = $producto->unidadDeMedida === 'unidadMilesima' ? 3 : 2;
            $precioFormateado = round((float) $precioBase, $decimales);

            DB::table('historico_precios_productos')->updateOrInsert(
                [
                    'producto_id' => $producto->id,
                    'fecha' => $fecha,
                ],
                [
                    'precio_minimo' => $precioFormateado,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            return response()->json([
                'status' => $existe ? 'actualizado' : 'guardado',
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'producto_id' => $producto->id,
                'nombre' => $producto->nombre,
                'error' => $e->getMessage(),
            ]);
        }
    })->name('productos.historico.procesar');

    Route::post('productos/historico-precios/finalizar-ejecucion', function (Request $request) {
        \App\Models\EjecucionGlobal::create([
            'inicio' => now(), // puedes guardar esto aparte si quieres precisión
            'fin' => now(),
            'nombre' => 'ejecuciones_historico_precios_productos',
            'total' => $request->input('total'),
            'total_guardado' => $request->input('correctos'),
            'total_errores' => $request->input('errores'),
            'log' => $request->input('log', []),
        ]);

        return response()->json(['status' => 'ok']);
    })->name('productos.historico.finalizar');
});

// Ruta de redirección para mantener compatibilidad - COMENTADA PARA EVITAR CONFLICTOS
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn() => redirect()->route('admin.dashboard'))->name('dashboard');
});

// // RUTA DE PRUEBA CON PATRÓN IDÉNTICO AL GRUPO PROBLEMÁTICO - ELIMINAR DESPUÉS
// Route::middleware(['auth'])->prefix('panel-privado')->group(function () {
//     Route::get('/test-grupo', function() {
//         return 'Grupo de rutas funcionando correctamente';
//     })->name('test.grupo');
// });

// CATEGORÍAS PÚBLICAS (MOVIDA AL FINAL PARA EVITAR CONFLICTOS)
Route::middleware(['security.headers', 'block.public.deletes'])->get('/categorias', [App\Http\Controllers\HomeController::class, 'todasCategorias'])->name('categorias.todas');

// DASHBOARD DE CLICKS PARA INFLUENCERS (fuera del middleware de autenticación)
Route::get('influencer/{usuario}/{password}/clicks/dashboard', [App\Http\Controllers\ClickInfluencerController::class, 'dashboard'])->middleware('throttle:10,1')->name('influencer.clicks.dashboard');
Route::get('influencer/clicks/posiciones-tienda', [App\Http\Controllers\ClickInfluencerController::class, 'posicionesTienda'])->name('influencer.clicks.posiciones-tienda');

// RUTA API PARA OBTENER PRECIOS HISTÓRICOS CON FILTROS DE TIEMPO
Route::middleware(['security.headers', 'block.public.deletes', 'throttle:10,1'])->get('/api/precios-historicos/{productoId}', [ProductoController::class, 'obtenerPreciosHistoricos'])
    ->name('api.precios.historicos');

// RUTA DINÁMICA PARA PRODUCTOS CON MÚLTIPLES CATEGORÍAS (DEBE IR AL FINAL)
// Esta ruta renderiza comparador.unidades.blade.php
// NOTA: Esta ruta está dentro del grupo de rutas públicas con security.headers y block.public.deletes (línea 45)
// Acepta un segmento opcional de variante: /{categorias}/{slug}/{variante?}
// Capturamos todo el path y parseamos manualmente
Route::get('/{path}', function ($path) {
    // Parsear el path completo manualmente
    $pathSegments = array_filter(explode('/', $path));
    
    if (count($pathSegments) < 2) {
        // Necesitamos al menos categoría + slug
        abort(404);
    }
    
    // El último segmento puede ser el slug o parte de la variante
    // Necesitamos encontrar el slug del producto
    // Intentar encontrar el producto probando desde el final hacia atrás
    $slug = null;
    $variante = null;
    $categorias = [];
    
    // Intentar encontrar el producto empezando desde el final
    for ($i = count($pathSegments) - 1; $i >= 1; $i--) {
        $slugCandidato = $pathSegments[$i];
        $producto = \App\Models\Producto::where('slug', $slugCandidato)->first();
        
        if ($producto) {
            $slug = $slugCandidato;
            // Todo antes del slug son categorías
            $categorias = array_slice($pathSegments, 0, $i);
            // Todo después del slug es la variante
            if ($i < count($pathSegments) - 1) {
                $variante = implode('/', array_slice($pathSegments, $i + 1));
            }
            break;
        }
    }
    
    if (!$slug) {
        abort(404);
    }
    
    // Buscar el producto por slug (ya lo tenemos de antes, pero lo recargamos con relaciones)
    $producto = \App\Models\Producto::with('categoria')->where('slug', $slug)->firstOrFail();
    
    // Obtener la jerarquía completa de categorías del producto
    $jerarquiaCompleta = $producto->categoria->obtenerJerarquiaCompleta();
    $slugsReales = collect($jerarquiaCompleta)->pluck('slug')->toArray();
    
    // Verificar si la URL coincide con la jerarquía real
    if ($categorias !== $slugsReales) {
        // Construir la URL correcta con todas las categorías
        $categoriasCorrectas = implode('/', $slugsReales);
        // Preservar el segmento de variante si existe
        $urlRedireccion = "/{$categoriasCorrectas}/{$slug}";
        if ($variante) {
            $urlRedireccion .= "/{$variante}";
        }
        return redirect($urlRedireccion, 301);
    }
    // Obtener datos históricos de 3 meses por defecto
    $desde = Carbon::today()->subDays(89); // 90 días - 1 = 89

$historico = HistoricoPrecioProducto::where('producto_id', $producto->id)
    ->where('fecha', '>=', $desde)
    ->orderBy('fecha')
    ->get()
    ->mapWithKeys(fn($item) => [
        Carbon::parse($item->fecha)->toDateString() => (float) $item->precio_minimo
    ]);

$precios = [];
for ($i = 0; $i < 90; $i++) {
    $fechaObj = Carbon::today()->subDays(89 - $i);
    $fechaYMD = $fechaObj->toDateString();
    $fechaDM = $fechaObj->format('d/m');

    $precios[] = [
        'fecha' => $fechaDM,
        'precio' => isset($historico[$fechaYMD]) ? (float) $historico[$fechaYMD] : 0,
    ];
}

    // Obtener todas las ofertas del producto aplicando descuentos y chollos
    //Esta localizado en App/Services/SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos.php
    $servicioOfertas = new SacarPrimeraOfertaDeUnProductoAplicadoDescuentosYChollos();
    $ofertas = $servicioOfertas->obtenerTodas($producto);

    // Construir breadcrumb dinámicamente desde la jerarquía completa
    $breadcrumb = collect($jerarquiaCompleta)->map(function ($categoria) {
        return [
            'nombre' => $categoria->nombre,
            'slug' => $categoria->slug
        ];
    })->toArray();

    // NUEVA LÓGICA: Usar la categoría seleccionada para productos relacionados
    $relacionados = collect();
    
    if ($producto->id_categoria_productos_relacionados) {
        // Obtener todas las categorías hijas de la categoría seleccionada
        $categoriaRelacionada = Categoria::find($producto->id_categoria_productos_relacionados);
        if ($categoriaRelacionada) {
            // Función para obtener todas las categorías hijas recursivamente
            $obtenerCategoriasHijas = function($categoriaId) use (&$obtenerCategoriasHijas) {
                $categoriaIds = [$categoriaId];
                $hijas = Categoria::where('parent_id', $categoriaId)->get();
                foreach ($hijas as $hija) {
                    $categoriaIds = array_merge($categoriaIds, $obtenerCategoriasHijas($hija->id));
                }
                return $categoriaIds;
            };
            
            $idsCategoriasRelacionadas = $obtenerCategoriasHijas($producto->id_categoria_productos_relacionados);
            
            $relacionados = Producto::where('id', '!=', $producto->id)
                ->whereIn('categoria_id', $idsCategoriasRelacionadas)
                ->get()
                ->filter(function ($rel) use ($producto) {
                    $coincidencias = 0;
                    foreach ($producto->keys_relacionados ?? [] as $tag) {
                        $coincidencias += collect([$rel->marca, $rel->modelo, $rel->talla])
                            ->filter(fn($v) => Str::of($v)->lower()->contains(Str::of($tag)->lower()))
                            ->count();
                    }
                    $rel->coincidencias = $coincidencias;
                    return $coincidencias > 0;
                })
                ->sortByDesc('coincidencias')
                ->sortBy('precio')
                ->take(10)
                ->values();
        }
    } else {
        // LÓGICA ANTERIOR: Si no hay categoría seleccionada, usar la lógica original
        //Sacamos todas las categorias hermanas y padre, relacionadas con este producto
        // Cadena de categorías desde raíz hasta la actual
        $chain = collect();
        $cat = $producto->categoria;         // requiere ->parent en Categoria
        while ($cat) { $chain->push($cat); $cat = $cat->parent; }
        $chain = $chain->reverse()->values(); // [raíz, ..., actual]
        
        // ids de la jerarquía (incluye la categoría actual)
        $idsJerarquia = $chain->pluck('id');
        
        // parent_ids de todos los niveles excepto el más alto (omitimos raíz)
        $parentIds = $chain->skip(1)->pluck('parent_id')->filter()->unique();
        
        // ids de hermanas de cada uno de esos niveles
        $idsHermanas = $parentIds->isEmpty()
            ? collect()
            : Categoria::whereIn('parent_id', $parentIds)->pluck('id');
        
        // set final: jerarquía + hermanas (incluye la categoría actual)
        $idsObjetivo = $idsJerarquia->merge($idsHermanas)->unique()->values();
        
        $relacionados = Producto::where('id', '!=', $producto->id)
            ->whereIn('categoria_id', $idsObjetivo) // OR implícito
            ->get()
            ->filter(function ($rel) use ($producto) {
                $coincidencias = 0;
                foreach ($producto->keys_relacionados ?? [] as $tag) {
                    $coincidencias += collect([$rel->marca, $rel->modelo, $rel->talla])
                        ->filter(fn($v) => Str::of($v)->lower()->contains(Str::of($tag)->lower()))
                        ->count();
                }
                $rel->coincidencias = $coincidencias;
                return $coincidencias > 0;
            })
            ->sortByDesc('coincidencias')
            ->sortBy('precio')
            ->take(10)
            ->values();
    }

    // Obtener productos por debajo del precio medio
    $productosPrecioMedio = collect();
    $precioHot = \App\Models\PrecioHot::where('nombre', $producto->categoria->nombre)->first();
    
    if ($precioHot && !empty($precioHot->datos)) {
        $productosPrecioMedio = collect($precioHot->datos)
            ->take(10)
            ->map(function ($item) {
                $producto = \App\Models\Producto::find($item['producto_id']);
                if ($producto) {
                    return [
                        'producto' => $producto,
                        'oferta_id' => $item['oferta_id'] ?? null,
                        'tienda_id' => $item['tienda_id'] ?? null,
                        'img_tienda' => $item['img_tienda'] ?? 'tiendas/carrefour.png',
                        'img_producto' => $item['img_producto'] ?? 'panales/chelino-nature-talla-1.jpg',
                        'precio_oferta' => $item['precio_oferta'] ?? 0,
                        'precio_formateado' => $item['precio_formateado'] ?? number_format($item['precio_oferta'] ?? 0, 2, ',', '.') . ' €/Und.',
                        'porcentaje_diferencia' => $item['porcentaje_diferencia'] ?? 0,
                        'url_oferta' => $item['url_oferta'] ?? '#',
                        'url_producto' => $item['url_producto'] ?? '#',
                        'producto_nombre' => $item['producto_nombre'] ?? $producto->nombre,
                        'tienda_nombre' => $item['tienda_nombre'] ?? 'Tienda desconocida',
                        'unidades' => $item['unidades'] ?? 1,
                        'unidades_formateadas' => $item['unidades_formateadas'] ?? number_format($item['unidades'] ?? 1, 0, ',', '.') . ' Unidades',
                        'unidad_medida' => $item['unidad_medida'] ?? $producto->unidadDeMedida
                    ];
                }
                return null;
            })->filter()->values();
    }

    // Obtener productos de Precios Hot generales
    $productosPreciosHot = collect();
    $precioHotGeneral = \App\Models\PrecioHot::where('nombre', 'Precios Hot')->first();
    
    if ($precioHotGeneral && !empty($precioHotGeneral->datos)) {
        $productosPreciosHot = collect($precioHotGeneral->datos)
            ->take(10)
            ->map(function ($item) {
                $producto = \App\Models\Producto::find($item['producto_id']);
                if ($producto) {
                    return [
                        'producto' => $producto,
                        'oferta_id' => $item['oferta_id'] ?? null,
                        'tienda_id' => $item['tienda_id'] ?? null,
                        'img_tienda' => $item['img_tienda'] ?? 'tiendas/carrefour.png',
                        'img_producto' => $item['img_producto'] ?? 'panales/chelino-nature-talla-1.jpg',
                        'precio_oferta' => $item['precio_oferta'] ?? 0,
                        'precio_formateado' => $item['precio_formateado'] ?? number_format($item['precio_oferta'] ?? 0, 2, ',', '.') . ' €/Und.',
                        'porcentaje_diferencia' => $item['porcentaje_diferencia'] ?? 0,
                        'url_oferta' => $item['url_oferta'] ?? '#',
                        'url_producto' => $item['url_producto'] ?? '#',
                        'producto_nombre' => $item['producto_nombre'] ?? $producto->nombre,
                        'tienda_nombre' => $item['tienda_nombre'] ?? 'Tienda desconocida',
                        'unidades' => $item['unidades'] ?? 1,
                        'unidades_formateadas' => $item['unidades_formateadas'] ?? number_format($item['unidades'] ?? 1, 0, ',', '.') . ' Unidades',
                        'unidad_medida' => $item['unidad_medida'] ?? $producto->unidadDeMedida
                    ];
                }
                return null;
            })->filter()->values();
    }

    // Parsear filtros desde query params si existen
    $filtrosAplicadosDesdeUrl = [];
    $request = request();
    
    // Verificar si la categoría del producto tiene especificaciones internas
    $categoria = $producto->categoria;
    $tieneEspecificacionesInternas = $categoria->especificaciones_internas && 
                                     is_array($categoria->especificaciones_internas) && 
                                     isset($categoria->especificaciones_internas['filtros']) &&
                                     count($categoria->especificaciones_internas['filtros']) > 0;
    
    if ($tieneEspecificacionesInternas) {
        // Obtener estructura de filtros de categoría
        $estructuraFiltros = $categoria->especificaciones_internas['filtros'] ?? [];
        
        // También obtener filtros de producto si existen
        $filtrosProducto = [];
        $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
        if (isset($especificacionesElegidas['_producto']['filtros']) && 
            is_array($especificacionesElegidas['_producto']['filtros'])) {
            $filtrosProducto = $especificacionesElegidas['_producto']['filtros'];
        }
        
        // Combinar filtros de categoría y producto
        $filtrosCombinados = array_merge($estructuraFiltros, $filtrosProducto);
        
        // Crear mapa de slugs a IDs
        $mapaSlugs = [];
        foreach ($filtrosCombinados as $filtro) {
            $lineaId = $filtro['id'] ?? null;
            if (!$lineaId) continue;
            
            foreach ($filtro['subprincipales'] ?? [] as $sub) {
                $sublineaId = $sub['id'] ?? null;
                $slug = $sub['slug'] ?? null;
                $texto = $sub['texto'] ?? null;
                
                // IMPORTANTE: Siempre generar el slug desde el texto para asegurar consistencia
                // "blanco blanco" → "blanco-blanco"
                $slugDesdeTexto = $texto ? \Illuminate\Support\Str::slug($texto) : null;
                
                // Usar el slug generado desde el texto como principal
                // Si hay un slug guardado, también añadirlo, pero el generado tiene prioridad
                $slugPrincipal = $slugDesdeTexto ?: $slug;
                
                if ($slugPrincipal && $sublineaId) {
                    $mapaSlugs[$slugPrincipal] = [
                        'id' => $sublineaId,
                        'linea_principal_id' => $lineaId,
                        'texto' => $texto, // Guardar también el texto para búsqueda flexible
                    ];
                }
                
                // También añadir el slug guardado si es diferente al generado
                if ($slug && $slug !== $slugPrincipal && $sublineaId) {
                    $mapaSlugs[$slug] = [
                        'id' => $sublineaId,
                        'linea_principal_id' => $lineaId,
                        'texto' => $texto,
                    ];
                }
            }
        }
        
        // Procesar segmento de variante de la URL (ej: /256gb/amarillo)
        if ($variante) {
            // Dividir el segmento de variante por "/" para obtener múltiples variantes
            $segmentosVariante = array_filter(explode('/', $variante));
            
            // Agrupar IDs por línea principal
            $idsPorLinea = [];
            
            foreach ($segmentosVariante as $segmento) {
                // Normalizar el segmento de la URL (por si tiene variaciones)
                // La URL ya viene como slug (ej: "blanco-blanco"), pero normalizamos por si acaso
                $segmentoNormalizado = \Illuminate\Support\Str::slug($segmento);
                
                // Buscar el slug en el mapa (búsqueda exacta primero)
                $info = null;
                if (isset($mapaSlugs[$segmento])) {
                    $info = $mapaSlugs[$segmento];
                } elseif (isset($mapaSlugs[$segmentoNormalizado])) {
                    $info = $mapaSlugs[$segmentoNormalizado];
                } else {
                    // Buscar en todos los slugs del mapa (comparación flexible)
                    foreach ($mapaSlugs as $slugMapa => $infoMapa) {
                        // Normalizar el slug del mapa para comparar
                        $slugMapaNormalizado = is_string($slugMapa) ? \Illuminate\Support\Str::slug($slugMapa) : strval($slugMapa);
                        
                        // Comparar normalizados
                        if ($slugMapaNormalizado === $segmentoNormalizado) {
                            $info = $infoMapa;
                            break;
                        }
                        
                        // También comparar con el texto si está disponible
                        if (isset($infoMapa['texto']) && $infoMapa['texto']) {
                            $textoNormalizado = \Illuminate\Support\Str::slug($infoMapa['texto']);
                            if ($textoNormalizado === $segmentoNormalizado) {
                                $info = $infoMapa;
                                break;
                            }
                        }
                    }
                }
                
                if ($info) {
                    $lineaId = $info['linea_principal_id'];
                    $sublineaId = $info['id'];
                    
                    // Verificar que esta sublínea existe en las especificaciones elegidas del producto
                    $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
                    if ($especificacionesElegidas && is_array($especificacionesElegidas)) {
                        // Buscar primero en las especificaciones de categoría (estructura normal)
                        $productoLinea = $especificacionesElegidas[$lineaId] ?? null;
                        $existeEnProducto = false;
                        
                        if ($productoLinea) {
                            $productoSublineas = is_array($productoLinea) ? $productoLinea : [$productoLinea];
                            
                            // Verificar si esta sublínea está en las especificaciones del producto
                            foreach ($productoSublineas as $item) {
                                $itemId = (is_array($item) && isset($item['id'])) ? strval($item['id']) : strval($item);
                                if (strval($itemId) === strval($sublineaId)) {
                                    $existeEnProducto = true;
                                    break;
                                }
                            }
                        }
                        
                        // Si no se encontró en las especificaciones de categoría, buscar en las especificaciones del producto
                        if (!$existeEnProducto && isset($especificacionesElegidas['_producto']['filtros'])) {
                            foreach ($especificacionesElegidas['_producto']['filtros'] as $filtroProducto) {
                                $filtroProductoId = $filtroProducto['id'] ?? null;
                                if (strval($filtroProductoId) === strval($lineaId)) {
                                    // Encontrar la sublínea en este filtro del producto
                                    foreach ($filtroProducto['subprincipales'] ?? [] as $subProducto) {
                                        $subProductoId = $subProducto['id'] ?? null;
                                        if (strval($subProductoId) === strval($sublineaId)) {
                                            $existeEnProducto = true;
                                            break 2; // Salir de ambos bucles
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Solo añadir si existe en el producto
                        if ($existeEnProducto) {
                            if (!isset($idsPorLinea[$lineaId])) {
                                $idsPorLinea[$lineaId] = [];
                            }
                            $idsPorLinea[$lineaId][] = $sublineaId;
                        }
                    }
                }
            }
            
            // Añadir los IDs encontrados a $filtrosAplicadosDesdeUrl
            foreach ($idsPorLinea as $lineaId => $ids) {
                // IMPORTANTE: Para variantes desde la URL, siempre aplicamos el filtro
                // ya que el usuario está navegando directamente a esa variante específica
                // No necesitamos verificar _columnas porque es una navegación directa
                $debeAplicarFiltro = true;
                
                // Verificar si es una especificación interna del producto
                $esEspecificacionProducto = false;
                $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
                if ($especificacionesElegidas && is_array($especificacionesElegidas)) {
                    // Verificar si está en las especificaciones del producto
                    if (isset($especificacionesElegidas['_producto']['filtros'])) {
                        foreach ($especificacionesElegidas['_producto']['filtros'] as $filtroProducto) {
                            $filtroProductoId = $filtroProducto['id'] ?? null;
                            if (strval($filtroProductoId) === strval($lineaId)) {
                                $esEspecificacionProducto = true;
                                break;
                            }
                        }
                    }
                    
                    // Si NO es especificación del producto Y el producto tiene unidadDeMedida === 'unidadUnica'
                    // entonces solo aplicar si está en _columnas
                    if (!$esEspecificacionProducto && $producto->unidadDeMedida === 'unidadUnica') {
                        if (isset($especificacionesElegidas['_columnas']) && 
                            is_array($especificacionesElegidas['_columnas'])) {
                            $columnas = array_map('strval', $especificacionesElegidas['_columnas']);
                            $lineaIdStr = (string)$lineaId;
                            
                            if (!in_array($lineaIdStr, $columnas, true)) {
                                $debeAplicarFiltro = false;
                            }
                        }
                    }
                }
                
                if ($debeAplicarFiltro && !empty($ids)) {
                    $filtrosAplicadosDesdeUrl[$lineaId] = array_unique($ids);
                }
            }
        }
        
        // Parsear filtros desde query params
        // Formato: f{lineaId} = "talla-1-talla-2" donde lineaId puede ser cualquier formato (ej: "id_123_abc", "123", "abc", etc.)
        // También acepta formato antiguo: filtros_{lineaId} para compatibilidad
        foreach ($request->all() as $key => $value) {
            $lineaId = null;
            
            // Detectar formato nuevo: f{lineaId} (donde lineaId puede ser cualquier string, sin restricciones de formato)
            // Ejemplos válidos: f123, fid_123_abc, f1767459951337_fe5eebb9, etc.
            if (strpos($key, 'f') === 0 && strlen($key) > 1) {
                // Tomar todo lo que viene después de 'f' como el ID de la línea
                // No asumimos ningún formato específico del ID, puede ser cualquier string
                $lineaId = substr($key, 1);
            } 
            // Detectar formato antiguo: filtros_{lineaId} (para compatibilidad con enlaces antiguos)
            elseif (strpos($key, 'filtros_') === 0) {
                $lineaId = str_replace('filtros_', '', $key);
            }
            
            // Si encontramos un parámetro de filtro válido, procesarlo
            if ($lineaId && !empty($value)) {
                $segmento = $value;
                
                // Buscar slugs en el segmento
                $partes = explode('-', $segmento);
                $longitud = count($partes);
                $posicion = 0;
                $idsEncontrados = [];
                
                while ($posicion < $longitud) {
                    $slugEncontrado = null;
                    $longitudSlug = 0;
                    
                    // Intentar encontrar el slug más largo posible desde la posición actual
                    for ($i = $longitud; $i > $posicion; $i--) {
                        $candidato = implode('-', array_slice($partes, $posicion, $i - $posicion));
                        if (isset($mapaSlugs[$candidato])) {
                            $slugEncontrado = $candidato;
                            $longitudSlug = $i - $posicion;
                            break;
                        }
                    }
                    
                    if ($slugEncontrado) {
                        $info = $mapaSlugs[$slugEncontrado];
                        // Solo añadir si pertenece a la misma línea principal
                        if ($info['linea_principal_id'] == $lineaId) {
                            $idsEncontrados[] = $info['id'];
                        }
                        $posicion += $longitudSlug;
                    } else {
                        $posicion++;
                    }
                }
                
                if (!empty($idsEncontrados)) {
                    // Verificar si la línea principal está marcada como "columna oferta" en el producto
                    $debeAplicarFiltro = true;
                    
                    // Solo verificar si el producto tiene especificaciones internas elegidas
                    if ($producto->categoria_id_especificaciones_internas && 
                        $producto->categoria_especificaciones_internas_elegidas &&
                        is_array($producto->categoria_especificaciones_internas_elegidas)) {
                        
                        $especificacionesElegidas = $producto->categoria_especificaciones_internas_elegidas;
                        
                        // Verificar si existe la clave _columnas
                        if (isset($especificacionesElegidas['_columnas']) && 
                            is_array($especificacionesElegidas['_columnas'])) {
                            
                            // Verificar si la línea principal está en el array _columnas
                            // Convertir ambos a string para comparación segura
                            $columnas = array_map('strval', $especificacionesElegidas['_columnas']);
                            $lineaIdStr = (string)$lineaId;
                            
                            // Si la línea principal NO está marcada como columna, no aplicar el filtro
                            if (!in_array($lineaIdStr, $columnas, true)) {
                                $debeAplicarFiltro = false;
                            }
                        }
                    }
                    
                    // Solo aplicar el filtro si debe aplicarse
                    if ($debeAplicarFiltro) {
                        $filtrosAplicadosDesdeUrl[$lineaId] = array_unique($idsEncontrados);
                    }
                    // Si no debe aplicarse, simplemente no hacer nada (no añadir el filtro)
                }
            }
        }
        
    }

    return view('comparador.unidades', compact('producto', 'ofertas', 'breadcrumb', 'relacionados', 'productosPrecioMedio', 'productosPreciosHot', 'precios', 'filtrosAplicadosDesdeUrl'));
})->where('path', '.*');

// Cierre del grupo de rutas públicas con security.headers (iniciado en línea 44)
});

