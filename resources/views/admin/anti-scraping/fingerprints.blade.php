<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Fingerprints Problemáticos - Panel Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.dashboard') }}">
                            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Panel -></h2>
                        </a>
                        <h1 class="text-2xl font-light text-gray-800 dark:text-gray-200">Fingerprints Problemáticos</h1>
                    </div>
                </div>
            </div>
        </header>

        <!-- Estadísticas -->
        <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 mb-6">
            <div class="max-w-7xl mx-auto px-4 py-3">
                <div class="flex items-center gap-3">
                        <!-- Total Fingerprints -->
                        <div class="flex items-center space-x-2 p-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg flex-1">
                            <div class="p-1.5 bg-blue-100 dark:bg-blue-800 rounded-md">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white">Fingerprints</span>
                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ number_format($estadisticas['total_fingerprints']) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Total Actividades -->
                        <div class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-900/20 rounded-lg flex-1">
                            <div class="p-1.5 bg-gray-100 dark:bg-gray-800 rounded-md">
                                <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white">Actividades</span>
                                    <span class="text-lg font-bold text-gray-600 dark:text-gray-400">{{ number_format($estadisticas['total_actividades']) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bloqueos Prolongados -->
                        <div class="flex items-center space-x-2 p-2 bg-red-50 dark:bg-red-900/20 rounded-lg flex-1">
                            <div class="p-1.5 bg-red-100 dark:bg-red-800 rounded-md">
                                <svg class="w-4 h-4 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white">Bloq. Prolong.</span>
                                    <span class="text-lg font-bold text-red-600 dark:text-red-400">{{ number_format($estadisticas['bloqueos_prolongados']) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bloqueos Temporales -->
                        <div class="flex items-center space-x-2 p-2 bg-orange-50 dark:bg-orange-900/20 rounded-lg flex-1">
                            <div class="p-1.5 bg-orange-100 dark:bg-orange-800 rounded-md">
                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white">Bloq. Temp.</span>
                                    <span class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ number_format($estadisticas['bloqueos_temporales']) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- CAPTCHAs Requeridos -->
                        <div class="flex items-center space-x-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg flex-1">
                            <div class="p-1.5 bg-yellow-100 dark:bg-yellow-800 rounded-md">
                                <svg class="w-4 h-4 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium text-white">CAPTCHAs</span>
                                    <span class="text-lg font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($estadisticas['captchas_requeridos']) }}</span>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

            <!-- Filtros -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 mb-6">
                <div class="flex items-center space-x-4">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Ordenar por:</label>
                    <a href="{{ route('admin.anti-scraping.fingerprints', ['filtro' => 'score', 'per_page' => $perPage]) }}" 
                       class="px-3 py-1 rounded {{ $filtro === 'score' ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                        Score Máximo
                    </a>
                    <a href="{{ route('admin.anti-scraping.fingerprints', ['filtro' => 'requests', 'per_page' => $perPage]) }}" 
                       class="px-3 py-1 rounded {{ $filtro === 'requests' ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                        Total Requests
                    </a>
                    <a href="{{ route('admin.anti-scraping.fingerprints', ['filtro' => 'ips', 'per_page' => $perPage]) }}" 
                       class="px-3 py-1 rounded {{ $filtro === 'ips' ? 'bg-blue-500 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300' }}">
                        IPs Únicas
                    </a>
                </div>
            </div>

            <!-- Tabla -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fingerprint</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actividades</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Score Máx</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Score Prom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IPs Únicas</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Endpoints</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Última Actividad</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($fingerprints as $fp)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="text-xs text-gray-900 dark:text-gray-100 font-mono">{{ substr($fp->fingerprint, 0, 20) }}...</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ number_format($fp->total_actividades) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $fp->score_maximo >= 100 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                               ($fp->score_maximo >= 80 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                                               ($fp->score_maximo >= 40 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200')) }}">
                                            {{ $fp->score_maximo }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ number_format($fp->score_promedio, 1) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $fp->ips_unicas }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $fp->endpoints_unicos }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $fp->ultima_actividad ? $fp->ultima_actividad->format('d/m/Y H:i') : 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="{{ route('admin.anti-scraping.fingerprints.detalle', ['fingerprint' => $fp->fingerprint]) }}" 
                                           class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                            Ver Detalles
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No se encontraron fingerprints problemáticos
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $fingerprints->links() }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>

