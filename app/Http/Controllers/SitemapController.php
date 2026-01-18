<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    /**
     * Sitemap principal que lista todos los sitemaps
     */
    public function index()
    {
        $content = view('politicas.sitemaps.index')->render();

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);
    }

    /**
     * Sitemap de categorías con paginación
     */
    public function categorias(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 1000;

        // Cache para mejorar rendimiento
        $cacheKey = "sitemap_categorias_page_{$page}";

        $content = Cache::remember($cacheKey, 3600, function () use ($page, $perPage) {
            $categorias = Categoria::select('slug', 'updated_at')
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return view('politicas.sitemaps.categorias', compact('categorias'))->render();
        });

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);
    }

    /**
     * Sitemap de productos con paginación
     */
    public function productos(Request $request)
    {
        $page = $request->get('page', 1);
        $perPage = 1000;

        // Cache para mejorar rendimiento
        $cacheKey = "sitemap_productos_page_{$page}";

        $content = Cache::remember($cacheKey, 3600, function () use ($page, $perPage) {
            $productos = Producto::with('categoria.parent.parent')
                ->select('id', 'slug', 'categoria_id', 'updated_at', 'clicks')
                ->where('obsoleto', 'no')
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);


            // Calculamos segun el rango de clicks de cada producto
            // segun el rango de clicks, le asignamos un valor de prioridad
            $allClicks = Producto::where('obsoleto', 'no')
            ->pluck('clicks')
            ->sort()
            ->values();
            
            $count = $allClicks->count();
            
            $q1 = $allClicks->get(floor($count * 0.25)) ?? 0;
            $q2 = $allClicks->get(floor($count * 0.50)) ?? 0;
            $q3 = $allClicks->get(floor($count * 0.75)) ?? 0;

            return view('politicas.sitemaps.productos', compact('productos', 'q1', 'q2', 'q3'))->render();
        });

        return Response::make($content, 200, [
            'Content-Type' => 'application/xml; charset=utf-8'
        ]);
    }
}

