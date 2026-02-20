<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limits Configuration
    |--------------------------------------------------------------------------
    |
    | Define los límites de rate limiting para cada tipo de endpoint.
    | Los límites están diseñados para ser generosos con usuarios reales
    | pero restrictivos con scrapers.
    |
    */

    'limits' => [
        'token' => [
            'per_minute_ip' => env('ANTI_SCRAPING_TOKEN_MIN_IP', 15),
            'per_minute_fingerprint' => env('ANTI_SCRAPING_TOKEN_MIN_FP', 10),
            'per_hour_ip' => env('ANTI_SCRAPING_TOKEN_HOUR_IP', 50),
            'per_day_ip' => env('ANTI_SCRAPING_TOKEN_DAY_IP', 100),
        ],
        'ofertas' => [
            'per_minute_ip' => env('ANTI_SCRAPING_OFERTAS_MIN_IP', 15),
            'per_minute_token' => env('ANTI_SCRAPING_OFERTAS_MIN_TOKEN', 10),
            'per_hour_ip' => env('ANTI_SCRAPING_OFERTAS_HOUR_IP', 50),
            'per_day_ip' => env('ANTI_SCRAPING_OFERTAS_DAY_IP', 100),
        ],
        'especificaciones' => [
            'per_minute_ip' => env('ANTI_SCRAPING_ESPEC_MIN_IP', 15),
            'per_minute_token' => env('ANTI_SCRAPING_ESPEC_MIN_TOKEN', 10),
            'per_hour_ip' => env('ANTI_SCRAPING_ESPEC_HOUR_IP', 50),
            'per_day_ip' => env('ANTI_SCRAPING_ESPEC_DAY_IP', 100),
        ],
        'historicos' => [
            'per_minute_ip' => env('ANTI_SCRAPING_HISTORICOS_MIN_IP', 15),
            'per_hour_ip' => env('ANTI_SCRAPING_HISTORICOS_HOUR_IP', 50),
            'per_day_ip' => env('ANTI_SCRAPING_HISTORICOS_DAY_IP', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring System Configuration
    |--------------------------------------------------------------------------
    |
    | Sistema de puntuación basado en patrones repetidos, no eventos únicos.
    | Solo se penaliza cuando hay un patrón claro de comportamiento sospechoso.
    |
    */

    'scoring' => [
        'thresholds' => [
            'normal' => 20,
            'slowdown' => 40,
            'captcha' => 80,
            'temp_ban' => 100,
        ],
        'penalties' => [
            'productos_por_minuto' => [
                'threshold' => 10,
                'points_per_product' => 0.5,
                'max_points' => 10,
            ],
            'acceso_secuencial' => [
                'min_sequential' => 5,
                'points' => [
                    5 => 5,
                    10 => 10,
                    20 => 20,
                ],
            ],
            'sin_pausas_humanas' => [
                'min_requests' => 10,
                'threshold_seconds' => 3,
                'points' => 10,
            ],
            'no_recursos_estaticos' => [
                'min_requests' => 20,
                'points' => 15,
            ],
            'fingerprint_multiple_ips' => [
                'min_ips' => 3,
                'points' => 30,
            ],
            'acceso_directo_endpoint' => [
                'min_direct_access' => 5,
                'points' => [
                    5 => 10,
                    10 => 20,
                    20 => 35,
                ],
            ],
            'fingerprint_reutilizado' => [
                'min_reuses' => 5,
                'points' => 10,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions Configuration
    |--------------------------------------------------------------------------
    |
    | Define las acciones a tomar según el score acumulado.
    |
    */

    'actions' => [
        'slowdown' => [
            'min_delay' => 500,    // ms
            'max_delay' => 2000,   // ms
        ],
        'temp_ban' => [
            'duration' => 3600,    // 1 hora en segundos
        ],
        'prolonged_ban' => [
            'duration' => 604800,  // 7 días en segundos
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para tokens efímeros.
    |
    */

    'token' => [
        'expiration' => env('ANTI_SCRAPING_TOKEN_EXPIRATION', 60), // segundos
        'max_uses' => env('ANTI_SCRAPING_TOKEN_MAX_USES', 10),
        'secret' => env('ANTI_SCRAPING_TOKEN_SECRET', env('APP_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fingerprint Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el sistema de fingerprinting.
    |
    */

    'fingerprint' => [
        'salt' => env('FINGERPRINT_SALT', env('APP_KEY')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authenticated Users Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para usuarios autenticados.
    | Mantienen bypass pero con rate limits altos y logging para detectar abuso.
    |
    */

    'authenticated' => [
        'rate_limits' => [
            'per_minute_ip' => env('ANTI_SCRAPING_AUTH_MIN_IP', 100),
            'per_hour_ip' => env('ANTI_SCRAPING_AUTH_HOUR_IP', 1000),
            'per_day_ip' => env('ANTI_SCRAPING_AUTH_DAY_IP', 5000),
        ],
        'enable_logging' => true,
        'enable_heuristics' => true, // Heurísticas pasivas (sin bloqueos)
    ],

    /*
    |--------------------------------------------------------------------------
    | Signed URLs Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para URLs firmadas de ofertas.
    | Los tokens expiran después del tiempo especificado.
    |
    */

    'signed_urls' => [
        'expiration' => env('ANTI_SCRAPING_SIGNED_URL_EXPIRATION', 10800), // 3 horas en segundos
        'secret' => env('ANTI_SCRAPING_SIGNED_URL_SECRET', env('APP_KEY')),
    ],
];

