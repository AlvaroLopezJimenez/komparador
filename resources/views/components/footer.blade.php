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

$y1 = now()->year;
@endphp
<footer class="kk-footer">
    <div class="kk-footer-grid">
        <div>
            <a href="{{ _f1(route('home')) }}" class="kk-footer-logo">
                <img src="{{ asset('images/logo.webp') }}" alt="Komparador" class="kk-logo-img">
            </a>
            <p class="kk-footer-text">
                En calidad de Afiliado de Amazon, obtengo ingresos por las compras adscritas que cumplen los requisitos aplicables.
            </p>
            <p class="kk-footer-text">
                Los precios incluyen IVA. Puede haber diferencias puntuales respecto al portal del vendedor, así como en los costes de envío.
            </p>
        </div>

        <div>
            <h4>Síguenos</h4>
            <div class="kk-social-row">
                <a href="#" class="kk-social-link" aria-label="Facebook">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M22 12a10 10 0 10-11.63 9.87v-6.99H8.9V12h1.47v-1.46c0-1.44.86-2.23 2.18-2.23.63 0 1.28.11 1.28.11v1.4h-.72c-.71 0-.93.44-.93.89V12h1.59l-.25 2.88h-1.34v6.99A10 10 0 0022 12z"/>
                    </svg>
                </a>
                <a href="#" class="kk-social-link" aria-label="Instagram">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M7.75 2h8.5A5.75 5.75 0 0122 7.75v8.5A5.75 5.75 0 0116.25 22h-8.5A5.75 5.75 0 012 16.25v-8.5A5.75 5.75 0 017.75 2zm0 1.5A4.25 4.25 0 003.5 7.75v8.5A4.25 4.25 0 007.75 20.5h8.5a4.25 4.25 0 004.25-4.25v-8.5A4.25 4.25 0 0016.25 3.5h-8.5zm4.25 3a5.25 5.25 0 110 10.5 5.25 5.25 0 010-10.5zm0 1.5a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5zm5.25-.75a.75.75 0 110 1.5.75.75 0 010-1.5z"/>
                    </svg>
                </a>
            </div>
            <ul>
                <li><a href="{{ _f1(route('politicas.contacto')) }}">Contáctanos</a></li>
                <li><a href="{{ _f1(route('politicas.contacto')) }}">Colaboraciones</a></li>
            </ul>
        </div>

        <div>
            <h4>Legal</h4>
            <ul>
                <li><a href="{{ route('politicas.aviso-legal') }}">Aviso legal</a></li>
                <li><a href="{{ route('politicas.privacidad') }}">Política de privacidad</a></li>
                <li><a href="{{ route('politicas.cookies') }}">Política de cookies</a></li>
            </ul>
        </div>
    </div>

    <div class="kk-footer-bottom">
        © {{ $y1 }} Komparador.com
    </div>
</footer>

<style>
    .kk-footer {
        background: #0f172a;
        color: #94a3b8;
        font-size: .8125rem;
        margin-top: 2.5rem;
        padding: 2.4rem 1.25rem 1.8rem;
    }
    .kk-footer-grid {
        max-width: 1200px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 2fr 1fr 1fr;
        gap: 2rem;
    }
    .kk-footer-logo {
        display: inline-flex;
        align-items: center;
        margin-bottom: 1rem;
    }
    .kk-logo-img { height: 32px; width: auto; }
    .kk-footer h4 {
        color: #fff;
        font-size: .75rem;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin: 0 0 1rem;
        font-weight: 700;
    }
    .kk-footer-text {
        margin: 0 0 .65rem;
        line-height: 1.6;
        color: #cbd5e1;
        max-width: 46ch;
    }
    .kk-footer ul { list-style: none; margin: 0; padding: 0; }
    .kk-footer li { margin-bottom: .5rem; }
    .kk-footer a { color: #94a3b8; transition: color .2s; }
    .kk-footer a:hover { color: #fff; }
    .kk-social-row {
        display: flex;
        align-items: center;
        gap: .7rem;
        margin-bottom: .7rem;
    }
    .kk-social-link { color: #fff !important; }
    .kk-social-link:hover { color: #e97b11 !important; }
    .kk-footer-bottom {
        max-width: 1200px;
        margin: 1.8rem auto 0;
        padding-top: 1.2rem;
        border-top: 1px solid #334155;
        text-align: center;
        color: #94a3b8;
    }
    @media (max-width: 800px) {
        .kk-footer-grid {
            grid-template-columns: 1fr;
            gap: 1.4rem;
        }
    }
</style>