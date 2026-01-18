@php
  // $cookiesUrl -> $u1, $privacyUrl -> $u2
  $u1 = url('/politica-cookies');
  $u2 = url('/privacidad');
@endphp

{{-- Overlay que bloquea la web hasta elegir --}}
{{-- cookie-overlay -> co1 --}}
<div id="co1" class="fixed inset-0 z-[9998] bg-black/60"></div>

{{-- Barra inferior: ocupa TODO el ancho --}}
{{-- cookie-bar -> cb1 --}}
<div id="cb1" class="fixed inset-x-0 bottom-0 z-[9999]">
  {{-- Franja completa --}}
  <div class="w-full bg-white border-t border-pink-200 shadow-lg">
    {{-- Contenido centrado y limitado al ancho de la web --}}
    <div class="max-w-6xl mx-auto px-4 py-5">
      <div class="text-center space-y-4">
        <p class="text-sm md:text-base text-gray-800">
          Usamos cookies necesarias para el funcionamiento del sitio. Puedes consultar la
          <a href="{{ $u1 }}" class="text-pink-600 hover:underline">Política de cookies</a>
          y la
          <a href="{{ $u2 }}" class="text-pink-600 hover:underline">Política de privacidad</a>.
        </p>

        {{-- Botones iguales (mismo ancho) --}}
        <div class="flex flex-col md:flex-row justify-center gap-3">
          {{-- cookie-accept -> ca1, cookie-reject -> cr1 --}}
          <button id="ca1"
                  class="inline-flex justify-center items-center w-full md:w-56 px-8 py-3 rounded-xl text-base font-semibold text-white bg-pink-500 hover:bg-pink-600 transition">
            Aceptar
          </button>
          <button id="cr1"
                  class="inline-flex justify-center items-center w-full md:w-56 px-8 py-3 rounded-xl text-base font-semibold text-white bg-pink-500 hover:bg-pink-600 transition">
            Rechazar todo
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  {{-- COOKIE_NAME -> _cn1 ('cookie_consent' -> 'c_c'), DAYS -> _d1 --}}
  const _cn1 = 'c_c';
  const _d1 = 180;

  {{-- readConsent() -> _rc1() --}}
  function _rc1() {
    try {
      const _m1 = document.cookie.match(/(?:^|; )c_c=([^;]+)/);
      return _m1 ? JSON.parse(decodeURIComponent(_m1[1])) : null;
    } catch { return null; }
  }
  {{-- writeConsent() -> _wc1() --}}
  function _wc1(_v1) {
    const _e1 = new Date(Date.now() + _d1 * 864e5).toUTCString();
    document.cookie = `${_cn1}=${encodeURIComponent(JSON.stringify(_v1))}; Path=/; Expires=${_e1}; SameSite=Lax`;
    window.dispatchEvent(new Event('cookie-consent-changed'));
  }
  {{-- hideBar() -> _hb1() --}}
  function _hb1() {
    {{-- bar -> _b1, overlay -> _o1 --}}
    const _b1 = document.getElementById('cb1');
    const _o1 = document.getElementById('co1');
    if (_b1) _b1.style.display = 'none';
    if (_o1) _o1.style.display = 'none';
  }

  {{-- Si ya eligió antes, ocultar todo --}}
  if (_rc1()) _hb1();

  {{-- cookie-accept -> ca1 --}}
  document.getElementById('ca1').addEventListener('click', function () {
    _wc1({ necessary: true, analytics: true, marketing: true });
    _hb1();
  });
  {{-- cookie-reject -> cr1 --}}
  document.getElementById('cr1').addEventListener('click', function () {
    _wc1({ necessary: true, analytics: false, marketing: false });
    _hb1();
  });

  {{-- API mínima --}}
  {{-- cat -> _c1, cb -> _cb1 --}}
  window.cookieConsent = {
    get: _rc1,
    has: _c1 => !!((_rc1() || {})[_c1]),
    onChange: _cb1 => window.addEventListener('cookie-consent-changed', () => _cb1(_rc1()))
  };
})();
</script>
