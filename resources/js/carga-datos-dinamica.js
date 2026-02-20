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
    let captchaTokenResuelto = null; // Token del CAPTCHA resuelto
    const MAX_REINTENTOS_TOKEN = 2;
    
    /**
     * Inicializa el sistema de carga dinámica
     */
    function init() {
        // Si el usuario está autenticado, cargar datos directamente sin token

        if (window.usuarioAutenticado === true) {
            cargarDatos();
            return;
        }
        

        fingerprint = window.generateFingerprint ? window.generateFingerprint() : generateFingerprintFallback();
        

        if (!fingerprint || fingerprint.length < 10) {
            fingerprint = generateFingerprintFallback();
        }
        
        

        obtenerToken().then(() => {

            cargarDatos();
        }).catch(error => {

            if (error.message && (error.message.includes('token') || error.message.includes('Token'))) {
                mostrarModalTokenExpirado();
            } else if (error.message && (error.message.includes('CAPTCHA requerido') || error.message.includes('Límite diario'))) {
                // CAPTCHA o límite diario ya se mostró en obtenerToken() o cargarOfertas()
                // No hacer nada aquí, ya se mostró el mensaje/CAPTCHA
            } else {
                // Solo mostrar error genérico si no es CAPTCHA ni límite diario
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
     * @param {string} captchaToken - Token del CAPTCHA si se resolvió uno
     */
    async function obtenerToken(captchaToken = null) {
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
            
            const headers = {
                'Content-Type': 'application/json',
                'X-Fingerprint': fingerprint,
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            };
            
            // Si hay token de CAPTCHA (parámetro o guardado), añadirlo al header
            const tokenCaptcha = captchaToken || captchaTokenResuelto;
            if (tokenCaptcha) {
                headers['X-Captcha-Token'] = tokenCaptcha;
            }
            
            const response = await fetch('/api/token', {
                method: 'POST',
                headers: headers,
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                let errorData = {};
                let responseText = '';
                try {
                    responseText = await response.text();
                    if (responseText) {
                        errorData = JSON.parse(responseText);
                    }
                } catch (e) {

                }
                

                if (response.status === 429) {
                    reintentosToken = MAX_REINTENTOS_TOKEN; 
                    

                    if (errorData.code === 'RATE_LIMIT_EXCEEDED_DAY') {
                        mostrarErrorDiario(errorData.error || 'Demasiadas peticiones realizadas. Si es un error por favor ponte en contacto con info@komparador.com', errorData.contact_email || 'info@komparador.com');
                        throw new Error('Límite diario excedido');
                    }
                    

                    if (errorData.captcha_required === true || !errorData.code || errorData.code !== 'RATE_LIMIT_EXCEEDED_DAY') {
                        mostrarCaptcha();
                        throw new Error('CAPTCHA requerido');
                    }
                }
                
                throw new Error(errorData.error || 'Error al obtener token');
            }
            
            const data = await response.json();
            tokenCache = data.token;
            
            // Resetear contador de reintentos y limpiar token de CAPTCHA al obtener token exitosamente
            reintentosToken = 0;
            captchaTokenResuelto = null; // Limpiar token de CAPTCHA después de usarlo
            
            // Calcular expiración (usar expires_in del servidor o 60 segundos por defecto)
            const expiresIn = data.expires_in || 60;
            tokenExpiration = Date.now() + (expiresIn * 1000) - 5000; // 5 segundos de margen 
            
            return tokenCache;
        } catch (error) {
            console.error('Error obteniendo token:', error);
            throw error;
        }
    }
    
    async function cargarOfertas(productoId) {
        try {

            const headers = {
                'Accept': 'application/json'
            };
            

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
                let errorData = {};
                try {
                    const text = await response.text();
                    errorData = JSON.parse(text);
                } catch (e) {

                }
                

                if (window.usuarioAutenticado === true) {
                    throw new Error(errorData.error || 'Error al cargar ofertas');
                }
                

                if (response.status === 401 && (errorData.code === 'TOKEN_EXPIRED' || errorData.code === 'INVALID_TOKEN')) {
                    mostrarModalTokenExpirado();
                    throw new Error('Token inválido o expirado');
                }
                

                if (response.status === 429 && errorData.captcha_required === true) {
                    mostrarCaptcha();
                    throw new Error('CAPTCHA requerido');
                }
                

                if (response.status === 429 && errorData.code === 'RATE_LIMIT_EXCEEDED_DAY') {
                    mostrarErrorDiario(errorData.error, errorData.contact_email);
                    throw new Error('Límite diario excedido');
                }
                

                if (response.status === 429) {
                    if (!errorData.code || errorData.code !== 'RATE_LIMIT_EXCEEDED_DAY') {
                        mostrarCaptcha();
                        throw new Error('CAPTCHA requerido');
                    }
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
                columnas_data: data.columnas_data 
            };
        } catch (error) {
            console.error('Error cargando especificaciones:', error);
            throw error;
        }
    }
    
    async function cargarHistoricos(productoId, periodo = '3m') {
        try {

            const headers = {
                'Accept': 'application/json'
            };
            

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
            // Si es límite diario, ya se mostró el mensaje de contacto
            // Si es CAPTCHA requerido, ya se mostró el CAPTCHA
            // Si no, mostrar error genérico

            if (!error.message.includes('token') && 
                !error.message.includes('Token') && 
                !error.message.includes('Límite diario') &&
                !error.message.includes('CAPTCHA requerido')) {
                mostrarError('Error al cargar datos. Por favor, recarga la página.');
            }
        }
    }
    
    function mostrarError(mensaje) {

        const contenedor = document.getElementById('x6');
        if (contenedor) {
            contenedor.innerHTML = `<div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded text-lg text-center'>${mensaje}</div>`;
        }
    }
    
    /**
     * Muestra error de límite diario excedido (sin CAPTCHA)
     */
    function mostrarErrorDiario(mensaje, email) {
        const contenedor = document.getElementById('x6');
        if (contenedor) {
            const emailLink = email ? `<p class='text-sm mt-4'><a href='mailto:${email}' class='text-blue-600 underline hover:text-blue-800'>${email}</a></p>` : '';
            contenedor.innerHTML = `
                <div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded text-lg text-center'>
                    <p class='mb-2'>${mensaje}</p>
                    ${emailLink}
                </div>
            `;
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
    
    /**
     * Muestra el CAPTCHA cuando se requiere por rate limit
     */
    function mostrarCaptcha() {
        const contenedor = document.getElementById('x6');
        if (!contenedor) {
            return;
        }
        
        const recaptchaSiteKey = window.recaptchaSiteKey || '6LdVT0AsAAAAANV0xlEtKRr7y27sqoG1ICTAVBMV';
        
        // Esperar a que grecaptcha esté disponible antes de insertar el HTML
        const checkAndInsert = () => {
            if (typeof grecaptcha !== 'undefined' && grecaptcha.render) {
                // grecaptcha está disponible, insertar HTML
                contenedor.innerHTML = `
                    <div class='bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-6 rounded'>
                        <h3 class='text-xl font-semibold mb-4 text-center'>Has realizado demasiadas peticiones</h3>
                        <p class='text-center mb-6'>Por favor, completa el CAPTCHA para continuar:</p>
                        
                        <form id="captcha-form-rate-limit" class="max-w-md mx-auto">
                            <input type="hidden" name="captcha_token" id="captcha-token">
                            
                            <div class="flex justify-center mb-4">
                                <div id="recaptcha-container-rate-limit"></div>
                            </div>
                            
                            <button type="submit" 
                                    id="btn-verificar-captcha"
                                    disabled
                                    class="w-full disabled:bg-gray-400 disabled:cursor-not-allowed bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                                Verificar y continuar
                            </button>
                        </form>
                        
                        <p class="text-xs text-gray-500 text-center mt-4">
                            Esta verificación ayuda a proteger nuestro sitio de accesos automatizados.
                        </p>
                    </div>
                `;
                
                // Renderizar el CAPTCHA explícitamente
                setTimeout(() => {
                    const recaptchaDiv = document.getElementById('recaptcha-container-rate-limit');
                    if (recaptchaDiv && !recaptchaDiv.hasChildNodes()) {
                        grecaptcha.render('recaptcha-container-rate-limit', {
                            'sitekey': recaptchaSiteKey,
                            'callback': onCaptchaSuccessRateLimit
                        });
                    }
                }, 100);
                
                // Configurar submit del formulario
                const form = document.getElementById('captcha-form-rate-limit');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        e.preventDefault();
                        const token = document.getElementById('captcha-token')?.value;
                        if (token) {
                            verificarCaptchaYDesbloquear(token);
                        }
                    });
                }
            } else {
                // grecaptcha aún no está disponible, esperar un poco más
                setTimeout(checkAndInsert, 100);
            }
        };
        
        // Iniciar la verificación
        checkAndInsert();
    }
    
    /**
     * Callback cuando se resuelve el CAPTCHA (igual que en redireccion.blade.php)
     */
    window.onCaptchaSuccessRateLimit = function(token) {
        document.getElementById('captcha-token').value = token;
        document.getElementById('btn-verificar-captcha').disabled = false;
    };
    
    /**
     * Verifica el CAPTCHA y desbloquea la IP
     */
    async function verificarCaptchaYDesbloquear(token) {
        const contenedor = document.getElementById('x6');
        if (!contenedor) return;
        
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            
            contenedor.innerHTML = '<div class="text-center p-4"><p class="text-blue-600">Verificando CAPTCHA...</p></div>';
            
            const response = await fetch('/api/captcha/verificar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Fingerprint': fingerprint,
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ captcha_token: token }),
                credentials: 'same-origin'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // CAPTCHA verificado, guardar token para usarlo en la siguiente petición
                captchaTokenResuelto = token;
                // Limpiar cache de token para forzar nueva obtención
                tokenCache = null;
                tokenExpiration = null;
                reintentosToken = 0; // Resetear reintentos
                
                // Recargar datos (que volverá a obtener token, pero ahora con CAPTCHA resuelto)
                contenedor.innerHTML = '<div class="text-center p-4"><p class="text-green-600">CAPTCHA verificado. Cargando ofertas...</p></div>';
                await cargarDatos();
            } else {
                contenedor.innerHTML = `
                    <div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded'>
                        <p class='text-center mb-4'>Error al verificar CAPTCHA: ${data.error || 'Error desconocido'}</p>
                        <button onclick="mostrarCaptcha()" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded">
                            Intentar de nuevo
                        </button>
                    </div>
                `;
                // Resetear CAPTCHA
                if (window.grecaptcha) {
                    window.grecaptcha.reset();
                }
            }
        } catch (error) {
            console.error('Error verificando CAPTCHA:', error);
            contenedor.innerHTML = `
                <div class='bg-red-100 border-l-4 border-red-500 text-red-800 p-6 rounded'>
                    <p class='text-center mb-4'>Error al verificar CAPTCHA. Por favor, recarga la página.</p>
                    <button onclick="window.location.reload()" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 px-4 rounded">
                        Recargar página
                    </button>
                </div>
            `;
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
    

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {

            setTimeout(init, 200);
        });
    } else {

        setTimeout(init, 200);
    }
})();
