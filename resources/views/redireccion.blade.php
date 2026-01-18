<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@if(isset($bloqueadoMensual) && $bloqueadoMensual) Acceso bloqueado @elseif(isset($requiereCaptcha) && $requiereCaptcha) Verificación de seguridad @else Redirigiendo... @endif</title>
    @if(isset($requiereCaptcha) && $requiereCaptcha || isset($bloqueadoMensual) && $bloqueadoMensual)
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('images/icono.webp') }}">
    <style>
        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            text-align: center;
        }

        .contenedor {
            max-width: 400px;
            padding: 2rem;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #ddd;
            border-top: 5px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 2rem auto;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .mensaje {
            font-size: 1.2rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    @if(isset($bloqueadoMensual) && $bloqueadoMensual)
        {{-- BLOQUEO MENSUAL --}}
        <div class="min-h-screen flex items-center justify-center px-4 bg-gray-50">
            <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-6">
                    <svg class="mx-auto h-12 w-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900 mt-4">🚗 Multa por exceso de velocidad</h1>
                    <p class="text-gray-600 mt-4 text-lg">
                        ¡Ups! Has sido multado por circular demasiado rápido por nuestra web.
                    </p>
                    <p class="text-gray-700 mt-3">
                        Tu dirección IP ha superado el límite de peticiones permitidas y ha sido bloqueada temporalmente.
                    </p>
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mt-6 rounded">
                        <p class="text-sm text-gray-700 font-medium">
                            Si crees que esta multa es un error, ponte en contacto con la 
                            <strong class="text-gray-900">Jefatura de Trafico Web</strong> en:
                        </p>
                        <p class="mt-2">
                            <a href="mailto:info@komparador.com" class="text-blue-600 hover:text-blue-800 hover:underline font-semibold">
                                info@komparador.com
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    
    @elseif(isset($requiereCaptcha) && $requiereCaptcha)
        {{-- CAPTCHA REQUERIDO --}}
        <div class="min-h-screen flex items-center justify-center px-4 bg-gray-50">
            <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8">
                <div class="text-center mb-6">
                    <svg class="mx-auto h-12 w-12 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h1 class="text-2xl font-bold text-gray-900 mt-4">Verificación de seguridad</h1>
                    <p class="text-gray-600 mt-2">
                        Hemos detectado muchas peticiones desde tu dirección IP. 
                        Por favor, completa el CAPTCHA para continuar.
                    </p>
                </div>
                
                <form id="captcha-form" method="GET" action="{{ route('click.redirigir', $ofertaId ?? '') }}">
                    @if(isset($cam) && $cam)
                        <input type="hidden" name="cam" value="{{ $cam }}">
                    @endif
                    <input type="hidden" name="captcha_token" id="captcha-token">
                    
                    <div class="flex justify-center mb-6">
                        <div class="g-recaptcha" 
                             data-sitekey="{{ env('RECAPTCHA_SITE_KEY', '6LdVT0AsAAAAANV0xlEtKRr7y27sqoG1ICTAVBMV') }}"
                             data-callback="onCaptchaSuccess"></div>
                    </div>
                    
                    <button type="submit" 
                            id="btn-continuar"
                            disabled
                            class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-semibold py-3 px-4 rounded-lg transition-colors">
                        Continuar a la tienda
                    </button>
                </form>
                
                <p class="text-xs text-gray-500 text-center mt-4">
                    Esta verificación ayuda a proteger nuestro sitio de accesos automatizados.
                </p>
            </div>
        </div>
        
        <script>
            function onCaptchaSuccess(token) {
                document.getElementById('captcha-token').value = token;
                document.getElementById('btn-continuar').disabled = false;
            }
        </script>
    
    @else
        {{-- REDIRECCIÓN NORMAL --}}
        <div class="contenedor">
            <div class="spinner"></div>
            <p class="mensaje">Estás siendo redirigido a la tienda...</p>
            @if(isset($url) && $url)
                <p class="text-sm text-gray-500">
                    Si no ocurre nada en unos segundos, 
                    <a href="{{ $url }}" rel="sponsored noopener noreferrer" style="color:#3b82f6; font-weight:600;">haz clic aquí</a>.
                </p>
            @endif
        </div>
        
        @if(isset($url) && $url)
        <script>
            setTimeout(() => {
                window.location.href = @json($url);
            }, 0);
        </script>
        @endif
    @endif
</body>
</html>
