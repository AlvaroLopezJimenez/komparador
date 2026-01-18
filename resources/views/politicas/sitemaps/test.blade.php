<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sitemaps - Komparador.com</title>
    <meta name="description" content="Sitemaps de Komparador.com - Encuentra todas las categorías y productos">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Header -->
    @include('components.header')
    
    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-6 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-6">Sitemaps de Komparador.com</h1>
            
            <div class="grid md:grid-cols-2 gap-8">
                <!-- Sitemap de Categorías -->
                <div class="bg-blue-50 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-blue-900 mb-4">Sitemap de Categorías</h2>
                    <p class="text-blue-700 mb-4">
                        Contiene todas las categorías organizadas jerárquicamente.
                    </p>
                    <div class="space-y-2">
                        <a href="/sitemap-categorias.xml" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Ver Sitemap de Categorías
                        </a>
                        <p class="text-sm text-blue-600">
                            Formato: /categoria/{slug}
                        </p>
                    </div>
                </div>
                
                <!-- Sitemap de Productos -->
                <div class="bg-green-50 rounded-lg p-6">
                    <h2 class="text-xl font-semibold text-green-900 mb-4">Sitemap de Productos</h2>
                    <p class="text-green-700 mb-4">
                        Contiene todos los productos con rutas completas de categorías.
                    </p>
                    <div class="space-y-2">
                        <a href="/sitemap-productos.xml" 
                           class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            Ver Sitemap de Productos
                        </a>
                        <p class="text-sm text-green-600">
                            Formato: /{categoria-abuelo}/{categoria-padre}/{categoria-hijo}/{slug}
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Información adicional -->
            <div class="mt-8 p-6 bg-gray-50 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Información Técnica</h3>
                <div class="grid md:grid-cols-2 gap-6 text-sm text-gray-700">
                    <div>
                        <h4 class="font-medium mb-2">Características:</h4>
                        <ul class="space-y-1">
                            <li>• Paginación automática (1000 elementos por página)</li>
                            <li>• Protección contra uso malicioso</li>
                            <li>• Cache para mejorar rendimiento</li>
                            <li>• Validación de User-Agent para bots legítimos</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-medium mb-2">URLs disponibles:</h4>
                        <ul class="space-y-1">
                            <li>• <code>/sitemap.xml</code> - Sitemap principal</li>
                            <li>• <code>/sitemap-categorias.xml</code> - Categorías</li>
                            <li>• <code>/sitemap-productos.xml</code> - Productos</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    @include('components.footer')
</body>
</html>
