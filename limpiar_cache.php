<?php
/**
 * Script temporal para limpiar todos los caches de Laravel
 * Ejecutar este archivo una vez en el hosting y luego eliminarlo
 */

echo "🧹 Limpiando caches de Laravel...\n";

// Incluir el autoloader de Laravel
require_once __DIR__ . '/vendor/autoload.php';

// Cargar la aplicación Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Limpiar cache de rutas
    echo "📋 Limpiando cache de rutas...\n";
    \Artisan::call('route:clear');
    
    // Limpiar cache de configuración
    echo "⚙️ Limpiando cache de configuración...\n";
    \Artisan::call('config:clear');
    
    // Limpiar cache de vistas
    echo "👁️ Limpiando cache de vistas...\n";
    \Artisan::call('view:clear');
    
    // Limpiar cache de aplicación
    echo "📱 Limpiando cache de aplicación...\n";
    \Artisan::call('cache:clear');
    
    // Limpiar cache de compilación
    echo "🔨 Limpiando cache de compilación...\n";
    \Artisan::call('clear-compiled');
    
    // Optimizar autoloader
    echo "🚀 Optimizando autoloader...\n";
    \Artisan::call('optimize:clear');
    
    echo "✅ ¡Todos los caches han sido limpiados correctamente!\n";
    echo "🔄 Recarga la página del formulario para ver los cambios.\n";
    
} catch (Exception $e) {
    echo "❌ Error al limpiar caches: " . $e->getMessage() . "\n";
}

echo "\n⚠️ IMPORTANTE: Elimina este archivo después de ejecutarlo por seguridad.\n";
?>
