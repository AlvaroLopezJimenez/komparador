{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($categorias as $categoria)
    <url>
        <loc>https://komparador.com/categoria/{{ $categoria->slug }}</loc>
        <lastmod>{{ $categoria->updated_at->utc()->format('Y-m-d\TH:i:s\Z') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    @endforeach
    
    @if($categorias->hasMorePages())
        @for($page = 2; $page <= $categorias->lastPage(); $page++)
    <url>
        <loc>https://komparador.com/sitemap-categorias.xml?page={{ $page }}</loc>
        <lastmod>{{ now()->utc()->format('Y-m-d\TH:i:s\Z') }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.5</priority>
    </url>
        @endfor
    @endif
</urlset>
