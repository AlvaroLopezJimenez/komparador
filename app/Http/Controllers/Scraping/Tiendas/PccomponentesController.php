<?php

namespace App\Http\Controllers\Scraping\Tiendas;

use App\Http\Controllers\Scraping\PeticionApiHTMLController;
use App\Http\Controllers\Scraping\Tiendas\PlantillaTiendaController;
use App\Models\OfertaProducto;
use Illuminate\Support\Facades\DB;

class PccomponentesController extends PlantillaTiendaController
{
    /**
     * Devuelve JSON: { success: bool, precio?: float, error?: string }
     */
    public function obtenerPrecio($url, $variante = null, $tienda = null, $oferta = null)
    {
        $resultado = $this->apiHTML->obtenerHTML($url, null, $tienda ? $tienda->api : null);
        if (!$resultado['success']) {
            return response()->json([
                'success' => false,
                'error'   => $resultado['error'] ?? 'Error obteniendo HTML'
            ]);
        }

        $html = $resultado['html'];

        // Verificar si el producto está sin stock (texto "Avísame cuando esté disponible")
        if (strpos($html, 'Avísame cuando esté disponible') !== false) {
            // Si tenemos oferta, generar aviso y ocultar
            if ($oferta && $oferta instanceof OfertaProducto) {
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a un día vista
                DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'SIN STOCK 1A VEZ - GENERADO AUTOMÁTICAMENTE',
                    'fecha_aviso'     => now()->addDay(), // Un día vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Producto sin stock'
            ]);
        }

        // Verificar si es una página 404 (producto no encontrado)
        if ($this->esPagina404($html)) {
            // Si tenemos oferta, generar aviso y ocultar
            if ($oferta && $oferta instanceof OfertaProducto) {
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a una hora vista
                DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'PRODUCTO NO ENCONTRADO (404) - 1a vez Generado Automáticamente',
                    'fecha_aviso'     => now()->addHour(), // Una hora vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Producto no encontrado (página 404)'
            ]);
        }

        // Verificar si el producto es reacondicionado (en el título)
        if ($this->esReacondicionado($html)) {
            // Si tenemos oferta, generar aviso y ocultar
            if ($oferta && $oferta instanceof OfertaProducto) {
                // Actualizar oferta para no mostrar
                $oferta->update(['mostrar' => 'no']);
                
                // Crear aviso con fecha a 4 días vista
                DB::table('avisos')->insertGetId([
                    'texto_aviso'     => 'Reacondicionado 1a vez - Generado Automaticamente',
                    'fecha_aviso'     => now()->addDays(4), // 4 días vista
                    'user_id'         => 1,                 // usuario sistema
                    'avisoable_type'  => \App\Models\OfertaProducto::class,
                    'avisoable_id'    => $oferta->id,
                    'oculto'          => 0,                 // visible
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            return response()->json([
                'success' => false,
                'error'   => 'Producto reacondicionado'
            ]);
        }

        // Extraer precio (varios fallbacks, porque el HTML de PCC puede variar / tener spans anidados)
        $precio = $this->extraerPrecio($html);
        if ($precio !== null) {
            return response()->json(['success' => true, 'precio' => $precio]);
        }

        return response()->json([
            'success' => false,
            'error'   => 'No se pudo encontrar el precio en la página de PC Componentes'
        ]);
    }

    // -------------------------------------------------------------------------
    // Cron Neo Objetivos – listado de categoría por sitemap
    // -------------------------------------------------------------------------

    /**
     * Tipo de listado de categoría: sitemap (URLs desde sitemap XML).
     *
     * @return string|null
     */
    public function tipoListadoCategoria(): ?string
    {
        return 'sitemap';
    }

    /**
     * Extrae URLs de productos desde sitemap XML (urlset con &lt;loc&gt;, con o sin namespace).
     *
     * @param string $contenidoSitemap Contenido crudo del sitemap (XML)
     * @return string[] URLs de fichas de producto en pccomponentes.com
     */
    public function urlsProductosDesdeSitemap(string $contenidoSitemap): array
    {
        $contenido = $this->normalizarContenidoSitemapPccomponentes($contenidoSitemap);
        $todasLasLocs = $this->extraerTodasLasLocsSitemapPccomponentes($contenido);

        $productos = array_values(array_filter(
            $todasLasLocs,
            fn (string $u) => $this->esUrlProductoPccomponentesDesdeSitemap($u)
        ));

        if ($productos !== []) {
            return $productos;
        }

        return $this->urlsProductosDesdeSubSitemapsPccomponentes($todasLasLocs, null);
    }

    /**
     * @return array<int, string>
     */
    private function extraerTodasLasLocsSitemapPccomponentes(string $contenido): array
    {
        $urls = $this->urlsLocDesdeSitemapXmlPccomponentes($contenido);
        if ($urls === []) {
            $urls = $this->urlsLocDesdeSitemapRegexPccomponentes($contenido);
        }

        return array_values(array_unique(array_filter(array_map('trim', $urls))));
    }

    /**
     * Si el índice solo enlaza .xml hijos, descarga cada sub-sitemap y extrae productos.
     *
     * @param array<int, string> $locs
     * @return array<int, string>
     */
    private function urlsProductosDesdeSubSitemapsPccomponentes(array $locs, ?string $apiTienda): array
    {
        $subSitemaps = array_values(array_filter($locs, fn (string $u) => $this->esUrlSubSitemapPccomponentes($u)));
        if ($subSitemaps === []) {
            return [];
        }

        $apiHTML = $this->apiHTML ?? app(PeticionApiHTMLController::class);
        $productos = [];
        $maxSubSitemaps = 20;

        foreach (array_slice($subSitemaps, 0, $maxSubSitemaps) as $urlSub) {
            $resultado = $apiHTML->obtenerHTML($urlSub, null, $apiTienda);
            if (empty($resultado['success']) || empty($resultado['html'])) {
                continue;
            }
            $productos = array_merge(
                $productos,
                $this->urlsProductosDesdeSitemap((string) $resultado['html'])
            );
        }

        return array_values(array_unique($productos));
    }

    private function esUrlSubSitemapPccomponentes(string $url): bool
    {
        if (!$this->esUrlProductoPccomponentesDesdeSitemap($url)) {
            $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
            if (preg_match('/\.xml$/iu', $path) && preg_match('/(?:^|\.)pccomponentes\.com$/iu', (string) parse_url($url, PHP_URL_HOST))) {
                return true;
            }
        }

        return false;
    }

    private function normalizarContenidoSitemapPccomponentes(string $contenido): string
    {
        $contenido = html_entity_decode($contenido, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido) ?? $contenido;
        $contenido = preg_replace('/<!--\s*PROVEEDOR:[\s\S]*?-->\s*/iu', '', $contenido, 1) ?? $contenido;

        return trim($contenido);
    }

    /**
     * @return array<int, string>
     */
    private function urlsLocDesdeSitemapXmlPccomponentes(string $contenido): array
    {
        $prev = libxml_use_internal_errors(true);
        $xml = @simplexml_load_string($contenido, 'SimpleXMLElement', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            return [];
        }

        $urls = [];
        $this->recolectarLocsSitemapXml($xml, $urls);

        foreach ($xml->getNamespaces(true) as $nsUri) {
            if ($nsUri === '' || !is_string($nsUri)) {
                continue;
            }
            $this->recolectarLocsSitemapXml($xml->children($nsUri), $urls);
        }

        return $urls;
    }

    /**
     * @param array<int, string> $urls
     */
    private function recolectarLocsSitemapXml(\SimpleXMLElement $nodo, array &$urls): void
    {
        foreach ($nodo->url ?? [] as $urlNodo) {
            $loc = trim((string) ($urlNodo->loc ?? ''));
            if ($loc !== '') {
                $urls[] = $loc;
            }
        }

        foreach ($nodo->sitemap ?? [] as $sitemapNodo) {
            $loc = trim((string) ($sitemapNodo->loc ?? ''));
            if ($loc !== '') {
                $urls[] = $loc;
            }
        }

        foreach ($nodo->getNamespaces(true) as $nsUri) {
            if ($nsUri === '' || !is_string($nsUri)) {
                continue;
            }
            $hijos = $nodo->children($nsUri);
            foreach ($hijos->url ?? [] as $urlNodo) {
                $loc = trim((string) ($urlNodo->loc ?? ''));
                if ($loc !== '') {
                    $urls[] = $loc;
                }
            }
            foreach ($hijos->sitemap ?? [] as $sitemapNodo) {
                $loc = trim((string) ($sitemapNodo->loc ?? ''));
                if ($loc !== '') {
                    $urls[] = $loc;
                }
            }
        }
    }

    /**
     * @return array<int, string>
     */
    private function urlsLocDesdeSitemapRegexPccomponentes(string $contenido): array
    {
        $urls = [];

        if (preg_match_all('/<(?:[\w.-]+:)?loc\b[^>]*>\s*([^<]+?)\s*<\/(?:[\w.-]+:)?loc>/iu', $contenido, $matches)) {
            foreach ($matches[1] as $url) {
                $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($url !== '') {
                    $urls[] = $url;
                }
            }
        }

        if ($urls === [] && preg_match_all(
            '#https?://(?:www\.)?pccomponentes\.com/[a-z0-9][a-z0-9/-]*#iu',
            $contenido,
            $m2
        )) {
            foreach ($m2[0] as $url) {
                $urls[] = trim($url);
            }
        }

        return $urls;
    }

    /**
     * URL de ficha en pccomponentes.com (cualquier categoría).
     * Los sitemaps de categoría solo incluyen productos en &lt;loc&gt;; aquí se excluyen
     * sub-sitemaps .xml y secciones genéricas de la web (blog, carrito, etc.), no slugs de categoría.
     */
    private function esUrlProductoPccomponentesDesdeSitemap(string $url): bool
    {
        $partes = parse_url($url);
        if ($partes === false || empty($partes['host'])) {
            return false;
        }

        if (!preg_match('/(?:^|\.)pccomponentes\.com$/iu', (string) $partes['host'])) {
            return false;
        }

        if (!empty($partes['query']) || !empty($partes['fragment'])) {
            return false;
        }

        $path = rtrim((string) ($partes['path'] ?? ''), '/');
        if ($path === '' || $path === '/') {
            return false;
        }

        if (preg_match('/\.xml$/iu', $path) || preg_match('/sitemap/iu', $path)) {
            return false;
        }

        if (!preg_match('#^/(?:[a-z0-9][a-z0-9-]*)(?:/[a-z0-9][a-z0-9-]*)*$#iu', $path)) {
            return false;
        }

        $pathLower = mb_strtolower($path, 'UTF-8');
        $prefijosNoProducto = [
            '/blog',
            '/ayuda',
            '/servicios',
            '/carrito',
            '/login',
            '/cuenta',
            '/usuario',
            '/legal',
            '/contacto',
            '/empresa',
            '/comunidad',
            '/ofertas-especiales',
        ];

        foreach ($prefijosNoProducto as $prefijo) {
            if ($pathLower === $prefijo || str_starts_with($pathLower, $prefijo . '/')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verifica si el HTML contiene el mensaje de error 404 de PC Componentes
     */
    private function esPagina404(string $html): bool
    {
        return strpos($html, '<div class="content-message">Nuestro equipo de expertos en tecnología no puede encontrar lo que buscas...') !== false;
    }

    /**
     * Verifica si el producto es reacondicionado buscando la palabra en la etiqueta <title>
     */
    private function esReacondicionado(string $html): bool
    {
        // Buscar la etiqueta <title> con cualquier atributo (ej: <title data-rh="true">...)
        if (preg_match('/<title[^>]*>(.*?)<\/title>/i', $html, $matches)) {
            $titulo = $matches[1];
            // Buscar "reacondicionado" o "Reacondicionado" (case-insensitive)
            return stripos($titulo, 'reacondicionado') !== false;
        }
        
        return false;
    }

    /**
     * Intenta extraer el precio usando varios patrones (en orden de fiabilidad):
     * 1) JSON: {"@type":"AggregateOffer","highPrice":"332.47"}
     * 2) Span tipografía: <span class="typography-module_body2Bold__...">332,47€</span>
     * 3) IDs de la PDP (DOM/XPath): pdp-price-current-integer / pdp-price-current-decimals
     * 4) Regex sobre los IDs (fallback)
     */
    private function extraerPrecio(string $html): ?float
    {
        $p = $this->precioDesdeAggregateOfferHighPrice($html);
        if ($p !== null) return $p;

        $p = $this->precioDesdeTypographyBody2Bold($html);
        if ($p !== null) return $p;

        $p = $this->precioDesdeDomPdpIds($html);
        if ($p !== null) return $p;

        // Último recurso (regex)
        return $this->precioDesdeSpansPDP($html);
    }

    /**
     * Busca en JSON el patrón de AggregateOffer con highPrice.
     * Ej: {"@type":"AggregateOffer","highPrice":"332.47"}
     */
    private function precioDesdeAggregateOfferHighPrice(string $html): ?float
    {
        // Primera opción: buscar ,"price":"valor",
        if (preg_match('/,"price"\s*:\s*"(?<p>\d+(?:[.,]\d{1,2})?)",/i', $html, $m3)) {
            return $this->toNumber($m3['p']);
        }

        if (preg_match('/"@type"\s*:\s*"AggregateOffer"[\s\S]*?"highPrice"\s*:\s*"(?<p>\d+(?:[.,]\d{1,2})?)"/i', $html, $m)) {
            return $this->toNumber($m['p']);
        }

        // Fallback sin @type cerca (por si el JSON viene minificado/alterado)
        if (preg_match('/"highPrice"\s*:\s*"(?<p>\d+(?:[.,]\d{1,2})?)"/i', $html, $m2)) {
            return $this->toNumber($m2['p']);
        }

        return null;
    }

    /**
     * Busca un precio visible tipo "332,47€" en spans de tipografía.
     * Ej:
     * <span class="typography-module_body2Bold__eCMcx">332,47€</span>
     */
    private function precioDesdeTypographyBody2Bold(string $html): ?float
    {
        // Match flexible: clase que empiece por typography-module_body2Bold__ (hash variable)
        if (preg_match('/<span[^>]*class=["\'][^"\']*typography-module_body2Bold__[^"\']*["\'][^>]*>\s*(?<p>[\d\.,]+)\s*€\s*<\/span>/i', $html, $m)) {
            return $this->toNumber($m['p']);
        }

        // Variante por si el símbolo € va pegado o hay etiquetas extra alrededor
        if (preg_match('/typography-module_body2Bold__[^"\']*["\'][^>]*>[\s\S]*?(?<p>\d+(?:[.,]\d{1,2})?)\s*€/i', $html, $m2)) {
            return $this->toNumber($m2['p']);
        }

        return null;
    }

    /**
     * Extrae precio usando DOM/XPath (más robusto ante spans anidados).
     */
    private function precioDesdeDomPdpIds(string $html): ?float
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        @$dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $intNode = $xpath->query('//*[@id="pdp-price-current-integer"]')->item(0);
        if (!$intNode) return null;

        // El nodo entero suele tener un DOMText con "332" y luego un child <span> de decimales.
        $parteEntera = null;
        foreach ($intNode->childNodes as $child) {
            if ($child instanceof \DOMText) {
                $txt = trim($child->wholeText);
                $txt = preg_replace('/[^\d]/', '', $txt);
                if ($txt !== '') {
                    $parteEntera = $txt;
                    break;
                }
            }
        }

        // Fallback si no hay DOMText (raro): usa el textContent y se queda con el primer bloque de dígitos
        if ($parteEntera === null) {
            $txt = preg_replace('/[^\d]/', '', (string) $intNode->textContent);
            if ($txt !== '') {
                // OJO: aquí podría venir también el decimal; nos quedamos con el inicio (entero)
                // Si viniera "33247" no podemos separar sin más, por eso preferimos el DOMText.
                $parteEntera = $txt;
            }
        }

        $decNode = $xpath->query('//*[@id="pdp-price-current-decimals"]')->item(0);
        $parteDecimal = null;
        if ($decNode) {
            $txt = (string) $decNode->textContent; // ej: ",47€"
            if (preg_match('/(\d{1,2})\s*€?/', $txt, $m)) {
                $parteDecimal = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            }
        }

        if ($parteEntera !== null && $parteEntera !== '') {
            $precioCompleto = $parteEntera . '.' . ($parteDecimal !== null && $parteDecimal !== '' ? $parteDecimal : '00');
            $precio = (float) $precioCompleto;
            return $precio > 0 ? $precio : null;
        }

        return null;
    }

    /**
     * Extrae el precio desde los spans con IDs:
     * - pdp-price-current-integer: parte entera (ej: 332)
     * - pdp-price-current-decimals: parte decimal con separador (ej: ,47)
     * 
     * Formato real (anidado):
     * <span id="pdp-price-current-integer">332<span id="pdp-price-current-decimals">,47</span></span>
     * 
     * Resultado: 332.47
     */
    private function precioDesdeSpansPDP(string $html): ?float
    {
        // Método 0 (preferido): capturar entero + decimales en un único bloque, evitando problemas
        // con spans anidados dentro de "pdp-price-current-decimals" (separador, comentarios, etc.)
        //
        // Ejemplo real:
        // <span id="pdp-price-current-integer">332
        //   <span id="pdp-price-current-decimals"><span ...>,</span>47<!-- -->€</span>
        // </span>
        if (preg_match(
            '/<span[^>]*id=["\']pdp-price-current-integer["\'][^>]*>\s*(?<int>\d+)\s*'
            . '<span[^>]*id=["\']pdp-price-current-decimals["\'][^>]*>[\s\S]*?(?<dec>\d{1,2})[\s\S]*?<\/span>/i',
            $html,
            $m0
        )) {
            $parteEntera   = $m0['int'];
            $parteDecimal  = str_pad($m0['dec'], 2, '0', STR_PAD_LEFT);
            $precioCompleto = $parteEntera . '.' . $parteDecimal;
            $precio = (float) $precioCompleto;
            return $precio > 0 ? $precio : null;
        }

        // Método 1: Extraer parte entera - texto directo antes del siguiente tag
        $parteEntera = null;
        // Buscar el span y capturar el texto inmediatamente después de >
        if (preg_match('/<span[^>]*id=["\']pdp-price-current-integer["\'][^>]*>([^<]*?)(?:<|$)/i', $html, $mEntera)) {
            $parteEntera = preg_replace('/[^\d]/', '', trim($mEntera[1]));
        }
        
        // Si no encontramos con el método 1, intentar capturar todo el contenido y extraer solo números
        if (empty($parteEntera)) {
            if (preg_match('/<span[^>]*id=["\']pdp-price-current-integer["\'][^>]*>([\s\S]*?)<\/span>/i', $html, $mEntera2)) {
                // Extraer solo los números del inicio (antes del span anidado de decimales)
                $contenido = $mEntera2[1];
                // Buscar números al inicio antes de cualquier tag
                if (preg_match('/^([\d]+)/', $contenido, $mNum)) {
                    $parteEntera = $mNum[1];
                }
            }
        }

        // Extraer parte decimal: capturar todo el contenido del span (puede tener spans anidados)
        $parteDecimal = null;
        // Ojo: dentro suele haber un <span> del separador y comentarios; obligamos a que aparezcan dígitos
        // antes del cierre, para no quedarnos con el </span> interno.
        if (preg_match('/<span[^>]*id=["\']pdp-price-current-decimals["\'][^>]*>[\s\S]*?(\d{1,2})[\s\S]*?<\/span>/i', $html, $mDecimal)) {
            $parteDecimal = str_pad($mDecimal[1], 2, '0', STR_PAD_LEFT);
        }

        // Si tenemos parte entera, procesar
        if ($parteEntera !== null && $parteEntera !== '') {
            // Si tenemos decimales, combinarlos; si no, usar .00
            if ($parteDecimal !== null && $parteDecimal !== '') {
                $precioCompleto = $parteEntera . '.' . $parteDecimal;
            } else {
                $precioCompleto = $parteEntera . '.00';
            }
            
            $precio = (float) $precioCompleto;
            return $precio > 0 ? $precio : null;
        }

        return null;
    }

    /**
     * Normaliza texto de precio a float (sin símbolo €, sin %).
     * Acepta "1543,70", "332.47", "1.543,70", etc.
     */
    private function toNumber(string $raw): ?float
    {
        $txt = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (strpos($txt, '%') !== false) return null;

        // Mantener solo dígitos y separadores , .
        $txt = preg_replace('/[^\d.,]/u', '', $txt);
        if ($txt === '' || $txt === null) return null;

        // Sin separadores -> entero
        if (strpos($txt, ',') === false && strpos($txt, '.') === false) {
            return (float) $txt;
        }

        // Último separador como decimal
        $lastComma = strrpos($txt, ',');
        $lastDot   = strrpos($txt, '.');
        $decPos    = max($lastComma !== false ? $lastComma : -1, $lastDot !== false ? $lastDot : -1);

        $intPart = substr($txt, 0, $decPos);
        $decPart = substr($txt, $decPos + 1);

        $intPart = preg_replace('/[^\d]/', '', $intPart);
        $decPart = preg_replace('/[^\d]/', '', $decPart);

        if ($intPart === '') return null;

        $norm = $decPart === '' ? $intPart . '.00' : $intPart . '.' . substr($decPart, 0, 2);
        return (float) $norm;
    }
}

