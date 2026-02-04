<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Detalle Fingerprint - Panel Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('admin.anti-scraping.fingerprints') }}">
                            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Fingerprints -></h2>
                        </a>
                        <h1 class="text-2xl font-light text-gray-800 dark:text-gray-200">Detalle Fingerprint</h1>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Información del Fingerprint -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Fingerprint</h2>
                <code class="block p-3 bg-gray-100 dark:bg-gray-700 rounded text-sm text-gray-900 dark:text-gray-100 font-mono break-all">{{ $fingerprint }}</code>
            </div>

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total Actividades</div>
                    <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($estadisticas['total_actividades']) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Score Máximo</div>
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $estadisticas['score_maximo'] ?? 0 }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Score Promedio</div>
                    <div class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ number_format($estadisticas['score_promedio'] ?? 0, 1) }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                    <div class="text-sm text-gray-500 dark:text-gray-400">IPs Únicas</div>
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $estadisticas['ips_unicas'] }}</div>
                </div>
            </div>

            <!-- IPs Asociadas -->
            @if($ips->count() > 0)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">IPs Asociadas</h2>
                <div class="space-y-2">
                    @foreach($ips as $ip)
                        <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                            <code class="text-sm text-gray-900 dark:text-gray-100 font-mono">{{ $ip->ip }}</code>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $ip->total }} actividades</span>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Historial de Actividades -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Historial de Actividades</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Endpoint</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Score</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($actividades as $actividad)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $actividad->created_at->format('d/m/Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="text-xs text-gray-900 dark:text-gray-100 font-mono">{{ $actividad->ip }}</code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                        {{ $actividad->endpoint }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $actividad->score >= 100 ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                               ($actividad->score >= 80 ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                                               ($actividad->score >= 40 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200')) }}">
                                            {{ $actividad->score }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                            {{ $actividad->accion_tomada === 'prolonged_ban' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                               ($actividad->accion_tomada === 'temp_ban' ? 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200' : 
                                               ($actividad->accion_tomada === 'captcha' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' : 
                                               ($actividad->accion_tomada === 'slowdown' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                               'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200'))) }}">
                                            {{ ucfirst(str_replace('_', ' ', $actividad->accion_tomada ?? 'normal')) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                        No se encontraron actividades
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Paginación -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                    {{ $actividades->links() }}
                </div>
            </div>
        </div>
    </div>
</body>
</html>






