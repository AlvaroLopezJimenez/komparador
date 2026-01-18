<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contacto - Komparador</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    
    {{-- HEADER --}}
    <x-header />
    
    {{-- CONTENIDO PRINCIPAL --}}
    <main class="max-w-4xl mx-auto px-6 py-8 bg-gray-100">
        <div class="bg-white rounded-lg shadow-md p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Contacto</h1>
            
            <div class="prose prose-lg max-w-none">
                <p class="text-lg text-gray-700 leading-relaxed mb-6">
                    ¿Tienes alguna pregunta, sugerencia o quieres colaborar con nosotros? 
                    No dudes en ponerte en contacto con nuestro equipo.
                </p>
                
                <div class="bg-blue-50 border-l-4 border-blue-400 p-6 mb-8">
                    <h2 class="text-xl font-semibold text-blue-800 mb-3">Información de Contacto</h2>
                    <p class="text-blue-700 mb-2">
    <strong>Email:</strong>

    <span id="email-container" class="hidden">
        <a href="mailto:info@komparador.com" class="text-blue-600 hover:text-blue-800 underline">
            info@komparador.com
        </a>
    </span>

    <button 
        id="btn-ver-correo"
        class="ml-2 text-sm text-white bg-blue-500 px-3 py-1 rounded hover:bg-blue-600 transition">
        info@komparador.com
    </button>

    <div id="captcha-container" class="g-recaptcha"
        data-sitekey="6LeDYZ0rAAAAAKtuossDvmAMdtaltoeMTcUbfLTA"
        data-callback="onCaptchaSuccess"
        data-size="invisible">
    </div>
</p>


                    <p class="text-blue-700">
                        Te responderemos en un plazo máximo de 24-48 horas.
                    </p>
                </div>
                
                <h2 class="text-2xl font-bold text-gray-800 mb-4">¿En qué podemos ayudarte?</h2>
                
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Soporte Técnico</h3>
                        <p class="text-gray-700">
                            Si tienes problemas con la web o encuentras algún error, 
                            envíanos un email con los detalles para solucionarlo rápidamente.
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Sugerencias</h3>
                        <p class="text-gray-700">
                            ¿Tienes ideas para mejorar Komparador? 
                            Nos encanta recibir feedback de nuestros usuarios.
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Colaboraciones</h3>
                        <p class="text-gray-700">
                            Si quieres colaborar con nosotros o tienes una propuesta 
                            comercial, estaremos encantados de escucharte.
                        </p>
                    </div>
                    
                    <div class="bg-gray-50 p-6 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-3">Prensa</h3>
                        <p class="text-gray-700">
                            Para entrevistas, notas de prensa o información 
                            para medios de comunicación.
                        </p>
                    </div>
                </div>
                
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6">
                    <h3 class="text-lg font-semibold text-yellow-800 mb-2">Horario de Atención</h3>
                    <p class="text-yellow-700">
                        <strong>Lunes a Viernes:</strong> 9:00 - 18:00<br>
                        <strong>Sábados:</strong> 10:00 - 14:00<br>
                        <strong>Domingos:</strong> ☀️ 🏖️ 🍹
                    </p>
                </div>
            </div>
        </div>
    </main>
    
    {{-- FOOTER --}}
    <x-footer />

    {{--MOSTRAR CORREO--}}
    <script>
    let captchaWidgetId;

    window.onload = function () {
        const checkRecaptcha = () => {
            if (typeof grecaptcha !== 'undefined') {
                captchaWidgetId = grecaptcha.render('captcha-container', {
                    'sitekey': '6LeDYZ0rAAAAAKtuossDvmAMdtaltoeMTcUbfLTA',
                    'callback': onCaptchaSuccess,
                    'size': 'invisible'
                });

                document.getElementById('btn-ver-correo').addEventListener('click', function () {
                    grecaptcha.execute(captchaWidgetId);
                });
            } else {
                setTimeout(checkRecaptcha, 200); // reintenta hasta que cargue
            }
        };

        checkRecaptcha();
    };

    function onCaptchaSuccess() {
        document.getElementById('email-container').classList.remove('hidden');
        document.getElementById('btn-ver-correo').remove();
    }
</script>
<script src="https://www.google.com/recaptcha/api.js?render=explicit" async defer></script>




    @stack('scripts')
</body>
</html> 