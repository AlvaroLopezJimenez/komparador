<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <a href="{{ route('admin.productos.historico.ejecuciones') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Historial -></h2>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Ejecución en tiempo real
            </h2>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Queso de progreso --}}
        <div class="flex justify-center items-center">
            <canvas id="grafico" width="200" height="200"></canvas>
        </div>

        {{-- Estadísticas --}}
        <div class="space-y-4 text-gray-800 dark:text-white">
            <div><strong>Total de productos:</strong> <span id="total">0</span></div>
            <div><strong>Guardados correctamente:</strong> <span id="correctos">0</span></div>
            <div><strong>Actualizados:</strong> <span id="actualizados">0</span></div>
            <div><strong>Saltados:</strong> <span id="saltados">0</span></div>
            <div><strong>Errores:</strong> <span id="errores">0</span></div>
            <button onclick="detener()" class="mt-4 px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                ⛔ Detener ejecución
            </button>
        </div>
    </div>

    {{-- Log de errores --}}
    <div class="max-w-7xl mx-auto mt-10 px-4">
        <h3 class="text-lg font-semibold mb-2 text-gray-800 dark:text-white">Errores:</h3>
        <pre id="log" class="bg-gray-100 text-sm p-4 rounded overflow-auto h-64 whitespace-pre-wrap text-red-700 dark:text-red-400"></pre>
    </div>

    {{-- Modal de conflicto al existir ya precio --}}
    <div id="modal-conflicto" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 w-full max-w-xl space-y-4">
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white">Ya existe precio para hoy</h2>
            <p class="text-gray-600 dark:text-gray-300">
                El producto <strong id="conflicto-nombre"></strong> ya tiene un precio registrado hoy.
            </p>
            <div class="flex flex-col sm:flex-row gap-2 justify-end">

                <button onclick="resolverConflicto('solo_este')" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded">
                    Actualizar
                </button>
                <button onclick="resolverConflicto('todos')" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded">
                    Actualizar Todos
                </button>
                <button onclick="resolverConflicto('saltar')" class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded">
                    Saltar
                </button>
                <button onclick="resolverConflicto('saltar_todos')" class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded">
                    Saltar Todos
                </button>
                <button onclick="resolverConflicto('cancelar')" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">
                    Parar ejecución
                </button>
            </div>
        </div>
    </div>

<script>
    let productos = [];
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

        // fondo gris
        ctx.beginPath();
        ctx.arc(100, 100, 90, 0, totalAngulo);
        ctx.fillStyle = '#e5e7eb';
        ctx.fill();

        let startAngle = -Math.PI / 2;

        // correcto (verde)
        if (pCorrecto > 0) {
            ctx.beginPath();
            ctx.moveTo(100, 100);
            ctx.arc(100, 100, 90, startAngle, startAngle + totalAngulo * pCorrecto);
            ctx.fillStyle = '#10b981';
            ctx.fill();
            startAngle += totalAngulo * pCorrecto;
        }

        // actualizado (azul)
        if (pActualizado > 0) {
            ctx.beginPath();
            ctx.moveTo(100, 100);
            ctx.arc(100, 100, 90, startAngle, startAngle + totalAngulo * pActualizado);
            ctx.fillStyle = '#3b82f6';
            ctx.fill();
            startAngle += totalAngulo * pActualizado;
        }

        // saltado (amarillo)
        if (pSaltado > 0) {
            ctx.beginPath();
            ctx.moveTo(100, 100);
            ctx.arc(100, 100, 90, startAngle, startAngle + totalAngulo * pSaltado);
            ctx.fillStyle = '#facc15';
            ctx.fill();
            startAngle += totalAngulo * pSaltado;
        }

        // error (rojo)
        if (pError > 0) {
            ctx.beginPath();
            ctx.moveTo(100, 100);
            ctx.arc(100, 100, 90, startAngle, startAngle + totalAngulo * pError);
            ctx.fillStyle = '#ef4444';
            ctx.fill();
        }
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

    // Resolver conflicto cuando ya existe
    let conflictoProducto = null;

    function resolverConflicto(opcion) {
        document.getElementById('modal-conflicto').classList.add('hidden');

        if (opcion === 'cancelar') {
            detener();
            finalizarEjecucion();
            document.getElementById('log').textContent += '[⛔] Ejecución detenida por el usuario.\n';
        } else if (opcion === 'solo_este') {
            procesar(productos[actual], true);
        } else if (opcion === 'todos') {
            forzarTodos = true;
            procesar(productos[actual], true);
        } else if (opcion === 'saltar') {
            document.getElementById('log').textContent += `[!] Producto ${productos[actual].nombre} saltado.\n`;
            saltados++;
            actual++;
            if (actual >= total) {
                actualizarUI();
                finalizarEjecucion();
                document.getElementById('log').textContent += '[✔] Proceso completado.\n';
            } else {
                actualizarUI();
                procesarSiguiente();
            }
        } else if (opcion === 'saltar_todos') {
            document.getElementById('log').textContent += `[!] Producto ${productos[actual].nombre} saltado.\n`;
            forzarTodos = false;
            conflictoProducto = null;
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

        procesar(productos[actual], forzar);
    }

    async function procesar(producto, forzar = false) {
        if (detenerEjecucion) return;

        try {
            const res = await fetch("{{ route('admin.productos.historico.procesar') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    id: producto.id,
                    forzar
                })
            });

            if (!res.ok) {
                const texto = await res.text();
                throw new Error(`HTTP ${res.status} ${res.statusText}: ${texto}`);
            }

            const data = await res.json();

            if (data.status === 'existe') {
                if (forzar || forzarTodos) {
                    actualizados++;
                } else if (saltarTodos) {
                    document.getElementById('log').textContent += `[!] Producto ${producto.nombre} saltado automáticamente.\n`;
                    actual++;
                    actualizarUI();
                    setTimeout(() => procesarSiguiente(), 0);
                    return;
                } else {
                    conflictoProducto = producto;
                    document.getElementById('conflicto-nombre').textContent = producto.nombre;
                    document.getElementById('modal-conflicto').classList.remove('hidden');
                    return;
                }
            }

            if (data.status === 'guardado') {
                correctos++;
            } else if (data.status === 'actualizado') {
                actualizados++;
            } else if (data.status === 'error') {
                errores++;
                const linea = `[${data.producto_id}] ${data.nombre} => ${data.error}\n`;
                document.getElementById('log').textContent += linea;
            }

        } catch (err) {
            errores++;
            const linea = `[${producto.id}] ${producto.nombre} => ${err.message || err.toString()}\n`;
            document.getElementById('log').textContent += linea;
        }


        actual++;
        if (actual >= total) {
            actualizarUI(); // <- fuerza dibujar el queso con 100%
            finalizarEjecucion();
            document.getElementById('log').textContent += '[✔] Proceso completado.\n';
        } else {
            actualizarUI();
            setTimeout(() => procesarSiguiente(), 0);
        }

    }


    async function iniciar() {
        const res = await fetch("{{ route('admin.productos.historico.lista') }}");
        const data = await res.json();
        productos = data.productos;
        total = productos.length;
        actualizarUI();

        if (total > 0) {
            procesarSiguiente();
        }
    }

    iniciar();

    function finalizarEjecucion() {
        fetch("{{ route('admin.productos.historico.finalizar') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                total,
                correctos,
                errores,
                log: document.getElementById('log').textContent
                    .split('\n')
                    .filter(x => x) // elimina líneas vacías
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