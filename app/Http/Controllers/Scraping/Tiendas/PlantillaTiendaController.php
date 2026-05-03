<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Scraping\PeticionApiHTMLController;

/**
 * PLANTILLA BASE PARA CONTROLADORES DE TIENDAS
 *
 * INSTRUCCIONES PARA UNA IA:
 *
 * 1. Copia este archivo y renómbralo como [NombreTienda]Controller.php
 * 2. Cambia el nombre de la clase a [NombreTienda]Controller
 * 3. Implementa el método obtenerPrecio() con la lógica específica de la tienda
 *
 * ESTRUCTURA DEL MÉTODO obtenerPrecio():
 * - Recibe $url y $variante como parámetros
 * - Usa $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null) para obtener el HTML
 * - Extrae el precio del HTML usando selectores CSS, regex, o DOM
 * - Devuelve un array con 'success' => true/false y 'precio' => valor o 'error' => mensaje
 *
 * EJEMPLO DE IMPLEMENTACIÓN:
 *
 * public function obtenerPrecio($url, $variante = null, $tienda = null)
 * {
 *     $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
 *     if (!$resultado['success']) {
 *         return response()->json(['success' => false, 'error' => $resultado['error']]);
 *     }
 *     $html = $resultado['html'];
 *     // Extraer precio (selectores/regex de la tienda)
 *     return response()->json(['success' => true, 'precio' => (float) $precio]);
 * }
 *
 * NOTAS: Normaliza precio (comas por puntos), maneja errores, devuelve JSON consistente.
 *
 * -------------------------------------------------------------------------
 * CRON NEO OBJETIVOS - LISTADOS DE CATEGORÍA (opcional)
 * -------------------------------------------------------------------------
 * Si la tienda va a usarse en el cron de neo objetivos para sacar URLs de
 * productos desde una URL de categoría, implementa los métodos siguientes.
 * Si no implementas tipoListadoCategoria() o devuelves null, el cron
 * ignorará esta tienda para el flujo categoría/tienda (fuera de rama Neo).
 *
 * 1) tipoListadoCategoria(): string|null
 *    - Nombre del método: tipoListadoCategoria
 *    - Parámetros: ninguno
 *    - Devuelve: 'sitemap' | 'paginacion' | 'mostrar_mas' | null
 *    - null = esta tienda no soporta listado de categoría para el cron.
 *
 * 2) SITEMAP (una sola petición al sitemap, extraes todas las URLs):
 *    - Método: urlsProductosDesdeSitemap(string $contenidoSitemap): array
 *    - Recibe: contenido crudo de la petición al sitemap (XML/HTML).
 *    - Devuelve: array de strings con las URLs de productos encontradas.
 *
 * 3) PAGINACIÓN (varias páginas; en cada una extraes productos + URL siguiente):
 *    - Método: extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
 *    - Recibe:
 *        $html = HTML de la página de categoría obtenida.
 *        $urlPeticionActual = URL que se pidió para obtener ese HTML (por si la siguiente
 *            no viene en el HTML y hay que construirla ej. ?page=2).
 *    - Devuelve: [
 *        'urls_productos' => string[],   // URLs de productos de esta página
 *        'siguiente_url'   => ?string    // URL de la siguiente página o null si no hay más
 *      ]
 *    - Si el "siguiente" no está en el HTML, puedes construirla desde $urlPeticionActual
 *      (ej. añadir ?page=2, &page=2, etc.).
 *
 * 4) MOSTRAR MÁS (una petición; el VPS puede hacer clics en "ver más" y devuelve HTML completo):
 *    - Método: urlsProductosDesdeHtmlMostrarMas(string $html): array
 *    - Recibe: HTML completo de la categoría tras los clics en "ver más".
 *    - Devuelve: array de strings con las URLs de productos.
 *    - Opcional: selectorCargarMasParaVps(): ?string — CSS selector del botón/enlace
 *      (ej. #yith-infs-button). Si no es null, se envía al VPS en cargar_mas_selector.
 *
 * Los métodos 2–4 tienen implementación por defecto que devuelve vacío/null;
 * sobrescríbelos solo si la tienda soporta ese tipo.
 */
abstract class PlantillaTiendaController extends Controller
{
    protected $apiHTML;

    public function __construct()
    {
        $this->apiHTML = new PeticionApiHTMLController();
    }

    /**
     * Método abstracto que debe implementar cada tienda para obtener precio de una URL de producto.
     */
    abstract public function obtenerPrecio($url, $variante = null, $tienda = null);

    /**
     * Tipo de listado de categoría para el cron neo objetivos (rama categoría/tienda).
     * Devuelve 'sitemap' | 'paginacion' | 'mostrar_mas' | null (null = no soportado).
     *
     * @return string|null
     */
    public function tipoListadoCategoria(): ?string
    {
        return null;
    }

    /**
     * Extrae URLs de productos desde el contenido de un sitemap (solo si tipoListadoCategoria() === 'sitemap').
     *
     * @param string $contenidoSitemap Contenido crudo de la petición al sitemap (XML/HTML)
     * @return string[] URLs de productos
     */
    public function urlsProductosDesdeSitemap(string $contenidoSitemap): array
    {
        return [];
    }

    /**
     * Extrae URLs de productos de la página y la URL de la siguiente (solo si tipoListadoCategoria() === 'paginacion').
     *
     * @param string $html HTML de la página de categoría
     * @param string $urlPeticionActual URL que se pidió para obtener ese HTML (para poder construir ?page=2 si no viene en el HTML)
     * @return array{urls_productos: string[], siguiente_url: string|null}
     */
    public function extraerProductosYSiguientePagina(string $html, string $urlPeticionActual): array
    {
        return ['urls_productos' => [], 'siguiente_url' => null];
    }

    /**
     * Extrae todas las URLs de productos desde el HTML tras "ver más" (solo si tipoListadoCategoria() === 'mostrar_mas').
     *
     * @param string $html HTML completo de la categoría tras los clics en "ver más"
     * @return string[] URLs de productos
     */
    public function urlsProductosDesdeHtmlMostrarMas(string $html): array
    {
        return [];
    }

    /**
     * Selector CSS para que el VPS pulse "cargar más" antes de devolver el HTML (solo mostrar_mas + VPS).
     * Por defecto no se envía nada.
     */
    public function selectorCargarMasParaVps(): ?string
    {
        return null;
    }
}


