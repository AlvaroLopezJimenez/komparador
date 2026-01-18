<?php

namespace App\Http\Controllers\Scraping;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;

class PeticionApiHTMLController extends Controller
{
    /** PROVEEDOR PREFERIDO: 'bright_unlocker' | 'scrapingant' | 'scrapestack' */
    private $apiProveedorPreferido = 'bright_unlocker';

    /** FAILOVER: 'si' para intentar con otros proveedores si falla el primero; 'no' para no hacerlo */
    private $usarFailover = 'no';

    // ==== Mi VPS (HTML completo) ====
    private $miVpsApiUrl = 'http://51.38.184.245/obtener-html'; // POST {url, tienda?, variante?}

    // ==== ScrapingAnt (RapidAPI) ====
    private $apiKeyScrapingAnt = 'e7641b483cmshcac145d48569584p1de82ejsn452206de102d';
    private $apiUrlScrapingAnt = 'https://scrapingant.p.rapidapi.com/get';

    // ==== Scrapestack ====
    private $apiKeyScrapestack = '7994ef730e6b0ee3e95f976eec49dd72';
    private $apiUrlScrapestack = 'https://api.scrapestack.com/scrape';

    // ==== Bright Data Web Unlocker (REST /request) ====
    private $brightDataApiKey = 'e118ee69-5529-45d8-b643-982af6c8f205';
    private $brightDataZone   = 'chollopanales';
    private $brightDataApiUrl = 'https://api.brightdata.com/request';

    // ==== AliExpress Open Platform (Business/System/Affiliate) ====
    private $aliAppKey      = '519052';
    private $aliAppSecret   = 'D5BW9BTziNm5Ow8S8sXAc30PNHXU69yp';
    private $aliAccessToken = ''; // NO se usa en Affiliate
    private $aliBaseSync    = 'https://api-sg.aliexpress.com/sync';
    private $aliBaseRest    = 'https://api-sg.aliexpress.com/rest';

    // ==== Amazon Product Advertising API (PA-API 5.0) ====
    private $amazonAccessKey = 'AKPAT0P1HX1759785609';
    private $amazonSecretKey = 'VGfXEBBUH/FSsv2jENSilcOsRljI7aMgf7hb94gl';
    private $amazonAssociateTag = 'srto-21'; // Necesitarás configurar tu Associate Tag
    private $amazonEndpoint = 'webservices.amazon.es'; // Para España
    private $amazonRegion = 'eu-west-1'; // Región para España
    private $amazonService = 'ProductAdvertisingAPI';

    // ==== Amazon Product Info API (RapidAPI) ====
    private $amazonProductInfoApiKey = 'e7641b483cmshcac145d48569584p1de82ejsn452206de102d';
    private $amazonProductInfoApiUrl = 'https://amazon-product-info2.p.rapidapi.com/Amazon/details';

    // ==== Amazon Pricing And Product Info API (RapidAPI) ====
    private $amazonPricingApiKey = 'e7641b483cmshcac145d48569584p1de82ejsn452206de102d';
    private $amazonPricingApiUrl = 'https://amazon-pricing-and-product-info.p.rapidapi.com/';

    /**
     * Intenta con el proveedor preferido y, si $usarFailover === 'si' y falla, prueba con los otros.
     * Puedes forzar proveedor con $forzarProveedor ('bright_unlocker'|'scrapingant'|'scrapestack'|'aliexpress_open'|'vps_html').
     * Si se proporciona $apiTienda, se usará esa API específica.
     */
    public function obtenerHTML(string $url, ?string $forzarProveedor = null, ?string $apiTienda = null): array
    {
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');

        if ($apiTienda) {
            $forzarProveedor = $this->mapearApiTienda($apiTienda);
        }

        $prefer = $forzarProveedor ?: $this->apiProveedorPreferido;
        $todos = ['bright_unlocker', 'scrapingant', 'scrapestack', 'aliexpress_open', 'vps_html', 'amazon_api', 'amazon_product_info', 'amazon_pricing'];
        $proveedores = array_values(array_unique(array_merge([$prefer], $todos)));

        if ($this->usarFailover !== 'si') {
            $proveedores = [$prefer];
        }

        $errores = [];
        foreach ($proveedores as $prov) {
            $resultado = $this->llamarProveedor($prov, $url, $apiTienda);

            if (!empty($resultado['success'])) {
                // Proveedores HTML -> validamos 'html' y anotamos proveedor
                if (in_array($prov, ['bright_unlocker', 'scrapingant', 'scrapestack', 'vps_html'], true)) {
                    if (!isset($resultado['html']) || $resultado['html'] === '') {
                        return ['success' => false, 'error' => 'Respuesta vacia de ' . $prov];
                    }
                    if (stripos($resultado['html'], 'PROVEEDOR:') === false) {
                        $resultado['html'] = "<!-- PROVEEDOR: " . strtoupper($prov) . " -->\n" . $resultado['html'];
                    }
                    $resultado['proveedor'] = $prov;
                }
                return $resultado;
            }
            $errores[$prov] = $resultado['error'] ?? 'Error desconocido';
        }

        return ['success' => false, 'error' => $this->formatearErrores($errores)];
    }

    private function llamarProveedor(string $proveedor, string $url, ?string $apiTienda = null): array
    {
        try {
            if ($proveedor === 'bright_unlocker') return $this->callBrightDataUnlocker($url, $apiTienda);
            if ($proveedor === 'scrapingant')     return $this->callScrapingAnt($url);
            if ($proveedor === 'scrapestack')     return $this->callScrapestack($url);
            if ($proveedor === 'vps_html')        return $this->callMiVpsHtml($url, $apiTienda);
            if ($proveedor === 'aliexpress_open') return $this->callAliExpressOpen($url, $apiTienda);
            if ($proveedor === 'amazon_api')      return $this->callAmazonAPI($url, $apiTienda);
            if ($proveedor === 'amazon_product_info') return $this->callAmazonProductInfo($url, $apiTienda);
            if ($proveedor === 'amazon_pricing')  return $this->callAmazonPricing($url, $apiTienda);

            return ['success' => false, 'error' => 'Proveedor no valido. Usa "bright_unlocker", "scrapingant", "scrapestack", "aliexpress_open", "vps_html", "amazon_api", "amazon_product_info" o "amazon_pricing".'];
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

    /* =================== MI VPS =================== */

    private function callMiVpsHtml(string $url, ?string $apiTienda = null): array
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

            $resp = Http::timeout(90)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ])
                ->post($this->miVpsApiUrl, $payload);

            if (!$resp->successful()) {
                return ['success' => false, 'error' => 'VPS_HTML HTTP ' . $resp->status() . ' - ' . mb_substr($resp->body(), 0, 600)];
            }

            $json = $resp->json();
            if (!is_array($json))                 return ['success' => false, 'error' => 'VPS_HTML: respuesta no JSON'];
            if (!empty($json['error']))           return ['success' => false, 'error' => 'VPS_HTML: ' . (is_string($json['error']) ? $json['error'] : json_encode($json['error']))];
            if (!empty($json['html_b64'])) {
                $raw = base64_decode($json['html_b64'], true);
                if ($raw === false)               return ['success' => false, 'error' => 'VPS_HTML: html_b64 invalido'];
                $provider = $useProxy ? 'VPS_HTML_PROXY' : 'VPS_HTML';
                return ['success' => true, 'html' => "<!-- PROVEEDOR: {$provider} -->\n" . $raw];
            }
            if (!empty($json['html'])) {
                $provider = $useProxy ? 'VPS_HTML_PROXY' : 'VPS_HTML';
                return ['success' => true, 'html' => "<!-- PROVEEDOR: {$provider} -->\n" . (string)$json['html']];
            }

            return ['success' => false, 'error' => 'VPS_HTML: respuesta sin html_b64 ni html'];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'VPS_HTML Exception: ' . $e->getMessage()];
        }
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
    
        // Método de afiliados (no OAuth)
        $method = 'aliexpress.affiliate.productdetail.get';
    
        // Extraemos el productId de la URL (si no, devolvemos error)
        $itemId = $this->aliExtractItemId($url);
        if (!$itemId) {
            return [
                'success'   => false,
                'error'     => 'AliAffiliate: no se pudo extraer product_id de la URL',
                'proveedor' => 'ALIEXPRESS_OPEN',
            ];
        }
    
        $biz = [
            'product_ids'     => (string)$itemId,
            'target_language' => 'es',
            'country'         => 'ES',
            'currency'        => 'EUR',
            // 'tracking_id'   => 'TU_TRACKING_ID', // opcional si lo tienes
            // 'get_sku'       => 'true',           // si tu método lo soporta
        ];
    
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
    
            // ⚠️ Siempre leemos el cuerpo como texto y decodificamos con JSON_BIGINT_AS_STRING
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
    
            // Devuelve SOLO el JSON crudo (ahora con bigints como strings)
            return [
                'success'   => true,
                'proveedor' => 'ALIEXPRESS_OPEN',
                'raw'       => $data,
            ];
    
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'AliAffiliate Exception: ' . $e->getMessage(), 'proveedor' => 'ALIEXPRESS_OPEN'];
        }
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

    /* =================== AMAZON PRODUCT ADVERTISING API =================== */

    /**
     * Llama a la Amazon API, NO SE PUEDE SABER SI UN PRODUCTO NO TIENE OFERTAS DESTACADAS.
     */
    private function callAmazonAPI(string $url, ?string $apiTienda = null): array
    {
        if (!$this->amazonAccessKey || !$this->amazonSecretKey) {
            return ['success' => false, 'error' => 'Amazon API: faltan access_key/secret_key', 'proveedor' => 'AMAZON_API'];
        }

        // Extraer ASIN de la URL
        $asin = $this->amazonExtractASIN($url);
        if (!$asin) {
            return [
                'success'   => false,
                'error'     => 'Amazon API: no se pudo extraer ASIN de la URL: ' . $url,
                'proveedor' => 'AMAZON_API',
            ];
        }
        
        // Debug: mostrar ASIN extraído
        $debug = [
            'url_original' => $url,
            'asin_extraido' => $asin
        ];

        try {
            // PA-API 5.0 GetItems - estructura correcta para obtener información específica del producto
            $payload = [
                'ItemIds' => [$asin], // Array de ASINs
                'ItemIdType' => 'ASIN',
                'Marketplace' => 'www.amazon.es', // España
                'PartnerType' => 'Associates',
                'PartnerTag' => $this->amazonAssociateTag,
                'LanguagesOfPreference' => ['es_ES'], // Español de España
                'CurrencyOfPreference' => 'EUR', // Euros
                'Resources' => ["ItemInfo.Title",
                    "Offers.Summaries.OfferCount",
                    "Offers.Listings.Price",
                    "Offers.Listings.Availability.Type",
                    "Offers.Listings.Availability.Message",
                    "Offers.Listings.IsBuyBoxWinner",
                    "Offers.Listings.MerchantInfo",
                    "OffersV2.Listings.Price",
                    "OffersV2.Listings.Type",
                    "OffersV2.Listings.IsBuyBoxWinner",
                    "Offers.Listings.MerchantInfo"
                    ]
            ];

            $apiUrl = 'https://webservices.amazon.es/paapi5/getitems'; // Endpoint para España
            $payloadJson = json_encode($payload);
            $headers = $this->amazonCreateHeaders($apiUrl, $payloadJson);

            $resp = Http::timeout(30)
                ->withHeaders($headers)
                ->withBody($payloadJson, 'application/json')
                ->post($apiUrl);

            if (!$resp->successful()) {
                return [
                    'success'   => false,
                    'error'     => 'Amazon API HTTP ' . $resp->status() . ': ' . $resp->body(),
                    'raw'       => $resp->body(),
                    'proveedor' => 'AMAZON_API',
                    'debug'     => [
                        'url' => $apiUrl,
                        'payload' => $payload,
                        'headers' => $headers
                    ]
                ];
            }

            $json = $resp->json();
            
            // Devolver la respuesta cruda de Amazon sin procesar
            return [
                'success'   => true,
                'proveedor' => 'AMAZON_API',
                'raw'       => $json,  // Respuesta completa de Amazon
                'asin'      => $asin,
                'debug'     => $debug,
            ];

        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Amazon API Exception: ' . $e->getMessage(), 'proveedor' => 'AMAZON_API'];
        }
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

    private function amazonCreateHeaders(string $url, string $payload): array
    {
        $timestamp = gmdate('Ymd\THis\Z');
        $date = gmdate('Ymd');
        
        // Parsear la URL
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'];
        $path = $parsedUrl['path'];
        
        // Headers adicionales requeridos por la nueva API
        $xAmzTarget = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems';
        $contentEncoding = 'amz-1.0';
        
        // Crear el string para firmar (AWS Signature Version 4)
        $algorithm = 'AWS4-HMAC-SHA256';
        $credentialScope = $date . '/eu-west-1/ProductAdvertisingAPI/aws4_request';
        
        // Headers que se incluyen en la firma
        $canonicalHeaders = "content-encoding:" . $contentEncoding . "\n" .
                           "host:" . $host . "\n" .
                           "x-amz-date:" . $timestamp . "\n" .
                           "x-amz-target:" . $xAmzTarget . "\n";
        
        $signedHeaders = "content-encoding;host;x-amz-date;x-amz-target";
        
        $canonicalRequest = "POST\n" . 
                           $path . "\n" . 
                           "\n" . 
                           $canonicalHeaders . 
                           "\n" . 
                           $signedHeaders . "\n" . 
                           hash('sha256', $payload);
        
        $stringToSign = $algorithm . "\n" . 
                       $timestamp . "\n" . 
                       $credentialScope . "\n" . 
                       hash('sha256', $canonicalRequest);
        
        // Crear la firma
        $signingKey = $this->amazonGetSigningKey($date, 'eu-west-1');
        $signature = hash_hmac('sha256', $stringToSign, $signingKey);
        
        $authorization = $algorithm . ' ' . 
                        'Credential=' . $this->amazonAccessKey . '/' . $credentialScope . ', ' . 
                        'SignedHeaders=' . $signedHeaders . ', ' . 
                        'Signature=' . $signature;
        
        return [
            'Host' => $host,
            'Content-Type' => 'application/json; charset=UTF-8',
            'X-Amz-Date' => $timestamp,
            'X-Amz-Target' => $xAmzTarget,
            'Content-Encoding' => $contentEncoding,
            'User-Agent' => 'paapi-docs-curl/1.0.0',
            'Authorization' => $authorization
        ];
    }
    
    private function amazonGetSigningKey(string $date, string $region = null): string
    {
        $region = $region ?: $this->amazonRegion;
        $kDate = hash_hmac('sha256', $date, 'AWS4' . $this->amazonSecretKey, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', 'ProductAdvertisingAPI', $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        
        return $kSigning;
    }

    private function amazonParseGetItemsResponse(array $json): array
    {
        try {
            $result = [];

            // Verificar errores
            if (isset($json['Errors'])) {
                $error = $json['Errors'][0]['Message'] ?? 'Error desconocido';
                return ['error' => $error];
            }

            // Debug: verificar estructura de respuesta
            $result['debug'] = [
                'has_item_results' => isset($json['ItemResults']),
                'has_items_result' => isset($json['ItemsResult']),
                'has_items_in_result' => isset($json['ItemsResult']['Items']),
                'has_items_in_results' => isset($json['ItemResults']['Items']),
                'items_count_result' => isset($json['ItemsResult']['Items']) ? count($json['ItemsResult']['Items']) : 0,
                'items_count_results' => isset($json['ItemResults']['Items']) ? count($json['ItemResults']['Items']) : 0,
                'response_keys' => array_keys($json)
            ];

            // Extraer información del producto de GetItems
            // Intentar con ItemsResult primero, luego ItemResults
            $items = null;
            if (isset($json['ItemsResult']['Items'][0])) {
                $items = $json['ItemsResult']['Items'];
            } elseif (isset($json['ItemResults']['Items'][0])) {
                $items = $json['ItemResults']['Items'];
            }
            
            if ($items && isset($items[0])) {
                $item = $items[0];
                
                // Información básica
                $result['asin'] = $item['ASIN'] ?? null;
                $result['title'] = $item['ItemInfo']['Title']['DisplayValue'] ?? null;
                $result['detail_page_url'] = $item['DetailPageURL'] ?? null;
                
                // Imagen pequeña
                if (isset($item['Images']['Primary']['Small']['URL'])) {
                    $result['imagen'] = $item['Images']['Primary']['Small']['URL'];
                }
                
                // Características del producto
                if (isset($item['ItemInfo']['Features']['DisplayValues'])) {
                    $result['features'] = $item['ItemInfo']['Features']['DisplayValues'];
                }
                
                // Precio más alto de resúmenes
                if (isset($item['Offers']['Summaries'][0]['HighestPrice']['Amount'])) {
                    $result['precio_maximo'] = $this->num($item['Offers']['Summaries'][0]['HighestPrice']['Amount']);
                }
                
                // Parent ASIN
                if (isset($item['ParentASIN'])) {
                    $result['parent_asin'] = $item['ParentASIN'];
                }
            } else {
                $result['debug']['reason'] = 'No se encontraron items en la respuesta';
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
