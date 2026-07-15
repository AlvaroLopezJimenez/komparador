<?php
// BORRAR ESTE ARCHIVO INMEDIATAMENTE DESPUÉS DE USARLO
$secret = 'pon-aqui-una-clave-larga-y-secreta';
if (($_GET['key'] ?? '') !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call('view:clear');
echo 'Vistas limpiadas. Borra este archivo YA.';