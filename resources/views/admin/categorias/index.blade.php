<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.dashboard') }}">
                <h2 class="font-semibold text-xl text-white leading-tight">Inicio -></h2>
            </a>
            <h2 class="font-semibold text-xl text-white leading-tight">Categorías</h2>
            <style>[x-cloak]{ display:none !important; }</style>
        </div>
    </x-slot>

    <div class="py-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="mb-4 text-right">
            <a href="{{ route('admin.categorias.create') }}"
                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                + Añadir categoría
            </a>
        </div>

    <div class="max-w-5xl mx-auto py-10 px-4 space-y-8 bg-gray-50 dark:bg-gray-900 rounded-lg shadow-md"
        x-data="{
            openCategorias: [],
            toggle(id) {
                if (this.openCategorias.includes(id)) {
                    this.openCategorias = this.openCategorias.filter(i => i !== id);
                } else {
                    this.openCategorias.push(id);
                }
            },
            isOpen(id) {
                return this.openCategorias.includes(id);
            }
        }">
        
        

        {{-- MENSAJES FLASH --}}
        @if (session('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded shadow-sm border border-green-300">
            {{ session('success') }}
        </div>
        @endif
        @if (session('error'))
        <div class="p-3 bg-red-100 text-red-800 rounded shadow-sm border border-red-300">
            {{ session('error') }}
        </div>
        @endif

        {{-- CATEGORÍAS EXISTENTES --}}
        <fieldset class="bg-white dark:bg-gray-800 shadow-sm rounded-xl p-6 space-y-4 border border-gray-200 dark:border-gray-700">
            <legend class="text-lg font-semibold text-gray-700 dark:text-gray-200">Categorías existentes</legend>

            @foreach ($categoriasRaiz as $categoria)
                @include('admin.categorias.partial-categoria', ['categoria' => $categoria, 'nivel' => 0])
            @endforeach
        </fieldset>

    </div>

    {{-- SCRIPT PARA PREVENIR DOBLE CLIC --}}
    <script>
        // Prevenir doble clic en enlaces y botones
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href]');

            // Función para manejar la subida de imágenes
            function configurarUpload() {
                const carpetaSelect = document.getElementById('carpeta-imagen-categoria');
                const fileInput = document.getElementById('file-imagen-categoria');
                const btnSeleccionar = document.getElementById('btn-seleccionar-categoria');
                const dropZone = document.getElementById('drop-zone-categoria');
                const nombreArchivo = document.getElementById('nombre-archivo-categoria');
                const preview = document.getElementById('preview-upload-categoria');
                const rutaImagen = document.getElementById('ruta-imagen-categoria');

                // Verificar que todos los elementos existen
                if (!carpetaSelect || !fileInput || !btnSeleccionar || !dropZone || !nombreArchivo || !preview || !rutaImagen) {
                    console.error('No se encontraron todos los elementos necesarios para la subida de imágenes');
                    return;
                }

                // Botón de selección de archivo
                btnSeleccionar.addEventListener('click', () => {
                    fileInput.click();
                });

                // Cambio de archivo seleccionado
                fileInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        procesarArchivo(file);
                    }
                });

                // Drag and drop
                dropZone.addEventListener('click', () => {
                    fileInput.click();
                });
                
                dropZone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    dropZone.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                });

                dropZone.addEventListener('dragleave', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                });

                dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        procesarArchivo(files[0]);
                    }
                });

                // Función para procesar el archivo
                function procesarArchivo(file) {
                    // Validar que sea una imagen
                    if (!file.type.startsWith('image/')) {
                        alert('Por favor selecciona un archivo de imagen válido.');
                        return;
                    }

                    // Validar tamaño (máximo 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('La imagen es demasiado grande. Máximo 5MB.');
                        return;
                    }

                    // Validar que se haya seleccionado una carpeta
                    const carpeta = carpetaSelect.value;
                    if (!carpeta) {
                        alert('Por favor selecciona una carpeta primero.');
                        return;
                    }

                    // Mostrar nombre del archivo
                    nombreArchivo.textContent = file.name;

                    // Crear vista previa
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        preview.src = e.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);

                    // Subir archivo
                    const formData = new FormData();
                    formData.append('imagen', file);
                    formData.append('carpeta', carpeta);

                    fetch('{{ route("admin.imagenes.subir") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Actualizar campo oculto con la ruta
                            const rutaCompleta = `${carpeta}/${data.data.nombre}`;
                            rutaImagen.value = rutaCompleta;
                            alert('Imagen subida correctamente');
                        } else {
                            alert('Error al subir la imagen: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error al subir imagen:', error);
                        alert('Error al subir la imagen');
                    });
                }
            }

            // Configurar upload
            configurarUpload();

            // Configurar botón para ver imágenes existentes
            configurarBotonVerImagenes();

            // Cargar carpetas disponibles al inicio
            // cargarCarpetasDisponibles(); // Comentado temporalmente para pruebas
            
            console.log('Todas las funciones básicas configuradas correctamente');

            // Función para cargar carpetas disponibles dinámicamente
            function cargarCarpetasDisponibles() {
                fetch('{{ route("admin.imagenes.carpetas") }}', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            // Actualizar select con las carpetas encontradas
                            actualizarSelectCarpetas('carpeta-imagen-categoria', data.data);
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar carpetas:', error);
                        // Si hay error, mantener las carpetas por defecto
                    });
            }

            // Función para actualizar un select con las carpetas disponibles
            function actualizarSelectCarpetas(selectId, carpetas) {
                const select = document.getElementById(selectId);
                if (!select) return;

                // Mantener la primera opción (Selecciona una carpeta)
                const primeraOpcion = select.querySelector('option[value=""]');
                select.innerHTML = '';
                
                if (primeraOpcion) {
                    select.appendChild(primeraOpcion);
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Selecciona una carpeta';
                    select.appendChild(option);
                }

                // Agregar las carpetas encontradas
                carpetas.forEach(carpeta => {
                    const option = document.createElement('option');
                    option.value = carpeta;
                    option.textContent = carpeta.charAt(0).toUpperCase() + carpeta.slice(1);
                    select.appendChild(option);
                });
            }

            // Función para configurar el botón de ver imágenes
            function configurarBotonVerImagenes() {
                const btnVer = document.getElementById('btn-ver-imagenes-categoria');
                const carpetaSelect = document.getElementById('carpeta-imagen-categoria');

                if (!btnVer || !carpetaSelect) {
                    console.error('No se encontraron los elementos para el botón de ver imágenes');
                    return;
                }

                btnVer.addEventListener('click', () => {
                    const carpeta = carpetaSelect.value;
                    
                    if (!carpeta) {
                        alert('Por favor selecciona una carpeta primero.');
                        return;
                    }
                    
                    // Abrir modal directamente sin Alpine.js
                    abrirModalImagenesCategoria(carpeta);
                });
            }

            // Función para abrir el modal de imágenes de categoría
            function abrirModalImagenesCategoria(carpeta) {
                // Crear modal dinámicamente si no existe
                let modal = document.getElementById('modalImagenesCategoria');
                if (!modal) {
                    modal = document.createElement('div');
                    modal.id = 'modalImagenesCategoria';
                    modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center';
                    modal.innerHTML = `
                        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 max-w-6xl w-full relative shadow-xl overflow-y-auto max-h-[90vh]">
                            <button onclick="cerrarModalImagenesCategoria()" class="absolute top-3 right-4 text-xl text-gray-800 dark:text-gray-100 hover:text-gray-600 dark:hover:text-gray-300">×</button>
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200">Imágenes en la carpeta: <span class="text-blue-600 dark:text-blue-400">${carpeta}</span></h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Haz clic en una imagen para seleccionarla</p>
                            </div>
                            <div id="contenido-modal-imagenes-categoria" class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                                <div class="text-center text-gray-500 dark:text-gray-400">Cargando imágenes...</div>
                            </div>
                        </div>
                    `;
                    document.body.appendChild(modal);
                } else {
                    modal.style.display = 'flex';
                }
                
                // Cargar imágenes
                cargarImagenesEnModal(carpeta);
            }

            // Función para cerrar el modal de imágenes de categoría
            window.cerrarModalImagenesCategoria = function() {
                const modal = document.getElementById('modalImagenesCategoria');
                if (modal) {
                    modal.style.display = 'none';
                }
            };

            // Función para cargar imágenes en el modal de edición
            function cargarImagenesEnModalEditar(carpeta) {
                const contenedor = document.getElementById('contenido-modal-imagenes-editar');
                if (!contenedor) {
                    console.error('No se encontró el contenedor del modal de imágenes de edición');
                    return;
                }
                
                contenedor.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">Cargando imágenes...</div>';

                fetch(`{{ route('admin.imagenes.listar') }}?carpeta=${carpeta}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            contenedor.innerHTML = '';
                            data.data.forEach(imagen => {
                                const div = document.createElement('div');
                                div.className = 'relative group cursor-pointer';
                                div.innerHTML = `
                                    <img src="${imagen.url}" alt="${imagen.nombre}" 
                                         class="w-full h-24 object-cover rounded border hover:border-blue-500 transition-colors">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center">
                                        <button onclick="seleccionarImagenEditar('${imagen.ruta}')" 
                                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                            Seleccionar
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">${imagen.nombre}</div>
                                `;
                                contenedor.appendChild(div);
                            });
                        } else {
                            contenedor.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No se encontraron imágenes en esta carpeta.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar imágenes:', error);
                        contenedor.innerHTML = '<div class="text-center text-red-500">Error al cargar las imágenes.</div>';
                    });
            }

            // Función para cargar imágenes en el modal
            function cargarImagenesEnModal(carpeta) {
                const contenedor = document.getElementById('contenido-modal-imagenes-categoria');
                if (!contenedor) {
                    console.error('No se encontró el contenedor del modal de imágenes');
                    return;
                }
                
                contenedor.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">Cargando imágenes...</div>';

                fetch(`{{ route('admin.imagenes.listar') }}?carpeta=${carpeta}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.data.length > 0) {
                            contenedor.innerHTML = '';
                            data.data.forEach(imagen => {
                                const div = document.createElement('div');
                                div.className = 'relative group cursor-pointer';
                                div.innerHTML = `
                                    <img src="${imagen.url}" alt="${imagen.nombre}" 
                                         class="w-full h-24 object-cover rounded border hover:border-blue-500 transition-colors">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-all flex items-center justify-center">
                                        <button onclick="seleccionarImagenCategoria('${imagen.ruta}')" 
                                                class="bg-blue-600 text-white px-3 py-1 rounded text-sm opacity-0 group-hover:opacity-100 transition-opacity">
                                            Seleccionar
                                        </button>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">${imagen.nombre}</div>
                                `;
                                contenedor.appendChild(div);
                            });
                        } else {
                            contenedor.innerHTML = '<div class="text-center text-gray-500 dark:text-gray-400">No se encontraron imágenes en esta carpeta.</div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error al cargar imágenes:', error);
                        contenedor.innerHTML = '<div class="text-center text-red-500">Error al cargar las imágenes.</div>';
                    });
            }
        });

         // Función global para seleccionar imagen desde el modal
 function seleccionarImagenCategoria(ruta) {
   // Actualizar el campo oculto con la ruta seleccionada
   const inputImagen = document.getElementById('ruta-imagen-categoria');
   if (inputImagen) {
       inputImagen.value = ruta;
   }
   
   // Actualizar la vista previa
   const preview = document.getElementById('preview-imagen-categoria');
   if (preview) {
       preview.src = `/storage/${ruta}`;
       preview.style.display = 'block';
   }
   
   // Cerrar el modal
   cerrarModalImagenesCategoria();
 }

        // Función global para cerrar modal
        function cerrarModalImagenesCategoria() {
            const modal = document.getElementById('modalImagenesCategoria');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.add('hidden');
            }
        }

         // Función global para seleccionar imagen en el modal de edición
 function seleccionarImagenEditar(ruta) {
   // Actualizar el campo de imagen usando Alpine.js
   const scope = document.querySelector('[x-data]')?._x_dataStack?.[0];
   if (scope) {
       scope.editarImagen = ruta;
   }
   
   // Cerrar el modal
   if (scope) {
       scope.openModalImagenesEditar = false;
   }
 }

 // Función de emergencia para ocultar todos los modales
 function ocultarModalesEmergencia() {
     // Ocultar modales por CSS de múltiples maneras
     const modales = document.querySelectorAll('[x-show]');
     modales.forEach(modal => {
         if (modal.getAttribute('x-show') && modal.getAttribute('x-show').includes('openModal')) {
             modal.style.display = 'none';
             modal.style.visibility = 'hidden';
             modal.style.opacity = '0';
             modal.style.pointerEvents = 'none';
             modal.style.position = 'absolute';
             modal.style.zIndex = '-9999';
         }
     });
     
     // Ocultar cualquier elemento con fixed y bg-black
     const elementosNegros = document.querySelectorAll('.fixed.inset-0.bg-black');
     elementosNegros.forEach(elemento => {
         elemento.style.display = 'none';
         elemento.style.visibility = 'hidden';
         elemento.style.opacity = '0';
         elemento.style.pointerEvents = 'none';
         elemento.style.position = 'absolute';
         elemento.style.zIndex = '-9999';
     });
     
     // Ocultar modales por Alpine.js
     const scope = document.querySelector('[x-data]')?._x_dataStack?.[0];
     if (scope) {
         scope.openModalImagenes = false;
         scope.openModalImagenesEditar = false;
         scope.openModal = false;
     }
     
     console.log('Modales ocultados por emergencia');
 }





     </script>

    {{-- SCRIPT PARA GESTIÓN DE SLUG --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const nombreInput = document.getElementById('nombre-categoria');
            const slugInput = document.getElementById('slug-categoria');
            let slugModificadoManualmente = false;
            
            // Función para convertir texto a slug
            function convertirASlug(texto) {
                return texto
                    .toString()
                    .toLowerCase()
                    .trim()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // Eliminar acentos
                    .replace(/[^a-z0-9]+/g, '-') // Reemplazar espacios y caracteres especiales con guiones
                    .replace(/^-+|-+$/g, ''); // Eliminar guiones al inicio y final
            }
            
            // Generar slug automáticamente cuando se escribe el nombre
            if (nombreInput && slugInput) {
                nombreInput.addEventListener('input', function() {
                    if (!slugModificadoManualmente) {
                        const slug = convertirASlug(this.value);
                        slugInput.value = slug;
                        // Verificar si el slug existe
                        verificarSlugExistente('crear');
                    }
                });
                
                // Marcar que el slug fue modificado manualmente
                slugInput.addEventListener('input', function() {
                    slugModificadoManualmente = true;
                    verificarSlugExistente('crear');
                });
                
                // Habilitar botón solo cuando hay nombre y slug
                function verificarCampos() {
                    const btnGuardar = document.getElementById('btn-guardar-categoria');
                    if (btnGuardar && nombreInput && slugInput) {
                        const tieneNombre = nombreInput.value.trim().length > 0;
                        const tieneSlug = slugInput.value.trim().length > 0;
                        // El botón se habilita solo si hay nombre y slug, y si el slug no existe
                        // La verificación de si existe se hace en verificarSlugExistente
                    }
                }
                
                nombreInput.addEventListener('input', verificarCampos);
                slugInput.addEventListener('input', verificarCampos);
                
                // Función para verificar si el slug existe (crear)
                async function verificarSlugExistente(tipo) {
                    const slug = slugInput.value.trim();
                    if (!slug) {
                        const mensajeDiv = document.getElementById('slug-mensaje');
                        if (mensajeDiv) {
                            mensajeDiv.classList.add('hidden');
                        }
                        return;
                    }
                    
                    const mensajeDiv = document.getElementById('slug-mensaje');
                    if (!mensajeDiv) return;
                    
                    mensajeDiv.classList.remove('hidden');
                    
                    try {
                        const response = await fetch('{{ route("admin.categorias.verificar-slug") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                slug: slug
                            })
                        });
                        
                        const data = await response.json();
                        
                        mensajeDiv.innerHTML = '';
                        const span = document.createElement('span');
                        const btnGuardar = document.getElementById('btn-guardar-categoria');
                        
                        if (data.existe) {
                            span.className = 'text-red-600 dark:text-red-400';
                            span.textContent = '⚠️ Este slug ya existe';
                            mensajeDiv.className = 'mt-1 text-sm text-red-600 dark:text-red-400';
                            if (btnGuardar) {
                                btnGuardar.disabled = true;
                            }
                        } else {
                            span.className = 'text-green-600 dark:text-green-400';
                            span.textContent = '✓ Slug disponible';
                            mensajeDiv.className = 'mt-1 text-sm text-green-600 dark:text-green-400';
                            if (btnGuardar) {
                                btnGuardar.disabled = false;
                            }
                        }
                        mensajeDiv.appendChild(span);
                    } catch (error) {
                        console.error('Error al verificar slug:', error);
                        mensajeDiv.classList.add('hidden');
                    }
                }
            }
        });
    </script>

    {{-- SCRIPT PARA PREVENIR DOBLE CLIC --}}
    <script>
        // Prevenir doble clic en enlaces y botones
        document.addEventListener('DOMContentLoaded', function() {
            const links = document.querySelectorAll('a[href]');
                link.addEventListener('click', function(e) {
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