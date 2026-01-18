<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.ofertas.historico.ejecuciones') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Historial -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Ejecución en tiempo real
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
        <div class="flex justify-center items-center">
            <canvas id="grafico" width="200" height="200"></canvas>
        </div>

        <div class="space-y-4 text-gray-800 dark:text-white">
            <div><strong>Total de ofertas:</strong> <span id="total">0</span></div>
            <div><strong>Guardados correctamente:</strong> <span id="correctos">0</span></div>
            <div><strong>Actualizados:</strong> <span id="actualizados">0</span></div>
            <div><strong>Saltados:</strong> <span id="saltados">0</span></div>
            <div><strong>Errores:</strong> <span id="errores">0</span></div>
            <button onclick="detener()" class="mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                ⛔ Detener ejecución
            </button>
        </div>
    </div>

    <div class="max-w-7xl mx-auto mt-10 px-4">
        <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white">Errores:</h3>
        <pre id="log" class="bg-gray-100 text-sm p-4 rounded overflow-auto h-64 whitespace-pre-wrap text-red-700 dark:text-red-400"></pre>
    </div>

    <div id="modal-conflicto" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-xl space-y-4">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Ya existe precio para hoy</h2>
            <p class="text-gray-600 dark:text-gray-300">
                La oferta <strong id="conflicto-nombre"></strong> ya tiene un precio registrado hoy.
            </p>
            <div class="flex flex-col sm:flex-row gap-2 justify-end">
                <button onclick="resolverConflicto('solo_este')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">Actualizar</button>
                <button onclick="resolverConflicto('todos')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">Actualizar Todos</button>
                <button onclick="resolverConflicto('saltar')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">Saltar</button>
                <button onclick="resolverConflicto('saltar_todos')" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">Saltar Todos</button>
                <button onclick="resolverConflicto('cancelar')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Parar ejecución</button>
            </div>
        </div>
    </div>


<script>
    let ofertas = [];
    let actual = 0;
    let total = 0;
    let correctos = 0;
    let errores = 0;
    let actualizados = 0;
    let detenerEjecucion = false;
    let forzarTodos = false;
    let saltarTodos = false;
    let saltados = 0;

    const ctx = document.getElementById('grafico').getContext('2d');

    function drawCircle() {
        const totalAngulo = 2 * Math.PI;
        const pCorrecto = total ? correctos / total : 0;
        const pError = total ? errores / total : 0;
        const pActualizado = total ? actualizados / total : 0;
        const pSaltado = total ? saltados / total : 0;

        ctx.clearRect(0, 0, 200, 200);
        ctx.beginPath();
        ctx.arc(100, 100, 90, 0, totalAngulo);
        ctx.fillStyle = '#e5e7eb';
        ctx.fill();

        let startAngle = -Math.PI / 2;

        const sectores = [{
                proporción: pCorrecto,
                color: '#10b981'
            },
            {
                proporción: pActualizado,
                color: '#3b82f6'
            },
            {
                proporción: pSaltado,
                color: '#facc15'
            },
            {
                proporción: pError,
                color: '#ef4444'
            }
        ];

        sectores.forEach(({
            proporción,
            color
        }) => {
            if (proporción > 0) {
                ctx.beginPath();
                ctx.moveTo(100, 100);
                ctx.arc(100, 100, 90, startAngle, startAngle + totalAngulo * proporción);
                ctx.fillStyle = color;
                ctx.fill();
                startAngle += totalAngulo * proporción;
            }
        });
    }

    function actualizarUI() {
        document.getElementById('total').textContent = total;
        document.getElementById('correctos').textContent = correctos;
        document.getElementById('errores').textContent = errores;
        document.getElementById('actualizados').textContent = actualizados;
        document.getElementById('saltados').textContent = saltados;
        drawCircle();
    }

    function detener() {
        detenerEjecucion = true;
    }

    function resolverConflicto(opcion) {
        document.getElementById('modal-conflicto').classList.add('hidden');

        if (opcion === 'cancelar') {
            detener();
            finalizarEjecucion();
            document.getElementById('log').textContent += '[⛔] Ejecución detenida por el usuario.\n';
        } else if (opcion === 'solo_este') {
            procesar(ofertas[actual], true);
        } else if (opcion === 'todos') {
            forzarTodos = true;
            procesar(ofertas[actual], true);
        } else if (opcion === 'saltar') {
            document.getElementById('log').textContent += `[!] Oferta ${ofertas[actual].nombre} saltada.\n`;
            saltados++;
            actual++;
            actual >= total ? finalizarEjecucion() : procesarSiguiente();
            actualizarUI();
        } else if (opcion === 'saltar_todos') {
            document.getElementById('log').textContent += `[!] Oferta ${ofertas[actual].nombre} saltada.\n`;
            forzarTodos = false;
            saltarTodos = true;
            actual++;
            procesarSiguiente();
        }
    }

    function procesarSiguiente(forzar = false) {
        if (actual >= total) {
            finalizarEjecucion();
            document.getElementById('log').textContent += '[✔] Proceso completado.\n';
            return;
        }
        procesar(ofertas[actual], forzar);
    }

    async function procesar(oferta, forzar = false) {
        if (detenerEjecucion) return;

        try {
            const res = await fetch("{{ route('admin.ofertas.historico.procesar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: oferta.id,
                    forzar
                })
            });

            if (!res.ok) throw new Error(`HTTP ${res.status}: ${await res.text()}`);

            const data = await res.json();

            if (data.status === 'existe') {
                if (forzar || forzarTodos) {
                    actualizados++;
                } else if (saltarTodos) {
                    document.getElementById('log').textContent += `[!] Oferta ${oferta.nombre} saltada automáticamente.\n`;
                    actual++;
                    actualizarUI();
                    setTimeout(() => procesarSiguiente(), 0);
                    return;
                } else {
                    document.getElementById('conflicto-nombre').textContent = oferta.nombre;
                    document.getElementById('modal-conflicto').classList.remove('hidden');
                    return;
                }
            }

            if (data.status === 'guardado') correctos++;
            else if (data.status === 'actualizado') actualizados++;
            else if (data.status === 'error') {
                errores++;
                document.getElementById('log').textContent += `[${data.oferta_id}] ${data.nombre} => ${data.error}\n`;
            }

        } catch (err) {
            errores++;
            document.getElementById('log').textContent += `[${oferta.id}] ${oferta.nombre} => ${err.message}\n`;
        }

        actual++;
        if (actual >= total) {
            actualizarUI();
            finalizarEjecucion();
            document.getElementById('log').textContent += '[✔] Proceso completado.\n';
        } else {
            actualizarUI();
            setTimeout(() => procesarSiguiente(), 0);
        }
    }

    async function iniciar() {
        const res = await fetch("{{ route('admin.ofertas.historico.lista') }}");
        const data = await res.json();
        ofertas = data.productos;
        total = ofertas.length;
        actualizarUI();

        if (total > 0) procesarSiguiente();
    }

    iniciar();

    function finalizarEjecucion() {
        fetch("{{ route('admin.ofertas.historico.finalizar') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                total,
                correctos,
                errores,
                log: document.getElementById('log').textContent.split('\n').filter(Boolean)
            })
        });
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