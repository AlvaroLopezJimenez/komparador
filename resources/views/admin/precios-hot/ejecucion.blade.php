<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.categorias.index') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Categorías -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Ejecución Precios Hot</h2>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md">
        
        <!-- Panel de Control -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Panel de Control</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" id="totalCategorias">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Total Categorías</div>
                </div>
                <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="inserciones">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Inserciones</div>
                </div>
                <div class="bg-red-50 dark:bg-red-900/20 p-4 rounded-lg">
                    <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="errores">0</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Errores</div>
                </div>
            </div>

            <div class="flex gap-4">
                <button id="btnIniciar" onclick="iniciarEjecucion()" 
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                    Iniciar Ejecución
                </button>
                <button id="btnDetener" onclick="detenerEjecucion()" disabled
                    class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Detener
                </button>
                <button onclick="limpiarLog()" 
                    class="bg-gray-600 hover:bg-gray-700 text-white font-semibold px-6 py-3 rounded-md shadow-md transition">
                    Limpiar Log
                </button>
            </div>
        </div>

        <!-- Barra de Progreso -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Progreso</h3>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                <div id="barraProgreso" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
            <div class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                <span id="progresoTexto">0%</span> completado
            </div>
        </div>

        <!-- Log en Tiempo Real -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-4">Log de ejecución</h3>
            <div id="logContainer" class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 h-64 overflow-y-auto font-mono text-sm">
                <div class="text-gray-500 dark:text-gray-400">Esperando inicio de ejecución...</div>
            </div>
        </div>

        <!-- Producto focalizado (ej. ID 107) -->
        <div id="panelProductoFocalizado" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border-2 border-blue-500 dark:border-blue-400">
            <h3 class="text-lg font-semibold text-blue-700 dark:text-blue-300 mb-2">Análisis producto focalizado</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Detalle paso a paso para depurar un producto concreto (incluido en el JSON al copiar).</p>
            <pre id="productoFocalizadoJson" class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs overflow-x-auto max-h-[28rem] overflow-y-auto whitespace-pre-wrap font-mono text-gray-800 dark:text-gray-200"></pre>
        </div>

        <!-- Diagnóstico global -->
        <div id="panelDepuracion" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-amber-300 dark:border-amber-600">
            <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-2">Diagnóstico (paso global)</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Por qué entra o no cada producto/variante. Hasta 8 ejemplos por motivo.</p>
            <pre id="depuracionResumen" class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 text-xs overflow-x-auto mb-4 whitespace-pre-wrap font-mono text-gray-800 dark:text-gray-200"></pre>
            <div id="depuracionMuestras" class="space-y-4 max-h-96 overflow-y-auto text-sm"></div>
        </div>

        <!-- Resumen productos hot -->
        <div id="panelResumen" class="hidden bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 border border-gray-200 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">
                    Depuración: listado global guardado (top 60)
                    <span id="badgeTotalHot" class="ml-2 text-sm font-normal text-orange-600 dark:text-orange-400"></span>
                </h3>
                <button type="button" onclick="copiarJsonDepuracion()"
                    class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium px-4 py-2 rounded-md">
                    Copiar JSON para revisión
                </button>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Misma lista que se guarda en «Precios Hot». Columnas extra solo en pantalla (mín. 3 meses, precio actual, % rebaja).
            </p>
            <div class="overflow-x-auto max-h-[32rem] overflow-y-auto border border-gray-200 dark:border-gray-600 rounded-lg">
                <table class="min-w-full text-sm text-left">
                    <thead class="bg-gray-100 dark:bg-gray-700 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Producto</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Tipo</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200 text-right">Mín. histórico (3m)</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200 text-right">Precio actual</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200 text-right">Rebaja %</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">Tienda</th>
                            <th class="px-3 py-2 font-semibold text-gray-700 dark:text-gray-200">ID</th>
                        </tr>
                    </thead>
                    <tbody id="tablaProductosHot" class="divide-y divide-gray-200 dark:divide-gray-600">
                    </tbody>
                </table>
            </div>
            <p id="sinProductosHot" class="hidden mt-4 text-gray-500 dark:text-gray-400 text-sm">
                Ningún producto cumple el criterio en esta ejecución. Revisa el log o que exista histórico de precios en los últimos 3 meses.
            </p>
        </div>

    </div>

    <script>
        let ejecucionActiva = false;
        let totalCategorias = 0;
        let procesadas = 0;
        let inserciones = 0;
        let errores = 0;
        let pollingInterval = null;
        let ultimaRespuestaDepuracion = null;

        function iniciarEjecucion() {
            if (ejecucionActiva) return;

            ejecucionActiva = true;
            document.getElementById('btnIniciar').disabled = true;
            document.getElementById('btnDetener').disabled = false;
            
            // Resetear contadores
            totalCategorias = 0;
            procesadas = 0;
            inserciones = 0;
            errores = 0;
            actualizarContadores();
            
            agregarLog('🚀 Iniciando ejecución de precios hot...', 'info');
            
            // Iniciar ejecución real en el servidor
            iniciarEjecucionServidor();
        }

        function detenerEjecucion() {
            ejecucionActiva = false;
            document.getElementById('btnIniciar').disabled = false;
            document.getElementById('btnDetener').disabled = true;
            
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            
            agregarLog('⏹️ Ejecución detenida por el usuario', 'warning');
        }

        function iniciarEjecucionServidor() {
            const token = "{{ $tokenScraper }}";
            document.getElementById('panelResumen').classList.add('hidden');
            document.getElementById('panelDepuracion').classList.add('hidden');
            document.getElementById('panelProductoFocalizado').classList.add('hidden');
            document.getElementById('tablaProductosHot').innerHTML = '';
            document.getElementById('sinProductosHot').classList.add('hidden');
            agregarLog('⏳ Procesando en el servidor (puede tardar varios minutos)...', 'info');

            fetch('/admin/precios-hot/ejecutar-segundo-plano?token=' + encodeURIComponent(token), {
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                }
            })
            .then(async response => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(data.message || 'HTTP ' + response.status);
                }
                return data;
            })
            .then(data => {
                if (data.status === 'ok') {
                    totalCategorias = data.total_categorias ?? 0;
                    inserciones = data.total_inserciones ?? data.inserciones ?? 0;
                    errores = data.total_errores ?? data.errores ?? 0;
                    procesadas = totalCategorias;

                    actualizarContadores();
                    actualizarProgreso(100);

                    if (Array.isArray(data.log)) {
                        data.log.forEach(linea => agregarLog(linea, 'info'));
                    }

                    agregarLog('🎉 Ejecución completada', 'success');
                    agregarLog(`📈 Resumen: ${inserciones} listas guardadas, ${errores} errores, ${data.total_productos_hot ?? 0} productos hot detectados`, 'info');

                    ultimaRespuestaDepuracion = data;
                    mostrarProductoFocalizado(data.depuracion?.productos_focalizados || null);
                    mostrarDepuracion(data.depuracion || null);
                    mostrarTablaProductosHot(data.productos_hot || []);

                    ejecucionActiva = false;
                    document.getElementById('btnIniciar').disabled = false;
                    document.getElementById('btnDetener').disabled = true;
                } else {
                    agregarLog('❌ Error: ' + (data.message || 'Error desconocido'), 'error');
                    detenerEjecucion();
                }
            })
            .catch(error => {
                agregarLog('❌ Error de conexión: ' + error.message, 'error');
                detenerEjecucion();
            });
        }

        function mostrarProductoFocalizado(productosFocalizados) {
            const panel = document.getElementById('panelProductoFocalizado');
            const pre = document.getElementById('productoFocalizadoJson');

            if (!productosFocalizados || Object.keys(productosFocalizados).length === 0) {
                panel.classList.add('hidden');
                return;
            }

            panel.classList.remove('hidden');
            pre.textContent = JSON.stringify(productosFocalizados, null, 2);

            Object.keys(productosFocalizados).forEach(id => {
                const p = productosFocalizados[id];
                agregarLog('🔍 Producto ' + id + ': ' + (p.conclusion || p.error || '—'), p.entro_en_listado_hot_top60_esta_ejecucion ? 'success' : 'warning');
            });
        }

        function mostrarDepuracion(depuracion) {
            const panel = document.getElementById('panelDepuracion');
            const resumen = document.getElementById('depuracionResumen');
            const muestras = document.getElementById('depuracionMuestras');

            if (!depuracion) {
                panel.classList.add('hidden');
                return;
            }

            panel.classList.remove('hidden');
            const lineas = depuracion.resumen_texto || [];
            const criterios = depuracion.criterios || {};
            let texto = 'Criterios: ' + JSON.stringify(criterios, null, 2) + '\n\n';
            texto += lineas.join('\n');
            if (depuracion.contadores) {
                texto += '\n\nContadores:\n' + JSON.stringify(depuracion.contadores, null, 2);
            }
            resumen.textContent = texto;

            muestras.innerHTML = '';
            const porMotivo = depuracion.muestras || {};
            Object.keys(porMotivo).forEach(motivo => {
                const bloque = document.createElement('div');
                bloque.className = 'border border-gray-200 dark:border-gray-600 rounded-lg p-3';
                const titulo = document.createElement('h4');
                titulo.className = 'font-semibold text-gray-800 dark:text-gray-100 mb-2';
                titulo.textContent = motivo + ' (' + porMotivo[motivo].length + ' ejemplos)';
                bloque.appendChild(titulo);
                const pre = document.createElement('pre');
                pre.className = 'text-xs bg-gray-50 dark:bg-gray-900 p-2 rounded overflow-x-auto text-gray-700 dark:text-gray-300';
                pre.textContent = JSON.stringify(porMotivo[motivo], null, 2);
                bloque.appendChild(pre);
                muestras.appendChild(bloque);
            });
        }

        function mostrarTablaProductosHot(productos) {
            const panel = document.getElementById('panelResumen');
            const tbody = document.getElementById('tablaProductosHot');
            const sinProductos = document.getElementById('sinProductosHot');
            const badge = document.getElementById('badgeTotalHot');

            panel.classList.remove('hidden');
            tbody.innerHTML = '';
            badge.textContent = '(' + productos.length + ')';

            if (!productos.length) {
                sinProductos.classList.remove('hidden');
                return;
            }

            sinProductos.classList.add('hidden');

            productos.forEach(p => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/50';
                const minHist = p.precio_minimo_historico != null ? formatPrecio(p.precio_minimo_historico) : '—';
                const precioActual = p.precio_oferta != null ? formatPrecio(p.precio_oferta) : '—';
                const tipo = p.tipo === 'especificacion' ? 'Variante' : 'General';
                const nombre = escapeHtml(p.producto_nombre || '—');
                const tienda = escapeHtml(p.tienda_nombre || '—');
                const rebaja = p.porcentaje_diferencia != null ? p.porcentaje_diferencia + '%' : '—';
                const urlProducto = p.url_producto ? escapeHtml(p.url_producto) : '#';

                tr.innerHTML = `
                    <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                        <a href="${urlProducto}" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline">${nombre}</a>
                    </td>
                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">${tipo}</td>
                    <td class="px-3 py-2 text-right font-mono text-gray-700 dark:text-gray-200">${minHist} €</td>
                    <td class="px-3 py-2 text-right font-mono text-green-700 dark:text-green-400 font-semibold">${precioActual} €</td>
                    <td class="px-3 py-2 text-right font-mono text-orange-600 dark:text-orange-400">-${rebaja}</td>
                    <td class="px-3 py-2 text-gray-600 dark:text-gray-300">${tienda}</td>
                    <td class="px-3 py-2 text-gray-400 dark:text-gray-500 font-mono text-xs">P${p.producto_id} / O${p.oferta_id}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function formatPrecio(valor) {
            const n = Number(valor);
            if (Number.isNaN(n)) return '—';
            const dec = n < 1 ? 3 : 2;
            return n.toLocaleString('es-ES', { minimumFractionDigits: dec, maximumFractionDigits: dec });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function actualizarContadores() {
            document.getElementById('totalCategorias').textContent = totalCategorias;
            document.getElementById('inserciones').textContent = inserciones;
            document.getElementById('errores').textContent = errores;
        }

        function actualizarProgreso(forzarPorcentaje) {
            const porcentaje = forzarPorcentaje != null
                ? forzarPorcentaje
                : (totalCategorias > 0 ? (procesadas / totalCategorias) * 100 : 0);
            document.getElementById('barraProgreso').style.width = porcentaje + '%';
            document.getElementById('progresoTexto').textContent = Math.round(porcentaje) + '%';
        }

        function agregarLog(mensaje, tipo = 'info') {
            const container = document.getElementById('logContainer');
            const timestamp = new Date().toLocaleTimeString();
            
            let icono = 'ℹ️';
            let clase = 'text-gray-700 dark:text-gray-300';
            
            switch(tipo) {
                case 'success':
                    icono = '✅';
                    clase = 'text-green-600 dark:text-green-400';
                    break;
                case 'error':
                    icono = '❌';
                    clase = 'text-red-600 dark:text-red-400';
                    break;
                case 'warning':
                    icono = '⚠️';
                    clase = 'text-yellow-600 dark:text-yellow-400';
                    break;
            }
            
            const logEntry = document.createElement('div');
            logEntry.className = `mb-1 ${clase}`;
            logEntry.innerHTML = `<span class="text-gray-500 dark:text-gray-400">[${timestamp}]</span> ${icono} ${mensaje}`;
            
            container.appendChild(logEntry);
            container.scrollTop = container.scrollHeight;
        }

        function copiarJsonDepuracion() {
            if (!ultimaRespuestaDepuracion) {
                agregarLog('No hay datos: ejecuta el cron primero.', 'warning');
                return;
            }
            const payload = {
                ejecucion_id: ultimaRespuestaDepuracion.ejecucion_id,
                total_productos_hot: ultimaRespuestaDepuracion.total_productos_hot,
                log: ultimaRespuestaDepuracion.log,
                productos_focalizados: ultimaRespuestaDepuracion.depuracion?.productos_focalizados ?? null,
                depuracion: ultimaRespuestaDepuracion.depuracion,
                productos_hot: ultimaRespuestaDepuracion.productos_hot,
            };
            navigator.clipboard.writeText(JSON.stringify(payload, null, 2))
                .then(() => agregarLog('JSON copiado al portapapeles.', 'success'))
                .catch(() => agregarLog('No se pudo copiar. Usa la tabla o exporta manualmente.', 'error'));
        }

        function limpiarLog() {
            document.getElementById('logContainer').innerHTML = '<div class="text-gray-500 dark:text-gray-400">Log limpiado...</div>';
            document.getElementById('panelResumen').classList.add('hidden');
            document.getElementById('panelDepuracion').classList.add('hidden');
            document.getElementById('panelProductoFocalizado').classList.add('hidden');
            document.getElementById('tablaProductosHot').innerHTML = '';
            ultimaRespuestaDepuracion = null;
        }
    </script>
    {{-- EVITAR TENER QUE PINCHAR DOS VECES EN LOS ENLACES PARA QUE FUNCIONEN--}}
    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Prevenir doble clic en enlaces
    const links = document.querySelectorAll('a[href]');
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            // Si el enlace ya está siendo procesado, prevenir el clic
            if (this.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            
            // Marcar como en procesamiento
            this.dataset.processing = 'true';
            
            // Remover la marca después de un tiempo
            setTimeout(() => {
                this.dataset.processing = 'false';
            }, 2000);
        });
    });
    
    // Prevenir doble clic en botones
    const buttons = document.querySelectorAll('button');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (this.dataset.processing === 'true') {
                e.preventDefault();
                return false;
            }
            
            this.dataset.processing = 'true';
            
            setTimeout(() => {
                this.dataset.processing = 'false';
            }, 2000);
        });
    });
});
</script>
</x-app-layout> 