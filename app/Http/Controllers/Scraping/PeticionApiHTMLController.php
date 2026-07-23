<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PeticionApiHTMLController extends Controller
{
    /** HTML enviado por el programa externo (una petición por oferta). */
    private static ?string $htmlInyectado = null;

    /** Último log de petición API (sin HTML completo), para vistas de diagnóstico como test-precio. */
    private static ?array $ultimoLogApi = null;

    /** PROVEEDOR PREFERIDO: 'bright_unlocker' | 'scrapingant' | 'scrapestack' */
    private $apiProveedorPreferido;

    /** FAILOVER: 'si' para intentar con otros proveedores si falla el primero; 'no' para no hacerlo */
    private $usarFailover;

    // ==== Mi VPS (HTML completo) ====
    private $miVpsApiUrl;

    // ==== ScrapingAnt (RapidAPI) ====
    private $apiKeyScrapingAnt;
    private $apiUrlScrapingAnt;

    // ==== Scrapestack ====
    private $apiKeyScrapestack;
    private $apiUrlScrapestack;

    // ==== Bright Data Web Unlocker (REST /request) ====
    private $brightDataApiKey;
    private $brightDataZone;
    private $brightDataApiUrl;

    // ==== AliExpress Open Platform (Business/System/Affiliate) ====
    private $aliAppKey;
    private $aliAppSecret;
    private $aliBaseSync;
    private $aliBaseRest;

    // ==== Amazon Creators API (sustituye PA-API 5.0; OAuth 2.0, v3.2 EU) ====
    /** Segundos de espera antes de cada petición a la API (límite típico 1 petición/segundo). 0 = no esperar. */
    private $amazonCreatorsEsperaSegundos = 1;
    private $amazonCreatorsCredentialId;
    private $amazonCreatorsCredentialSecret;
    private $amazonCreatorsPartnerTag;
    private $amazonCreatorsMarketplace;
    private $amazonCreatorsTokenUrl;
    private $amazonCreatorsApiUrl;
    private $amazonCreatorsSearchApiUrl;
    private const AMAZON_CREATORS_CACHE_KEY = 'amazon_creators_api_token';
    private const AMAZON_CREATORS_TOKEN_TTL_SECONDS = 3500; // Renovar antes de 3600

    // ==== Amazon Product Info API (RapidAPI) ====
    private $amazonProductInfoApiKey;
    private $amazonProductInfoApiUrl;

    // ==== Amazon Pricing And Product Info API (RapidAPI) ====
    private $amazonPricingApiKey;
    private $amazonPricingApiUrl;

    // ==== Cloudflare Browser Rendering (HTML renderizado con JS) ====
    private $cloudflareAccountId;
    private $cloudflareApiToken;

    public static function setHtmlInyectado(?string $html): void
    {
        self::$htmlInyectado = $html;
    }

    public static function clearHtmlInyectado(): void
    {
        self::$htmlInyectado = null;
    }

    /**
     * Log de la última llamada a obtenerHTML (sin volcar HTML gigante).
     *
     * @return array<string, mixed>|null
     */
    public static function getUltimoLogApi(): ?array
    {
        return self::$ultimoLogApi;
    }

    public static function clearUltimoLogApi(): void
    {
        self::$ultimoLogApi = null;
    }

    public function __construct()
    {
        $this->apiProveedorPreferido = env('SCRAPING_PROVEEDOR_PREFERIDO', 'bright_unlocker');
        $this->usarFailover = env('SCRAPING_USAR_FAILOVER', 'no');
        $this->miVpsApiUrl = env('SCRAPING_MI_VPS_API_URL', 'http://51.38.184.245/obtener-html');
        $this->apiKeyScrapingAnt = env('SCRAPING_SCRAPINGANT_API_KEY');
        $this->apiUrlScrapingAnt = env('SCRAPING_SCRAPINGANT_API_URL', 'https://scrapingant.p.rapidapi.com/get');
        $this->apiKeyScrapestack = env('SCRAPING_SCRAPESTACK_API_KEY');
        $this->apiUrlScrapestack = env('SCRAPING_SCRAPESTACK_API_URL', 'https://api.scrapestack.com/scrape');
        $this->brightDataApiKey = env('SCRAPING_BRIGHTDATA_API_KEY');
        $this->brightDataZone = env('SCRAPING_BRIGHTDATA_ZONE', 'chollopanales');
        $this->brightDataApiUrl = env('SCRAPING_BRIGHTDATA_API_URL', 'https://api.brightdata.com/request');
        $this->aliAppKey = env('SCRAPING_ALI_APP_KEY');
        $this->aliAppSecret = env('SCRAPING_ALI_APP_SECRET');
        $this->aliBaseSync = env('SCRAPING_ALI_BASE_SYNC', 'https://api-sg.aliexpress.com/sync');
        $this->aliBaseRest = env('SCRAPING_ALI_BASE_REST', 'https://api-sg.aliexpress.com/rest');
        $this->amazonCreatorsCredentialId = env('SCRAPING_AMAZON_CREATORS_CREDENTIAL_ID');
        $this->amazonCreatorsCredentialSecret = env('SCRAPING_AMAZON_CREATORS_CREDENTIAL_SECRET');
        $this->amazonCreatorsPartnerTag = env('SCRAPING_AMAZON_CREATORS_PARTNER_TAG', 'srto-21');
        $this->amazonCreatorsMarketplace = env('SCRAPING_AMAZON_CREATORS_MARKETPLACE', 'www.amazon.es');
        $this->amazonCreatorsTokenUrl = env('SCRAPING_AMAZON_CREATORS_TOKEN_URL', 'https://api.amazon.co.uk/auth/o2/token');
        $this->amazonCreatorsApiUrl = env('SCRAPING_AMAZON_CREATORS_API_URL', 'https://creatorsapi.amazon/catalog/v1/getItems');
        $this->amazonCreatorsSearchApiUrl = env(
            'SCRAPING_AMAZON_CREATORS_SEARCH_API_URL',
            'https://creatorsapi.amazon/catalog/v1/searchItems'
        );
        $this->amazonProductInfoApiKey = env('SCRAPING_AMAZON_PRODUCT_INFO_API_KEY');
        $this->amazonProductInfoApiUrl = env('SCRAPING_AMAZON_PRODUCT_INFO_API_URL', 'https://amazon-product-info2.p.rapidapi.com/Amazon/details');
        $this->amazonPricingApiKey = env('SCRAPING_AMAZON_PRICING_API_KEY');
        $this->amazonPricingApiUrl = env('SCRAPING_AMAZON_PRICING_API_URL', 'https://amazon-pricing-and-product-info.p.rapidapi.com/');
        $this->cloudflareAccountId = env('SCRAPING_CLOUDFLARE_ACCOUNT_ID');
        $this->cloudflareApiToken = env('SCRAPING_CLOUDFLARE_API_TOKEN');
    }

    /**
     * Intenta con el proveedor preferido y, si $usarFailover === 'si' y falla, prueba con los otros.
     * Puedes forzar proveedor con $forzarProveedor ('bright_unlocker'|'scrapingant'|'scrapestack'|'aliexpress_open'|'vps_html').
     * Si se proporciona $apiTienda, se usará esa API específica.
     *
     * @param  string|null  $vpsCargarMasSelector  Selector CSS opcional (p. ej. #yith-infs-button); solo se envía al VPS.
     */
    public function obtenerHTML(string $url, ?string $forzarProveedor = null, ?string $apiTienda = null, ?string $vpsCargarMasSelector = null): array
    {
        $tiempoInicio = microtime(true);

        if (self::$htmlInyectado !== null && self::$htmlInyectado !== '') {
            $html = self::$htmlInyectado;
            self::$htmlInyectado = null;

            $resultado = [
                'success'   => true,
                'html'      => "<!-- PROVEEDOR: NAVEGADOR_LOCAL -->\n" . $html,
                'proveedor' => 'navegador_local',
            ];

            return $this->devolverYRegistrarLogApi($resultado, $url, $apiTienda, $forzarProveedor, $tiempoInicio);
        }

        if ($apiTienda && strtolower($apiTienda) === 'navegadorlocal') {
            return $this->devolverYRegistrarLogApi([
                'success' => false,
                'error'   => 'Esta tienda usa navegador local; el HTML debe enviarse desde el programa externo (API scraping-programa-externo).',
                'proveedor' => 'navegador_local',
            ], $url, $apiTienda, $forzarProveedor, $tiempoInicio);
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');

        if ($apiTienda) {
            $forzarProveedor = $this->mapearApiTienda($apiTienda);
        }

        $prefer = $forzarProveedor ?: $this->apiProveedorPreferido;
        $todos = ['bright_unlocker', 'scrapingant', 'scrapestack', 'cloudflare', 'aliexpress_open', 'vps_html', 'amazon_api', 'amazon_product_info', 'amazon_pricing'];
        $proveedores = array_values(array_unique(array_merge([$prefer], $todos)));

        if ($this->usarFailover !== 'si') {
            $proveedores = [$prefer];
        }

        $errores = [];
        $ultimoResultadoFallido = null;
        foreach ($proveedores as $prov) {
            $resultado = $this->llamarProveedor($prov, $url, $apiTienda, $vpsCargarMasSelector);
            if ($prov === 'vps_html' && empty($resultado['vps_log'])) {
                $resultado['vps_log'] = $this->construirVpsHtmlLogFallbackDesdeResultado($resultado);
            }

            if (!empty($resultado['success'])) {
                // Proveedores HTML -> validamos 'html' y anotamos proveedor
                if (in_array($prov, ['bright_unlocker', 'scrapingant', 'scrapestack', 'cloudflare', 'vps_html'], true)) {
                    if (!isset($resultado['html']) || $resultado['html'] === '') {
                        return $this->devolverYRegistrarLogApi(
                            ['success' => false, 'error' => 'Respuesta vacia de ' . $prov, 'proveedor' => $prov],
                            $url,
                            $apiTienda,
                            $forzarProveedor,
                            $tiempoInicio
                        );
                    }
                    if (stripos($resultado['html'], 'PROVEEDOR:') === false) {
                        $resultado['html'] = "<!-- PROVEEDOR: " . strtoupper($prov) . " -->\n" . $resultado['html'];
                    }
                    $resultado['proveedor'] = $prov;
                }
                return $this->devolverYRegistrarLogApi($resultado, $url, $apiTienda, $forzarProveedor, $tiempoInicio);
            }
            $ultimoResultadoFallido = $resultado;
            $errores[$prov] = $resultado['error'] ?? 'Error desconocido';
        }

        $fallo = [
            'success' => false,
            'error' => $this->formatearErrores($errores),
            'proveedor' => $prefer,
        ];
        if (is_array($ultimoResultadoFallido) && !empty($ultimoResultadoFallido['vps_log'])) {
            $fallo['vps_log'] = $ultimoResultadoFallido['vps_log'];
        }

        return $this->devolverYRegistrarLogApi($fallo, $url, $apiTienda, $forzarProveedor, $tiempoInicio);
    }

    /**
     * Guarda un log diagnóstico (sin HTML) y devuelve el resultado original.
     *
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function devolverYRegistrarLogApi(
        array $resultado,
        string $url,
        ?string $apiTienda,
        ?string $forzarProveedor,
        float $tiempoInicio
    ): array {
        $ms = round((microtime(true) - $tiempoInicio) * 1000, 2);
        $log = [
            'url' => $url,
            'api_tienda' => $apiTienda,
            'forzar_proveedor' => $forzarProveedor,
            'proveedor' => $resultado['proveedor'] ?? null,
            'success' => $resultado['success'] ?? null,
            'error' => $resultado['error'] ?? null,
            'tiempo_api_ms' => $ms,
            'html_length' => isset($resultado['html']) && is_string($resultado['html'])
                ? strlen($resultado['html'])
                : null,
        ];

        if (!empty($resultado['vps_log']) && is_array($resultado['vps_log'])) {
            $log['vps_log'] = $resultado['vps_log'];
        }
        if (!empty($resultado['debug']) && is_array($resultado['debug'])) {
            $log['debug'] = $resultado['debug'];
        }
        if (array_key_exists('precio', $resultado) && $resultado['precio'] !== null) {
            $log['precio_api'] = $resultado['precio'];
        }
        if (!empty($resultado['raw']) && is_array($resultado['raw'])) {
            $log['raw_keys'] = array_keys($resultado['raw']);
        }

        self::$ultimoLogApi = $log;

        return $resultado;
    }

    private function llamarProveedor(string $proveedor, string $url, ?string $apiTienda = null, ?string $vpsCargarMasSelector = null): array
    {
        try {
            if ($proveedor === 'bright_unlocker') return $this->callBrightDataUnlocker($url, $apiTienda);
            if ($proveedor === 'scrapingant')     return $this->callScrapingAnt($url);
            if ($proveedor === 'scrapestack')     return $this->callScrapestack($url);
            if ($proveedor === 'cloudflare')      return $this->callCloudflareContent($url, $apiTienda);
            if ($proveedor === 'vps_html')        return $this->callMiVpsHtml($url, $apiTienda, $vpsCargarMasSelector);
            if ($proveedor === 'aliexpress_open') return $this->callAliExpressOpen($url, $apiTienda);
            if ($proveedor === 'amazon_api')      return $this->callAmazonAPI($url, $apiTienda);
            if ($proveedor === 'amazon_product_info') return $this->callAmazonProductInfo($url, $apiTienda);
            if ($proveedor === 'amazon_pricing')  return $this->callAmazonPricing($url, $apiTienda);

            return ['success' => false, 'error' => 'Proveedor no valido. Usa "bright_unlocker", "scrapingant", "scrapestack", "cloudflare", "aliexpress_open", "vps_html", "amazon_api", "amazon_product_info" o "amazon_pricing".'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Excepcion en ' . $proveedor . ': ' . $e->getMessage()];
        }
    }

    /* =================== BRIGHT DATA =================== */

    private function callBrightDataUnlocker(string $url, ?string $apiTienda = null): array
    {
        if (!$this->brightDataApiKey || !$this->brightDataZone) {
            return ['success' => false, 'error' => 'BrightData Unlocker: API key o zone no configurados'];
        }

        $config = $apiTienda ? $this->obtenerConfiguracionBrightData($apiTienda) : ['render' => false];

        $configuraciones = [
            ['zone' => $this->brightDataZone, 'url' => $url, 'format' => 'raw', 'render' => $config['render'], 'headers' => $config['headers'] ?? null],
            ['zone' => $this->brightDataZone, 'url' => $url, 'format' => 'raw', 'render' => $config['render']],
            ['zone' => $this->brightDataZone, 'url' => $url, 'format' => 'raw'],
        ];

        foreach ($configuraciones as $index => $payload) {
            try {
                $resp = Http::timeout(90)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                        'Content-Type'  => 'application/json',
                    ])
                    ->post($this->brightDataApiUrl, $payload);

                if (!$resp->successful()) continue;

                $body = (string) $resp->body();
                if ($body === '') continue;

                $body = "<!-- PROVEEDOR: BRIGHTDATA_UNLOCKER (config " . ($index + 1) . ") -->\n" . $body;
                return ['success' => true, 'html' => $body];

            } catch (\Throwable $e) {
                continue;
            }
        }

        return ['success' => false, 'error' => 'BrightData Unlocker: Todas las configuraciones fallaron. Verificar zona: ' . $this->brightDataZone . ' y API key'];
    }

    /* =================== SCRAPINGANT =================== */

    private function callScrapingAnt(string $url): array
    {
        $response = Http::timeout(60)
            ->withHeaders([
                'x-rapidapi-host' => 'scrapingant.p.rapidapi.com',
                'x-rapidapi-key'  => $this->apiKeyScrapingAnt,
            ])
            ->get($this->apiUrlScrapingAnt, [
                'url'             => $url,
                'proxy_country'   => 'ES',
                'response_format' => 'html',
            ]);

        if ($response->successful()) {
            $body = (string) $response->body();

            $ct = strtolower((string) $response->header('Content-Type'));
            if (strpos($ct, 'application/json') !== false) {
                $json = json_decode($body, true);
                if (json_last_error() === JSON_ERROR_NONE && isset($json['error'])) {
                    return ['success' => false, 'error' => 'ScrapingAnt error: ' . (is_string($json['error']) ? $json['error'] : json_encode($json['error']))];
                }
            }

            if ($body !== '') {
                $body = "<!-- PROVEEDOR: SCRAPINGANT -->\n" . $body;
                return ['success' => true, 'html' => $body];
            }

            return ['success' => false, 'error' => 'ScrapingAnt 200 con cuerpo vacio'];
        }

        return ['success' => false, 'error' => 'ScrapingAnt ' . $response->status() . ' - ' . $response->body()];
    }

    /* =================== SCRAPESTACK =================== */

    private function callScrapestack(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        $overrides = [
            'www.amazon.es' => ['render_js' => 0, 'proxy' => false, 'keep_headers' => 0],
            'amazon.es'     => ['render_js' => 0, 'proxy' => false, 'keep_headers' => 0],
        ];

        $estrategias = [
            ['render_js' => 0, 'proxy' => false, 'keep_headers' => 0],
            ['render_js' => 0, 'proxy' => true,  'keep_headers' => 0],
            ['render_js' => 1, 'proxy' => false, 'keep_headers' => 0],
            ['render_js' => 1, 'proxy' => true,  'keep_headers' => 0],
        ];

        if (isset($overrides[$host])) $estrategias = [ $overrides[$host] ];

        $headers = [
            'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, como Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language' => 'es-ES,es;q=0.9',
            'Connection'      => 'keep-alive',
        ];

        $maxAttempts = 2;
        $baseSleepMs = 400;
        $ultimoError = null;

        foreach ($estrategias as $cfg) {
            for ($i = 1; $i <= $maxAttempts; $i++) {
                $query = [
                    'access_key' => $this->apiKeyScrapestack,
                    'url'        => $url,
                    'render_js'  => $cfg['render_js'] ? 1 : 0,
                ];
                if ($cfg['proxy']) $query['proxy_location'] = 'es';
                if ($cfg['keep_headers']) $query['keep_headers'] = 1;

                try {
                    $resp  = Http::timeout(60)->withHeaders($headers)->get($this->apiUrlScrapestack, $query);
                    $code  = $resp->status();
                    $ctype = strtolower((string) $resp->header('Content-Type'));
                    $body  = (string) $resp->body();

                    $json = (strpos($ctype, 'application/json') !== false && $body !== '')
                        ? json_decode($body, true)
                        : null;

                    if ($resp->successful() && $body !== '') {
                        if (is_array($json) && isset($json['success']) && $json['success'] === false) {
                            $ultimoError = 'Scrapestack 200 success=false: ' . json_encode($json['error'] ?? $json);
                        } else {
                            $body = "<!-- PROVEEDOR: SCRAPESTACK -->\n" . $body;
                            return ['success' => true, 'html' => $body];
                        }
                    }

                    if (is_array($json) && isset($json['success']) && $json['success'] === false) {
                        $ultimoError = 'Scrapestack error: ' . json_encode($json['error']);
                    } else {
                        $snippet = mb_substr($body, 0, 600);
                        $ultimoError = "Scrapestack HTTP $code (" . ($ctype ?: 'sin content-type') . ")"
                                     . ($snippet !== '' ? " Cuerpo: $snippet" : " (cuerpo vacio)");
                    }

                    $reintentar = in_array($code, [0, 403, 409, 425, 429, 500, 502, 503, 504], true) || $body === '';
                    if ($i < $maxAttempts && $reintentar) {
                        $sleepMs = $baseSleepMs * (2 ** ($i - 1)) + random_int(0, 200);
                        usleep($sleepMs * 1000);
                        continue;
                    }
                } catch (\Throwable $e) {
                    $ultimoError = 'Excepcion HTTP: ' . $e->getMessage();
                    if ($i < $maxAttempts) {
                        $sleepMs = $baseSleepMs * (2 ** ($i - 1)) + random_int(0, 200);
                        usleep($sleepMs * 1000);
                        continue;
                    }
                }

                break; // siguiente estrategia
            }
        }

        return ['success' => false, 'error' => $ultimoError ?: 'Error desconocido'];
    }

    /* =================== CLOUDFLARE BROWSER RENDERING =================== */

    private function callCloudflareContent(string $url, ?string $apiTienda = null): array
    {
        if (!$this->cloudflareAccountId || !$this->cloudflareApiToken) {
            return ['success' => false, 'error' => 'Cloudflare Browser Rendering: faltan account_id o api_token'];
        }

        $apiUrl = 'https://api.cloudflare.com/client/v4/accounts/' . $this->cloudflareAccountId . '/browser-rendering/content';

        $body = [
            'url' => $url,
            'gotoOptions' => ['waitUntil' => 'networkidle0'],
        ];

        try {
            $resp = Http::timeout(90)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->cloudflareApiToken,
                    'Content-Type'  => 'application/json',
                ])
                ->post($apiUrl, $body);

            if (!$resp->successful()) {
                return ['success' => false, 'error' => 'Cloudflare Browser Rendering HTTP ' . $resp->status() . ' - ' . mb_substr($resp->body(), 0, 500)];
            }

            $json = $resp->json();
            if (!is_array($json)) {
                return ['success' => false, 'error' => 'Cloudflare Browser Rendering: respuesta no JSON'];
            }
            if (!empty($json['success']) && isset($json['result']) && $json['result'] !== '') {
                return [
                    'success'   => true,
                    'html'      => "<!-- PROVEEDOR: CLOUDFLARE -->\n" . (string) $json['result'],
                    'proveedor' => 'cloudflare',
                ];
            }
            $msg = isset($json['errors'][0]['message']) ? $json['errors'][0]['message'] : ($resp->body() ?: 'Sin contenido');
            return ['success' => false, 'error' => 'Cloudflare Browser Rendering: ' . $msg];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Cloudflare Browser Rendering Exception: ' . $e->getMessage()];
        }
    }

    /* =================== MI VPS =================== */

    private function callMiVpsHtml(string $url, ?string $apiTienda = null, ?string $vpsCargarMasSelector = null): array
    {
        try {
            $mode = 'auto';
            $wait = 0.5;
            $useProxy = false;

            if ($apiTienda && stripos($apiTienda, 'mivpshtml') === 0) {
                $parts   = explode(';', $apiTienda, 2);
                $variant = $parts[1] ?? '';
                switch ($variant) {
                    case '1': $mode = 'auto';     $wait = 0.8; break;
                    case '2': $mode = 'selenium'; $wait = 1.2; break;
                    case '3': $mode = 'selenium'; $wait = 1.8; break;
                    case '4': $mode = 'requests'; $wait = 0.0; break;
                    case '5': $mode = 'proxy';    $wait = 1.0; $useProxy = true; break; // Nueva opción con proxies
                }
            }

            $payload = [
                'url'             => $url,
                'mode'            => $mode,
                'wait_after_load' => $wait,
                'use_proxy'        => $useProxy,
            ];

            $sel = $vpsCargarMasSelector !== null ? trim($vpsCargarMasSelector) : '';
            if ($sel !== '') {
                $payload['cargar_mas_selector'] = $sel;
            }

            $resp = Http::timeout(90)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post($this->miVpsApiUrl, $payload);

            if (!$resp->successful()) {
                return [
                    'success' => false,
                    'error' => 'VPS_HTML HTTP ' . $resp->status() . ' - ' . mb_substr($resp->body(), 0, 600),
                    'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), null, (string) $resp->body()),
                ];
            }

            $json = $resp->json();
            if (!is_array($json)) {
                return [
                    'success' => false,
                    'error' => 'VPS_HTML: respuesta no JSON',
                    'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), null, (string) $resp->body()),
                ];
            }
            if (!empty($json['error'])) {
                return [
                    'success' => false,
                    'error' => 'VPS_HTML: ' . (is_string($json['error']) ? $json['error'] : json_encode($json['error'])),
                    'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), $json, null),
                ];
            }
            if (!empty($json['html_b64'])) {
                $raw = base64_decode($json['html_b64'], true);
                if ($raw === false) {
                    return [
                        'success' => false,
                        'error' => 'VPS_HTML: html_b64 invalido',
                        'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), $json, null),
                    ];
                }
                $provider = $useProxy ? 'VPS_HTML_PROXY' : 'VPS_HTML';

                return [
                    'success' => true,
                    'html' => "<!-- PROVEEDOR: {$provider} -->\n" . $raw,
                    'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), $json, null),
                ];
            }
            if (!empty($json['html'])) {
                $provider = $useProxy ? 'VPS_HTML_PROXY' : 'VPS_HTML';

                return [
                    'success' => true,
                    'html' => "<!-- PROVEEDOR: {$provider} -->\n" . (string) $json['html'],
                    'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), $json, null),
                ];
            }

            return [
                'success' => false,
                'error' => 'VPS_HTML: respuesta sin html_b64 ni html',
                'vps_log' => $this->construirVpsHtmlLog($payload, $resp->status(), $json, null),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => 'VPS_HTML Exception: ' . $e->getMessage(),
                'vps_log' => [
                    'tipo' => 'vps_html',
                    'endpoint' => $this->miVpsApiUrl ?? null,
                    'http_status' => null,
                    'request_payload' => [
                        'url' => $url,
                        'nota' => 'Excepción antes de recibir respuesta HTTP del VPS',
                    ],
                    'exception' => [
                        'class' => get_class($e),
                        'message' => $e->getMessage(),
                    ],
                ],
            ];
        }
    }

    /**
     * Cuando callMiVpsHtml no devolvió vps_log (despliegue antiguo u otro fallo), la prueba Neoobjetivos
     * puede seguir mostrando un bloque con longitud de HTML y aviso.
     *
     * @param  array<string, mixed>  $resultado
     * @return array<string, mixed>
     */
    private function construirVpsHtmlLogFallbackDesdeResultado(array $resultado): array
    {
        return [
            'tipo' => 'vps_html',
            'aviso' => 'No se recibió vps_log desde callMiVpsHtml. Despliega la última versión de PeticionApiHTMLController (método callMiVpsHtml con vps_log) o revisa logs del servidor.',
            'html_length' => strlen($resultado['html'] ?? ''),
            'success' => $resultado['success'] ?? null,
            'error' => $resultado['error'] ?? null,
        ];
    }

    /**
     * Log legible para vistas de prueba / diagnóstico (sin volcar HTML gigante).
     *
     * @param  array<string, mixed>|null  $json
     */
    private function construirVpsHtmlLog(array $payload, int $httpStatus, ?array $json, ?string $rawBodyFallback): array
    {
        $log = [
            'tipo' => 'vps_html',
            'endpoint' => $this->miVpsApiUrl,
            'http_status' => $httpStatus,
            'request_payload' => $payload,
        ];
        if (is_array($json)) {
            $log['response_json'] = $this->sanearJsonRespuestaVpsHtmlParaLog($json);
        }
        if ($rawBodyFallback !== null && $rawBodyFallback !== '') {
            $max = 14000;
            $log['raw_body'] = strlen($rawBodyFallback) > $max
                ? mb_substr($rawBodyFallback, 0, $max) . "\n...[recortado, longitud_original=" . strlen($rawBodyFallback) . "]"
                : $rawBodyFallback;
        }

        return $log;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function sanearJsonRespuestaVpsHtmlParaLog(array $json): array
    {
        $out = $json;
        if (isset($out['html_b64']) && is_string($out['html_b64'])) {
            $len = strlen($out['html_b64']);
            $decoded = base64_decode($out['html_b64'], true);
            $htmlLen = is_string($decoded) ? strlen($decoded) : 0;
            $out['html_b64'] = '[omitido en log: base64 de ' . $len . ' caracteres → HTML ~' . $htmlLen . ' bytes]';
        }
        if (isset($out['html']) && is_string($out['html']) && strlen($out['html']) > 4000) {
            $out['html'] = mb_substr($out['html'], 0, 4000) . "\n...[recortado, longitud_original=" . strlen($out['html']) . "]";
        }

        return $out;
    }

    /* =================== ALIEXPRESS OPEN PLATFORM =================== */

    /**
     * Llama a la **Affiliate API** (sin OAuth) y devuelve:
     *   { success, proveedor:'ALIEXPRESS_OPEN', raw, precio?, skus? }
     * Firmado con HMAC-SHA256 (app_key + app_secret).
     */
    private function callAliExpressOpen(string $url, ?string $apiTienda = null): array
    {
        if (!$this->aliAppKey || !$this->aliAppSecret) {
            return ['success' => false, 'error' => 'AliAffiliate: faltan app_key/app_secret', 'proveedor' => 'ALIEXPRESS_OPEN'];
        }

        $itemId = $this->aliExtractItemId($url);
        if (!$itemId) {
            return [
                'success'   => false,
                'error'     => 'AliAffiliate: no se pudo extraer product_id de la URL',
                'proveedor' => 'ALIEXPRESS_OPEN',
            ];
        }

        return $this->aliExpressAffiliateRequest('aliexpress.affiliate.productdetail.get', [
            'product_ids'     => (string) $itemId,
            'target_language' => 'es',
            'country'         => 'ES',
            'currency'        => 'EUR',
        ]);
    }

    /**
     * Búsqueda de productos AliExpress Affiliate API (product.query).
     *
     * @return array{success: bool, raw?: array, keywords?: string, page_no?: int, page_size?: int, proveedor?: string, error?: string}
     */
    public function buscarProductosAliexpress(string $keywords, int $pageNo = 1, int $pageSize = 20): array
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return ['success' => false, 'error' => 'Introduce un texto de búsqueda'];
        }

        if (!$this->aliAppKey || !$this->aliAppSecret) {
            return ['success' => false, 'error' => 'AliExpress: faltan app_key/app_secret', 'proveedor' => 'ALIEXPRESS_OPEN'];
        }

        $pageNo = max(1, $pageNo);
        $pageSize = max(1, min(50, $pageSize));

        $resultado = $this->aliExpressAffiliateRequest('aliexpress.affiliate.product.query', [
            'keywords'        => $keywords,
            'page_no'         => (string) $pageNo,
            'page_size'       => (string) $pageSize,
            'target_currency' => 'EUR',
            'target_language' => 'ES',
            'ship_to_country' => 'ES',
        ]);

        if ($resultado['success'] ?? false) {
            $resultado['keywords'] = $keywords;
            $resultado['page_no'] = $pageNo;
            $resultado['page_size'] = $pageSize;
        }

        return $resultado;
    }

    /**
     * @param array<string, string> $biz
     * @return array{success: bool, raw?: array|string, proveedor?: string, error?: string}
     */
    private function aliExpressAffiliateRequest(string $method, array $biz): array
    {
        $common = [
            'app_key'     => $this->aliAppKey,
            'timestamp'   => (string) round(microtime(true) * 1000),
            'sign_method' => 'sha256',
            'method'      => $method,
        ];

        $all = array_merge($common, $biz);
        $all['sign'] = $this->aliSignBusiness($all);

        try {
            $resp = Http::timeout(45)->acceptJson()->get($this->aliBaseSync, $all);
            $rawBody = (string) $resp->body();

            if (!$resp->successful()) {
                $parsed = json_decode($rawBody, true, 512, JSON_BIGINT_AS_STRING);

                return [
                    'success'   => false,
                    'error'     => 'AliAffiliate HTTP ' . $resp->status(),
                    'raw'       => is_array($parsed) ? $parsed : $rawBody,
                    'proveedor' => 'ALIEXPRESS_OPEN',
                ];
            }

            $data = json_decode($rawBody, true, 512, JSON_BIGINT_AS_STRING);
            if (!is_array($data)) {
                return [
                    'success'   => false,
                    'error'     => 'AliAffiliate: respuesta no JSON',
                    'raw'       => $rawBody,
                    'proveedor' => 'ALIEXPRESS_OPEN',
                ];
            }

            $errorMsg = $this->aliExpressAffiliateErrorFromRaw($data);
            if ($errorMsg !== null) {
                return [
                    'success'   => false,
                    'error'     => $errorMsg,
                    'raw'       => $data,
                    'proveedor' => 'ALIEXPRESS_OPEN',
                ];
            }

            return [
                'success'   => true,
                'proveedor' => 'ALIEXPRESS_OPEN',
                'raw'       => $data,
            ];
        } catch (\Throwable $e) {
            return [
                'success'   => false,
                'error'     => 'AliAffiliate Exception: ' . $e->getMessage(),
                'proveedor' => 'ALIEXPRESS_OPEN',
            ];
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function aliExpressAffiliateErrorFromRaw(array $data): ?string
    {
        $errorResponse = $data['error_response'] ?? null;
        if (is_array($errorResponse)) {
            $msg = trim((string) ($errorResponse['msg'] ?? $errorResponse['sub_msg'] ?? ''));

            return $msg !== '' ? $msg : 'AliExpress API error_response';
        }

        return null;
    }

    /* ===== Helpers AliOpen: firma, parseo, etc. ===== */

    private function aliSortedConcat(array $params): string
    {
        ksort($params, SORT_STRING);
        $s = '';
        foreach ($params as $k => $v) $s .= $k . (string)$v;
        return $s;
    }
    
    private function aliSignBusiness(array $allParams): string
    {
        $toSign = $this->aliSortedConcat($allParams);
        return strtoupper(hash_hmac('sha256', $toSign, $this->aliAppSecret));
    }
    
    private function aliExtractItemId(string $url): ?string
    {
        $url = trim($url);
        if (preg_match('/^\d{6,}$/', $url)) return $url;
        if (preg_match('~/(?:item/)?(\d{6,})\.html~i', $url, $m)) return $m[1];
        if (preg_match('/(\d{6,})/', $url, $m2)) return $m2[1];
        return null;
    }

    /* =================== AMAZON PRODUCT INFO API =================== */

    /**
     * Llama a la Amazon Product Info API (RapidAPI) y devuelve:
     *   { success, proveedor:'AMAZON_PRODUCT_INFO', raw, precio? }
     */
    private function callAmazonProductInfo(string $url, ?string $apiTienda = null): array
    {
        if (!$this->amazonProductInfoApiKey) {
            return ['success' => false, 'error' => 'Amazon Product Info API: falta API key', 'proveedor' => 'AMAZON_PRODUCT_INFO'];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-rapidapi-host' => 'amazon-product-info2.p.rapidapi.com',
                    'x-rapidapi-key'  => $this->amazonProductInfoApiKey,
                ])
                ->get($this->amazonProductInfoApiUrl, [
                    'url' => $url,
                ]);

            if (!$response->successful()) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon Product Info API HTTP ' . $response->status() . ': ' . $response->body(),
                    'raw'       => $response->body(),
                    'proveedor' => 'AMAZON_PRODUCT_INFO',
                ];
            }

            $json = $response->json();
            if (!is_array($json)) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon Product Info API: respuesta no JSON',
                    'raw'       => $response->body(),
                    'proveedor' => 'AMAZON_PRODUCT_INFO',
                ];
            }

            // Extraer precio del JSON
            $precio = null;
            if (isset($json['body']['rawPrice']) && $json['body']['rawPrice'] !== null) {
                $precio = $this->num($json['body']['rawPrice']);
            }

            return [
                'success'   => true,
                'proveedor' => 'AMAZON_PRODUCT_INFO',
                'raw'       => $json,
                'precio'    => $precio,
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Amazon Product Info API Exception: ' . $e->getMessage(), 'proveedor' => 'AMAZON_PRODUCT_INFO'];
        }
    }

    /* =================== AMAZON PRICING AND PRODUCT INFO API (RapidAPI) =================== */

    /**
     * Llama a la Amazon Pricing And Product Info API (RapidAPI) y devuelve:
     *   { success, proveedor:'AMAZON_PRICING', raw, precio? }
     * Extrae el ASIN de la URL y hace la petición GET con los parámetros asin y domain=es
     */
    private function callAmazonPricing(string $url, ?string $apiTienda = null): array
    {
        if (!$this->amazonPricingApiKey) {
            return ['success' => false, 'error' => 'Amazon Pricing API: falta API key', 'proveedor' => 'AMAZON_PRICING'];
        }

        // Extraer ASIN de la URL usando la función existente
        $asin = $this->amazonExtractASIN($url);
        if (!$asin) {
            return [
                'success'   => false,
                'error'     => 'Amazon Pricing API: no se pudo extraer ASIN de la URL: ' . $url,
                'proveedor' => 'AMAZON_PRICING',
            ];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'x-rapidapi-host' => 'amazon-pricing-and-product-info.p.rapidapi.com',
                    'x-rapidapi-key'  => $this->amazonPricingApiKey,
                ])
                ->get($this->amazonPricingApiUrl, [
                    'asin' => $asin,
                    'domain' => 'es',
                ]);

            if (!$response->successful()) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon Pricing API HTTP ' . $response->status() . ': ' . $response->body(),
                    'raw'       => $response->body(),
                    'proveedor' => 'AMAZON_PRICING',
                ];
            }

            $json = $response->json();
            if (!is_array($json)) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon Pricing API: respuesta no JSON',
                    'raw'       => $response->body(),
                    'proveedor' => 'AMAZON_PRICING',
                ];
            }

            // Extraer precio del JSON (buyBoxPrice)
            $precio = null;
            if (isset($json['responseData']['buyBoxPrice']) && $json['responseData']['buyBoxPrice'] !== null) {
                $precio = $this->num($json['responseData']['buyBoxPrice']);
            }

            return [
                'success'   => true,
                'proveedor' => 'AMAZON_PRICING',
                'raw'       => $json,
                'precio'    => $precio,
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Amazon Pricing API Exception: ' . $e->getMessage(), 'proveedor' => 'AMAZON_PRICING'];
        }
    }

    /* =================== AMAZON CREATORS API (OAuth 2.0, v3.2 EU) =================== */

    /**
     * Obtiene un access token OAuth 2.0 para Creators API (v3.x).
     * El token se cachea y se reutiliza hasta que expire (1h); se renueva antes.
     */
    private function getAmazonCreatorsAccessToken(): ?string
    {
        if (!$this->amazonCreatorsCredentialId || !$this->amazonCreatorsCredentialSecret) {
            return null;
        }

        $cached = Cache::get(self::AMAZON_CREATORS_CACHE_KEY);
        if ($cached) {
            return $cached;
        }

        if ($this->amazonCreatorsEsperaSegundos > 0) {
            sleep($this->amazonCreatorsEsperaSegundos);
        }
        $resp = Http::timeout(15)
            ->acceptJson()
            ->asJson()
            ->post($this->amazonCreatorsTokenUrl, [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->amazonCreatorsCredentialId,
                'client_secret' => $this->amazonCreatorsCredentialSecret,
                'scope'         => 'creatorsapi::default',
            ]);

        if (!$resp->successful()) {
            return null;
        }

        $data = $resp->json();
        $token = $data['access_token'] ?? null;
        if ($token) {
            Cache::put(self::AMAZON_CREATORS_CACHE_KEY, $token, self::AMAZON_CREATORS_TOKEN_TTL_SECONDS);
        }
        return $token;
    }

    /**
     * Llama a la Amazon Creators API (GetItems). Respuesta en camelCase; ofertas solo OffersV2.
     */
    private function callAmazonAPI(string $url, ?string $apiTienda = null): array
    {
        if (!$this->amazonCreatorsCredentialId || !$this->amazonCreatorsCredentialSecret) {
            return ['success' => false, 'error' => 'Amazon Creators API: faltan credential_id o credential_secret', 'proveedor' => 'AMAZON_API'];
        }

        $asin = $this->amazonExtractASIN($url);
        if (!$asin) {
            return [
                'success'   => false,
                'error'     => 'Amazon API: no se pudo extraer ASIN de la URL: ' . $url,
                'proveedor' => 'AMAZON_API',
            ];
        }

        $debug = ['url_original' => $url, 'asin_extraido' => $asin];

        try {
            $token = $this->getAmazonCreatorsAccessToken();
            if (!$token) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon Creators API: no se pudo obtener access token (revisa credential_id/secret y token endpoint)',
                    'proveedor' => 'AMAZON_API',
                ];
            }

            // Creators API: camelCase, solo recursos soportados (OffersV2, no Offers)
            $payload = [
                'itemIds'                 => [$asin],
                'itemIdType'              => 'ASIN',
                'marketplace'             => $this->amazonCreatorsMarketplace,
                'partnerTag'              => $this->amazonCreatorsPartnerTag,
                'languagesOfPreference'   => ['es_ES'],
                'currencyOfPreference'    => 'EUR',
                'resources'               => [
                    'itemInfo.title',
                    'itemInfo.features',
                    'images.primary.small',
                    'parentASIN',
                    'offersV2.listings.price',
                    'offersV2.listings.availability',
                    'offersV2.listings.isBuyBoxWinner',
                    'offersV2.listings.merchantInfo',
                    'offersV2.listings.condition',
                    'offersV2.listings.type',
                ],
            ];

            if ($this->amazonCreatorsEsperaSegundos > 0) {
                sleep($this->amazonCreatorsEsperaSegundos);
            }
            $resp = Http::timeout(30)
                ->withHeaders([
                    'Authorization'   => 'Bearer ' . $token,
                    'Content-Type'    => 'application/json',
                    'x-marketplace'  => $this->amazonCreatorsMarketplace,
                ])
                ->post($this->amazonCreatorsApiUrl, $payload);

            if (!$resp->successful()) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon Creators API HTTP ' . $resp->status() . ': ' . $resp->body(),
                    'raw'       => $resp->body(),
                    'proveedor' => 'AMAZON_API',
                    'debug'     => $debug,
                ];
            }

            $json = $resp->json();
            return [
                'success'   => true,
                'proveedor' => 'AMAZON_API',
                'raw'       => $json,
                'asin'      => $asin,
                'debug'     => $debug,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Amazon API Exception: ' . $e->getMessage(), 'proveedor' => 'AMAZON_API'];
        }
    }

    /**
     * Obtiene datos de un producto de Amazon (solo imágenes) vía Creators API GetItems.
     * Para uso en modal de imágenes (admin productos). Devuelve raw para procesar en el controlador.
     *
     * @return array{success: bool, raw?: array, asin?: string, error?: string}
     */
    public function obtenerItemAmazonParaImagenes(string $url): array
    {
        if (!$this->amazonCreatorsCredentialId || !$this->amazonCreatorsCredentialSecret) {
            return ['success' => false, 'error' => 'Amazon Creators API: faltan credenciales'];
        }

        $asin = $this->amazonExtractASIN($url);
        if (!$asin) {
            return ['success' => false, 'error' => 'No se pudo extraer el ASIN de la URL de Amazon'];
        }

        try {
            $token = $this->getAmazonCreatorsAccessToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Amazon Creators API: no se pudo obtener access token'];
            }

            $payload = [
                'itemIds'               => [$asin],
                'itemIdType'             => 'ASIN',
                'marketplace'            => $this->amazonCreatorsMarketplace,
                'partnerTag'             => $this->amazonCreatorsPartnerTag,
                'languagesOfPreference'  => ['es_ES'],
                'resources'              => [
                    'images.primary.small',
                    'images.primary.medium',
                    'images.primary.large',
                    'images.variants.small',
                    'images.variants.medium',
                    'images.variants.large',
                ],
            ];

            if ($this->amazonCreatorsEsperaSegundos > 0) {
                sleep($this->amazonCreatorsEsperaSegundos);
            }
            $resp = Http::timeout(30)
                ->withHeaders([
                    'Authorization'   => 'Bearer ' . $token,
                    'Content-Type'    => 'application/json',
                    'x-marketplace'   => $this->amazonCreatorsMarketplace,
                ])
                ->post($this->amazonCreatorsApiUrl, $payload);

            if (!$resp->successful()) {
                return [
                    'success' => false,
                    'error'   => 'Error al consultar la API de Amazon: ' . $resp->status(),
                ];
            }

            $json = $resp->json();

            if (isset($json['errors']) && !empty($json['errors'])) {
                $msg = $json['errors'][0]['message'] ?? 'Error desconocido';
                return ['success' => false, 'error' => $msg];
            }

            return [
                'success' => true,
                'raw'     => $json,
                'asin'    => $asin,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Amazon API: ' . $e->getMessage()];
        }
    }

    /**
     * Busca productos en Amazon por palabras clave (Creators API SearchItems).
     * Devuelve la respuesta cruda de la API para inspección o procesado posterior.
     *
     * @return array{success: bool, raw?: array, keywords?: string, item_page?: int, error?: string}
     */
    public function buscarProductosAmazon(string $keywords, int $itemCount = 10, int $itemPage = 1): array
    {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return ['success' => false, 'error' => 'Introduce un texto de búsqueda'];
        }

        if (!$this->amazonCreatorsCredentialId || !$this->amazonCreatorsCredentialSecret) {
            return ['success' => false, 'error' => 'Amazon Creators API: faltan credenciales'];
        }

        $itemCount = max(1, min(10, $itemCount));
        $itemPage = max(1, $itemPage);

        try {
            $token = $this->getAmazonCreatorsAccessToken();
            if (!$token) {
                return ['success' => false, 'error' => 'Amazon Creators API: no se pudo obtener access token'];
            }

            $payload = [
                'keywords'              => $keywords,
                'marketplace'           => $this->amazonCreatorsMarketplace,
                'partnerTag'            => $this->amazonCreatorsPartnerTag,
                'languagesOfPreference' => ['es_ES'],
                'currencyOfPreference'  => 'EUR',
                'itemCount'             => $itemCount,
                'itemPage'              => $itemPage,
                'resources'             => [
                    'itemInfo.title',
                    'itemInfo.features',
                    'images.primary.small',
                    'images.primary.medium',
                    'images.primary.large',
                    'offersV2.listings.price',
                    'offersV2.listings.availability',
                    'offersV2.listings.isBuyBoxWinner',
                ],
            ];

            if ($this->amazonCreatorsEsperaSegundos > 0) {
                sleep($this->amazonCreatorsEsperaSegundos);
            }

            $resp = Http::timeout(30)
                ->withHeaders([
                    'Authorization'  => 'Bearer ' . $token,
                    'Content-Type'   => 'application/json',
                    'x-marketplace'  => $this->amazonCreatorsMarketplace,
                ])
                ->post($this->amazonCreatorsSearchApiUrl, $payload);

            if (!$resp->successful()) {
                return [
                    'success' => false,
                    'error'   => 'Error al consultar la API de Amazon: ' . $resp->status() . ' — ' . $resp->body(),
                ];
            }

            $json = $resp->json();

            if (isset($json['errors']) && !empty($json['errors'])) {
                $msg = $json['errors'][0]['message'] ?? 'Error desconocido';
                return ['success' => false, 'error' => $msg, 'raw' => $json];
            }

            if (isset($json['Errors']) && !empty($json['Errors'])) {
                $msg = $json['Errors'][0]['Message'] ?? 'Error desconocido';
                return ['success' => false, 'error' => $msg, 'raw' => $json];
            }

            return [
                'success'   => true,
                'raw'       => $json,
                'keywords'  => $keywords,
                'item_page' => $itemPage,
            ];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Amazon API: ' . $e->getMessage()];
        }
    }

    /**
     * SearchItems paginado: página 1; si devuelve 10 items, página 2; etc. Máximo $maxPaginas (5).
     * Entre páginas adicionales espera al menos $esperaEntrePaginasSegundos segundos.
     *
     * @return array{success: bool, raw?: array, keywords?: string, paginas_consultadas?: int, total_items_amazon?: int, error?: string}
     */
    public function buscarProductosAmazonMultipagina(
        string $keywords,
        int $maxPaginas = 5,
        int $esperaEntrePaginasSegundos = 2
    ): array {
        $keywords = trim($keywords);
        if ($keywords === '') {
            return ['success' => false, 'error' => 'Introduce un texto de búsqueda'];
        }

        $maxPaginas = max(1, min(5, $maxPaginas));
        $esperaEntrePaginasSegundos = max(0, $esperaEntrePaginasSegundos);

        $itemsAcumulados = [];
        $paginasConsultadas = 0;
        $ultimoRaw = [];

        for ($pagina = 1; $pagina <= $maxPaginas; $pagina++) {
            if ($pagina > 1) {
                sleep($esperaEntrePaginasSegundos);
            }

            $resultado = $this->buscarProductosAmazon($keywords, 10, $pagina);
            if (!($resultado['success'] ?? false)) {
                if ($pagina === 1) {
                    return $resultado;
                }
                break;
            }

            $paginasConsultadas++;
            $raw = $resultado['raw'] ?? [];
            $ultimoRaw = is_array($raw) ? $raw : [];
            $itemsPagina = $this->extraerItemsBusquedaAmazonRaw($ultimoRaw);
            $itemsAcumulados = array_merge($itemsAcumulados, $itemsPagina);

            if (count($itemsPagina) < 10) {
                break;
            }
        }

        $itemsAcumulados = $this->deduplicarItemsAmazonPorAsin($itemsAcumulados);
        $rawMerged = $this->construirRawBusquedaAmazonMerged($ultimoRaw, $itemsAcumulados);

        return [
            'success'              => true,
            'raw'                  => $rawMerged,
            'keywords'             => $keywords,
            'paginas_consultadas'  => $paginasConsultadas,
            'total_items_amazon'   => count($itemsAcumulados),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function extraerItemsBusquedaAmazonRaw(array $raw): array
    {
        $items = $raw['searchResult']['items']
            ?? $raw['SearchResult']['Items']
            ?? [];

        return is_array($items) ? $items : [];
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function deduplicarItemsAmazonPorAsin(array $items): array
    {
        $vistos = [];
        $unicos = [];

        foreach ($items as $item) {
            $asin = (string) ($item['asin'] ?? $item['ASIN'] ?? '');
            if ($asin !== '' && isset($vistos[$asin])) {
                continue;
            }
            if ($asin !== '') {
                $vistos[$asin] = true;
            }
            $unicos[] = $item;
        }

        return $unicos;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function construirRawBusquedaAmazonMerged(array $ultimoRaw, array $items): array
    {
        if (isset($ultimoRaw['searchResult']) && is_array($ultimoRaw['searchResult'])) {
            $ultimoRaw['searchResult']['items'] = $items;

            return $ultimoRaw;
        }

        if (isset($ultimoRaw['SearchResult']) && is_array($ultimoRaw['SearchResult'])) {
            $ultimoRaw['SearchResult']['Items'] = $items;

            return $ultimoRaw;
        }

        return ['searchResult' => ['items' => $items]];
    }

    private function amazonExtractASIN(string $url): ?string
    {
        $url = trim($url);
        
        // Patrones comunes de URLs de Amazon (mejorados)
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',           // /dp/ASIN
            '/\/product\/([A-Z0-9]{10})/',      // /product/ASIN
            '/\/gp\/product\/([A-Z0-9]{10})/',  // /gp/product/ASIN
            '/\/dp\/([A-Z0-9]{10})\//',         // /dp/ASIN/ (con parámetros después)
            '/\/dp\/([A-Z0-9]{10})\?/',         // /dp/ASIN? (con query params)
            '/\/dp\/([A-Z0-9]{10})$/',          // /dp/ASIN (al final de URL)
            '/\/([A-Z0-9]{10})(?:\/|$|\?)/',    // ASIN seguido de /, fin de URL o ?
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Parsea la respuesta de GetItems (compatible con PA-API 5.0 y Creators API).
     * Creators API devuelve itemsResult.items con claves en camelCase.
     */
    private function amazonParseGetItemsResponse(array $json): array
    {
        try {
            $result = [];

            // Errores (PA-API: Errors; Creators: errors)
            if (isset($json['Errors'][0])) {
                $result['error'] = $json['Errors'][0]['Message'] ?? 'Error desconocido';
                return $result;
            }
            if (isset($json['errors'][0])) {
                $result['error'] = $json['errors'][0]['message'] ?? 'Error desconocido';
                return $result;
            }

            // Obtener lista de items: Creators API (itemsResult.items) o PA-API (ItemsResult.Items / ItemResults.Items)
            $items = $json['itemsResult']['items'] ?? $json['itemResults']['items'] ?? $json['ItemsResult']['Items'] ?? $json['ItemResults']['Items'] ?? null;

            if (!$items || !isset($items[0])) {
                return array_merge($result, ['error' => 'No se encontraron items en la respuesta']);
            }

            $item = $items[0];

            // Claves en camelCase (Creators) o PascalCase (PA-API)
            $result['asin'] = $item['asin'] ?? $item['ASIN'] ?? null;
            $result['title'] = $item['itemInfo']['title']['displayValue'] ?? $item['ItemInfo']['Title']['DisplayValue'] ?? null;
            $result['detail_page_url'] = $item['detailPageURL'] ?? $item['DetailPageURL'] ?? null;
            $result['parent_asin'] = $item['parentASIN'] ?? $item['ParentASIN'] ?? null;

            if (isset($item['images']['primary']['small']['url'])) {
                $result['imagen'] = $item['images']['primary']['small']['url'];
            } elseif (isset($item['Images']['Primary']['Small']['URL'])) {
                $result['imagen'] = $item['Images']['Primary']['Small']['URL'];
            }

            if (isset($item['itemInfo']['features']['displayValues'])) {
                $result['features'] = $item['itemInfo']['features']['displayValues'];
            } elseif (isset($item['ItemInfo']['Features']['DisplayValues'])) {
                $result['features'] = $item['ItemInfo']['Features']['DisplayValues'];
            }

            // Precio: Creators usa offersV2.listings[].price; PA-API Offers.Summaries o Listings
            if (!empty($item['offersV2']['listings'][0]['price']['amount'])) {
                $result['precio'] = $this->num($item['offersV2']['listings'][0]['price']['amount']);
            } elseif (!empty($item['OffersV2']['Listings'][0]['Price']['Amount'])) {
                $result['precio'] = $this->num($item['OffersV2']['Listings'][0]['Price']['Amount']);
            } elseif (!empty($item['Offers']['Listings'][0]['Price']['Amount'])) {
                $result['precio'] = $this->num($item['Offers']['Listings'][0]['Price']['Amount']);
            }
            if (isset($item['Offers']['Summaries'][0]['HighestPrice']['Amount'])) {
                $result['precio_maximo'] = $this->num($item['Offers']['Summaries'][0]['HighestPrice']['Amount']);
            }

            return $result;
        } catch (\Throwable $e) {
            return ['error' => 'Error parseando respuesta: ' . $e->getMessage()];
        }
    }

    private function amazonParseSearchResponse(array $json): array
    {
        try {
            $result = [];

            // Verificar errores
            if (isset($json['Errors'])) {
                $error = $json['Errors'][0]['Message'] ?? 'Error desconocido';
                return ['error' => $error];
            }

            // Extraer información del producto de SearchItems
            if (isset($json['SearchResult']['Items'][0])) {
                $item = $json['SearchResult']['Items'][0];
                
                $result['asin'] = $item['ASIN'] ?? null;
                $result['title'] = $item['ItemInfo']['Title']['DisplayValue'] ?? null;
                
                // Precio
                if (isset($item['Offers']['Listings'][0]['Price']['Amount'])) {
                    $precio = $item['Offers']['Listings'][0]['Price']['Amount'];
                    $result['precio'] = $this->num($precio);
                }

                // Disponibilidad
                if (isset($item['Offers']['Listings'][0]['Availability']['Message'])) {
                    $result['disponibilidad'] = $item['Offers']['Listings'][0]['Availability']['Message'];
                }

                // Prime
                if (isset($item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'])) {
                    $result['prime'] = $item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'];
                }

                // Imagen
                if (isset($item['Images']['Primary']['Large']['URL'])) {
                    $result['imagen'] = $item['Images']['Primary']['Large']['URL'];
                }
            }

            return $result;

        } catch (\Throwable $e) {
            return ['error' => 'Error parseando respuesta: ' . $e->getMessage()];
        }
    }

    private function amazonParsePA5Response(array $json): array
    {
        try {
            $result = [];

            // Verificar errores
            if (isset($json['Errors'])) {
                $error = $json['Errors'][0]['Message'] ?? 'Error desconocido';
                return ['error' => $error];
            }

            // Extraer información del producto
            if (isset($json['ItemsResult']['Items'][0])) {
                $item = $json['ItemsResult']['Items'][0];
                
                $result['asin'] = $item['ASIN'] ?? null;
                $result['title'] = $item['ItemInfo']['Title']['DisplayValue'] ?? null;
                
                // Precio
                if (isset($item['Offers']['Listings'][0]['Price']['Amount'])) {
                    $precio = $item['Offers']['Listings'][0]['Price']['Amount'];
                    $result['precio'] = $this->num($precio);
                }

                // Disponibilidad
                if (isset($item['Offers']['Listings'][0]['Availability']['Message'])) {
                    $result['disponibilidad'] = $item['Offers']['Listings'][0]['Availability']['Message'];
                }

                // Prime
                if (isset($item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'])) {
                    $result['prime'] = $item['Offers']['Listings'][0]['DeliveryInfo']['IsPrimeEligible'];
                }
            }

            return $result;

        } catch (\Throwable $e) {
            return ['error' => 'Error parseando respuesta: ' . $e->getMessage()];
        }
    }


    private function num($raw): ?float
    {
        if ($raw === null) return null;
        if (!is_string($raw) && !is_numeric($raw)) return null;
        $s = trim((string)$raw);
        if ($s === '') return null;
        if (strpos($s, ',') !== false && strpos($s, '.') !== false) $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) return null;
        return (float)$s;
    }

    /* =================== MAPEO Y DIAGNÓSTICO =================== */

    private function mapearApiTienda(string $apiTienda): string
    {
        $k = strtolower($apiTienda);

        if ($k === 'aliexpressopen') return 'aliexpress_open';
        if ($k === 'navegadorlocal') return 'navegador_local';
        if ($k === 'cloudflare') return 'cloudflare';
        if ($k === 'amazonapi') return 'amazon_api';
        if ($k === 'amazonproductinfo') return 'amazon_product_info';
        if ($k === 'amazonpricing') return 'amazon_pricing';

        if ($k === 'scrapingant') return 'scrapingant';
        if ($k === 'brightdata;false') return 'bright_unlocker';
        if ($k === 'brightdata;true')  return 'bright_unlocker';

        if (strpos($k, 'mivpshtml') === 0) return 'vps_html';

        return $this->apiProveedorPreferido;
    }

    private function obtenerConfiguracionBrightData(string $apiTienda): array
    {
        $config = [
            'render' => false,
            'headers' => [
                'Accept-Language' => 'es-ES,es;q=0.9',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ]
        ];

        if (strtolower($apiTienda) === 'brightdata;true') $config['render'] = true;

        return $config;
    }

    public function diagnosticarBrightData(): array
    {
        $resultado = [
            'api_key_configurada' => !empty($this->brightDataApiKey),
            'zone_configurada' => !empty($this->brightDataZone),
            'api_key_longitud' => strlen($this->brightDataApiKey),
            'zone_nombre' => $this->brightDataZone,
            'test_basico' => null
        ];

        try {
            $testUrl = 'https://httpbin.org/html';
            $payload = ['zone' => $this->brightDataZone, 'url' => $testUrl, 'format' => 'raw'];

            $resp = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->brightDataApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->brightDataApiUrl, $payload);

            $resultado['test_basico'] = [
                'status' => $resp->status(),
                'success' => $resp->successful(),
                'body_length' => strlen($resp->body()),
                'body_empty' => empty($resp->body()),
                'error' => $resp->successful() ? null : $resp->body()
            ];
        } catch (\Throwable $e) {
            $resultado['test_basico'] = ['error' => 'Exception: ' . $e->getMessage()];
        }

        return $resultado;
    }

    private function formatearErrores(array $errores): string
    {
        $partes = [];
        foreach ($errores as $prov => $msg) $partes[] = strtoupper($prov) . ': ' . $msg;
        return implode(' | ', $partes);
    }
}
