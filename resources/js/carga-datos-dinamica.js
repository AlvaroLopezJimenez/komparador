/**
 * Sistema de carga dinámica de datos protegidos
 * Carga ofertas, especificaciones e históricos mediante tokens efímeros
 */

(function() {
    'use strict';
    
    let tokenCache = null;
    let tokenExpiration = null;
    let fingerprint = null;
    let reintentosToken = 0;
    const MAX_REINTENTOS_TOKEN = 2;
    
    /**
     * Inicializa el sistema de carga dinámica
     */
    function init() {
        // Si el usuario está autenticado, cargar datos directamente sin token
        if (window.usuarioAutenticado === true) {
            console.log('Usuario autenticado detectado, cargando datos sin restricciones');
            cargarDatos();
            return;
        }
        
        // Generar fingerprint solo si no está autenticado
        fingerprint = window.generateFingerprint ? window.generateFingerprint() : generateFingerprintFallback();
        
        // Validar que el fingerprint tenga al menos 10 caracteres
        if (!fingerprint || fingerprint.length < 10) {
            console.error('Fingerprint inválido generado:', fingerprint);
            fingerprint = generateFingerprintFallback();
        }
        
        
        // Obtener token inicial
        obtenerToken().then(() => {
            // Cargar datos después de obtener token
            cargarDatos();
        }).catch(error => {
            console.error('Error inicializando carga dinámica:', error);
            // Si el error es relacionado con token, mostrar modal
            if (error.message && (error.message.includes('token') || error.message.includes('Token'))) {
                mostrarModalTokenExpirado();
            } else {
                mostrarError('Error al inicializar. Por favor, recarga la página.');
            }
        });
    }
    
    /**
     * Genera un fingerprint básico si no existe la función
     */
    function generateFingerprintFallback() {
        const components = [
            navigator.userAgent || '',
            `${screen.width}x${screen.height}`,
            navigator.language || '',
            screen.colorDepth || '',
            window.devicePixelRatio || 1
        ].join('|');
        
        // Hash múltiple para asegurar longitud
        let hash1 = 0;
        let hash2 = 0;
        for (let i = 0; i < components.length; i++) {
            const char = components.charCodeAt(i);
            hash1 = ((hash1 << 5) - hash1) + char;
            hash1 = hash1 & hash1;
            hash2 = ((hash2 << 3) - hash2) + char + i;
            hash2 = hash2 & hash2;
        }
        
        const hashStr = Math.abs(hash1).toString(36) + Math.abs(hash2).toString(36);
        
        // Asegurar longitud mínima de 10 caracteres
        if (hashStr.length < 10) {
            const timestampHash = Math.abs(Date.now()).toString(36).substring(0, 10 - hashStr.length);
            return (hashStr + timestampHash).substring(0, 10);
        }
        
        return hashStr.substring(0, 32);
    }
    
    /**
     * Obtiene un token efímero del servidor
     */
    async function obtenerToken() {
        // Verificar si el token en cache sigue siendo válido
        if (tokenCache && tokenExpiration && Date.now() < tokenExpiration) {
            return tokenCache;
        }
        
        // Limitar reintentos para evitar bucles infinitos
        if (reintentosToken >= MAX_REINTENTOS_TOKEN) {
            throw new Error('Demasiados intentos de obtener token. Por favor, recarga la página.');
        }
        
        reintentosToken++;
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            const response = await fetch('/api/token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Fingerprint': fingerprint,
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                
                // Si es rate limit, no reintentar más
                if (response.status === 429) {
                    reintentosToken = MAX_REINTENTOS_TOKEN; // Bloquear más reintentos
                    throw new Error('Demasiadas solicitudes de token. Por favor, espera un momento y recarga la página.');
                }
                
                throw new Error(errorData.error || 'Error al obtener token');
            }
            
            const data = await response.json();
            tokenCache = data.token;
            
            // Resetear contador de reintentos al obtener token exitosamente
            reintentosToken = 0;
            
            // Calcular expiración (usar expires_in del servidor o 60 segundos por defecto)
            const expiresIn = data.expires_in || 60;
            tokenExpiration = Date.now() + (expiresIn * 1000) - 5000; // 5 segundos de margen
            
            return tokenCache;
        } catch (error) {
            console.error('Error obteniendo token:', error);
            throw error;
        }
    }
    
    /**
     * Carga las ofertas del producto
     */
    async function cargarOfertas(productoId) {
        try {
            // Preparar headers según si el usuario está autenticado
            const headers = {
                'Accept': 'application/json'
            };
            
            // Solo añadir token y fingerprint si el usuario NO está autenticado
            if (window.usuarioAutenticado !== true) {
                const token = await obtenerToken();
                headers['X-Auth-Token'] = token;
                headers['X-Fingerprint'] = fingerprint;
            }
            
            const response = await fetch(`/api/ofertas/${productoId}`, {
                method: 'GET',
                headers: headers,
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                
                // Si el usuario está autenticado y recibe un 401, es un error del servidor
                // No intentar obtener token
                if (window.usuarioAutenticado === true) {
                    console.error('Error cargando ofertas (usuario autenticado):', errorData);
                    throw new Error(errorData.error || 'Error al cargar ofertas');
                }
                
                // Si el token expiró o es inválido, mostrar modal directamente sin intentar renovar
                if (response.status === 401 && (errorData.code === 'TOKEN_EXPIRED' || errorData.code === 'INVALID_TOKEN')) {
                    console.log('Token inválido o expirado, mostrando modal...');
                    mostrarModalTokenExpirado();
                    throw new Error('Token inválido o expirado');
                }
                
                throw new Error(errorData.error || 'Error al cargar ofertas');
            }
            
            const data = await response.json();
            // Devolver ofertas junto con especificaciones (todo viene en la misma respuesta)
            return {
                ofertas: data.ofertas || [],
                especificaciones: data.especificaciones || null,
                grupos_de_ofertas: data.grupos_de_ofertas || null,
                columnas_data: data.columnas_data || null
            };
        } catch (error) {
            console.error('Error cargando ofertas:', error);
            throw error;
        }
    }
    
    /**
     * Carga las especificaciones del producto
     */
    async function cargarEspecificaciones(productoId) {
        try {
            const token = await obtenerToken();
            
            const response = await fetch(`/api/especificaciones/${productoId}`, {
                method: 'GET',
                headers: {
                    'X-Auth-Token': token,
                    'X-Fingerprint': fingerprint,
                    'Accept': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                
                // Si el token expiró o es inválido, mostrar modal directamente sin intentar renovar
                if (response.status === 401 && (errorData.code === 'TOKEN_EXPIRED' || errorData.code === 'INVALID_TOKEN')) {
                    console.log('Token inválido o expirado, mostrando modal...');
                    mostrarModalTokenExpirado();
                    throw new Error('Token inválido o expirado');
                }
                
                throw new Error(errorData.error || 'Error al cargar especificaciones');
            }
            
            const data = await response.json();
            return {
                especificaciones: data.especificaciones,
                grupos_de_ofertas: data.grupos_de_ofertas,
                columnas_data: data.columnas_data // Incluir columnas_data procesado
            };
        } catch (error) {
            console.error('Error cargando especificaciones:', error);
            throw error;
        }
    }
    
    /**
     * Carga los precios históricos del producto
     */
    async function cargarHistoricos(productoId, periodo = '3m') {
        try {
            // Preparar headers según si el usuario está autenticado
            const headers = {
                'Accept': 'application/json'
            };
            
            // Solo añadir token y fingerprint si el usuario NO está autenticado
            if (window.usuarioAutenticado !== true) {
                const token = await obtenerToken();
                headers['X-Auth-Token'] = token;
                headers['X-Fingerprint'] = fingerprint;
            }
            
            const response = await fetch(`/api/precios-historicos/${productoId}?periodo=${periodo}`, {
                method: 'GET',
                headers: headers,
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                
                // Si el token expiró o es inválido, mostrar modal directamente sin intentar renovar
                if (response.status === 401 && (errorData.code === 'TOKEN_EXPIRED' || errorData.code === 'INVALID_TOKEN')) {
                    console.log('Token inválido o expirado, mostrando modal...');
                    mostrarModalTokenExpirado();
                    throw new Error('Token inválido o expirado');
                }
                
                throw new Error(errorData.error || 'Error al cargar históricos');
            }
            
            const data = await response.json();
            return data.precios || [];
        } catch (error) {
            console.error('Error cargando históricos:', error);
            throw error;
        }
    }
    
    /**
     * Carga todos los datos del producto
     */
    async function cargarDatos() {
        // Obtener productoId del DOM o de una variable global
        const productoId = window.productoId || document.querySelector('[data-producto-id]')?.dataset.productoId;
        
        if (!productoId) {
            console.error('No se encontró productoId');
            return;
        }
        
        try {
            // Cargar ofertas (que ahora incluyen también las especificaciones)
            const datos = await cargarOfertas(productoId);
            window.ofertasCargadas = datos.ofertas;
            window.especificacionesCargadas = {
                especificaciones: datos.especificaciones,
                grupos_de_ofertas: datos.grupos_de_ofertas,
                columnas_data: datos.columnas_data
            };
            
            // Esperar a que el DOM esté completamente listo y las funciones estén definidas
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(() => {
                        // Disparar evento de ofertas cargadas (con ofertas)
                        window.dispatchEvent(new CustomEvent('ofertas-cargadas', { 
                            detail: { ofertas: datos.ofertas } 
                        }));
                        
                        // Disparar evento de especificaciones cargadas (con especificaciones)
                        window.dispatchEvent(new CustomEvent('especificaciones-cargadas', { 
                            detail: {
                                especificaciones: datos.especificaciones,
                                grupos_de_ofertas: datos.grupos_de_ofertas,
                                columnas_data: datos.columnas_data
                            }
                        }));
                    }, 100);
                });
            } else {
                // DOM ya está listo, esperar un poco para asegurar que las funciones estén definidas
                setTimeout(() => {
                    // Disparar evento de ofertas cargadas (con ofertas)
                    window.dispatchEvent(new CustomEvent('ofertas-cargadas', { 
                        detail: { ofertas: datos.ofertas } 
                    }));
                    
                    // Disparar evento de especificaciones cargadas (con especificaciones)
                    window.dispatchEvent(new CustomEvent('especificaciones-cargadas', { 
                        detail: {
                            especificaciones: datos.especificaciones,
                            grupos_de_ofertas: datos.grupos_de_ofertas,
                            columnas_data: datos.columnas_data
                        }
                    }));
                }, 100);
            }
            
        } catch (error) {
            console.error('Error cargando datos:', error);
            // Si el error es relacionado con token, el modal ya se habrá mostrado
            // Si no, mostrar error genérico
            if (!error.message.includes('token') && !error.message.includes('Token')) {
                mostrarError('Error al cargar datos. Por favor, recarga la página.');
            }
        }
    }
    
    /**
     * Muestra un error al usuario
     */
    function mostrarError(mensaje) {
        // Buscar contenedor de ofertas y mostrar mensaje
        const contenedor = document.getElementById('x6');
        if (contenedor) {
            contenedor.innerHTML = `<div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded text-lg text-center'>${mensaje}</div>`;
        }
    }
    
    /**
     * Muestra el modal de token expirado/inválido
     */
    function mostrarModalTokenExpirado() {
        const modal = document.getElementById('x27');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    }
    
    // Exportar funciones globales
    window.cargarDatosDinamicos = {
        init: init,
        cargarOfertas: cargarOfertas,
        cargarEspecificaciones: cargarEspecificaciones,
        cargarHistoricos: cargarHistoricos,
        obtenerToken: obtenerToken,
        mostrarModalTokenExpirado: mostrarModalTokenExpirado
    };
    
    // Auto-inicializar cuando el DOM esté listo
    // Esperar un poco para asegurar que los listeners de la vista estén configurados
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            // Esperar un poco más para que los listeners de la vista blade estén configurados
            setTimeout(init, 200);
        });
    } else {
        // DOM ya está listo, esperar un poco para que los listeners estén configurados
        setTimeout(init, 200);
    }
})();

