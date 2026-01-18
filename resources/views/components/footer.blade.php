@php
// añadirCam() -> _f1() - Función para añadir parámetro cam a URLs de forma segura
if (!function_exists('_f1')) {
    function _f1($url) {
        if (!request()->has('cam')) {
            return $url;
        }
        
        $cam = request('cam');
        
        // Validar que solo contenga caracteres alfanuméricos, guiones y guiones bajos
        // Esto evita caracteres especiales que puedan romper la URL
        if (!preg_match('/^[a-zA-Z0-9\-_]+$/', $cam)) {
            // Si el parámetro cam no es válido, simplemente no lo añadimos
            return $url;
        }
        
        // Construir la URL de forma segura
        $separator = strpos($url, '?') !== false ? '&' : '?';
        // Aunque ya validamos, urlencode() añade una capa extra de seguridad
        return $url . $separator . 'cam=' . urlencode($cam);
    }
}
@endphp
<footer class="bg-gray-800 text-white text-sm mt-12">
    <div class="max-w-7xl mx-auto px-6 py-10 grid grid-cols-1 md:grid-cols-3 gap-12 text-left items-start">

        {{-- Columna 1 --}}
        <div class="px-2 md:pl-28">
            <h3 class="text-base font-semibold mb-2">Síguenos</h3>
            <div class="flex space-x-4 mb-4">
                <a href="#" class="hover:text-pink-400 transition-colors" aria-label="Facebook">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22 12a10 10 0 10-11.63 9.87v-6.99H8.9V12h1.47v-1.46c0-1.44.86-2.23 2.18-2.23.63 0 1.28.11 1.28.11v1.4h-.72c-.71 0-.93.44-.93.89V12h1.59l-.25 2.88h-1.34v6.99A10 10 0 0022 12z"/>
                    </svg>
                </a>
                <a href="#" class="hover:text-pink-400 transition-colors" aria-label="Instagram">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7.75 2h8.5A5.75 5.75 0 0122 7.75v8.5A5.75 5.75 0 0116.25 22h-8.5A5.75 5.75 0 012 16.25v-8.5A5.75 5.75 0 017.75 2zm0 1.5A4.25 4.25 0 003.5 7.75v8.5A4.25 4.25 0 007.75 20.5h8.5a4.25 4.25 0 004.25-4.25v-8.5A4.25 4.25 0 0016.25 3.5h-8.5zm4.25 3a5.25 5.25 0 110 10.5 5.25 5.25 0 010-10.5zm0 1.5a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5zm5.25-.75a.75.75 0 110 1.5.75.75 0 010-1.5z"/>
                    </svg>
                </a>
            </div>
            {{-- añadirCam() -> _f1() --}}
            <a href="{{ _f1(route('politicas.contacto')) }}" class="block hover:text-pink-400 transition-colors">Contáctanos</a>
            <a href="{{ _f1(route('politicas.contacto')) }}" class="block hover:text-pink-400 transition-colors">Colaboraciones</a>
        </div>

        {{-- Columna 2 --}}
        <div class="px-2 max-w-[400px]">
            <h3 class="text-base font-semibold mb-2">Información</h3>
            <p class="text-gray-300 leading-relaxed">
                En calidad de Afiliado de Amazon, obtengo ingresos por las compras adscritas que cumplen los requisitos aplicables.
            </p>
            <p class="text-gray-300 leading-relaxed mt-2">
                Los precios incluyen IVA. Puede haber diferencias puntuales respecto al portal del vendedor, así como en los costes de envío.
            </p>
        </div>

        {{-- Columna 3 --}}
        <div class="px-6 md:pl-36">
            <h3 class="text-base font-semibold mb-2">Legal</h3>
            <ul class="space-y-2">
                <li><a href="{{ route('politicas.aviso-legal') }}" class="hover:text-pink-400 transition-colors">Aviso legal</a></li>
                <li><a href="{{ route('politicas.privacidad') }}" class="hover:text-pink-400 transition-colors">Política de privacidad</a></li>
                <li><a href="{{ route('politicas.cookies') }}" class="hover:text-pink-400 transition-colors">Política de cookies</a></li>
            </ul>
        </div>

    </div>
</footer>