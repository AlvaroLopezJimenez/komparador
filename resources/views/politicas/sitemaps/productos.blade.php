{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($productos as $producto)
        @php
            // Construir la ruta manualmente si no existe el atributo
            $rutaCompleta = '';
            if (isset($producto->ruta_completa)) {
                $rutaCompleta = $producto->ruta_completa;
            } else {
                // Construir ruta manualmente
                $categorias = [];
                $categoria = $producto->categoria;
                
                while ($categoria) {
                    array_unshift($categorias, $categoria->slug);
                    $categoria = $categoria->parent;
                }
                
                $rutaCompleta = implode('/', $categorias) . '/' . $producto->slug;
            }
        @endphp
    <url>
        <loc>https://komparador.com/{{ $rutaCompleta }}</loc>
        <lastmod>{{ $producto->updated_at->utc()->format('Y-m-d\TH:i:s\Z') }}</lastmod>
        @php
    $c = $producto->clicks ?? 0;

    // Si todos los cuartiles son iguales, evita asignar 0.9 a todo
    if ($q1 === $q2 && $q2 === $q3) {
        $priority = $c > 0 ? 0.9 : 0.6;
    } else {
        if ($c >= $q3) {
            $priority = 0.9;
        } elseif ($c >= $q2) {
            $priority = 0.8;
        } elseif ($c >= $q1) {
            $priority = 0.7;
        } else {
            $priority = 0.6;
        }
    }

    // Ahora sí: usar $priority para definir $freq
    if ($priority == 0.9) {
        $freq = 'daily';
    } elseif ($priority == 0.8) {
        $freq = 'weekly';
    } elseif ($priority == 0.7) {
        $freq = 'weekly';
    } else {
        $freq = 'monthly';
    }
@endphp
<changefreq>{{ $freq }}</changefreq>
<priority>{{ $priority }}</priority>

    </url>
    @endforeach
    
    @if($productos->hasMorePages())
        @for($page = 2; $page <= $productos->lastPage(); $page++)
    <url>
        <loc>https://komparador.com/sitemap-productos.xml?page={{ $page }}</loc>
        <lastmod>{{ now()->utc()->format('Y-m-d\TH:i:s\Z') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.5</priority>
    </url>
        @endfor
    @endif
</urlset>
