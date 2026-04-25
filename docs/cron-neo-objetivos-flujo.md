# Diagrama: Cron Neo Objetivos (flujo completo)

## Entrada

```
GET /admin/cron-neo-objetivos?token=XXX
```

## Filtro inicial (neoobjetivo)

- `visitada` &lt; hace 7 días  
- `url` no null  

Se dividen en dos ramas según la URL:

---

## Rama 1: URL contiene "idealo"

```
neoobjetivo (idealo)
    → POST VPS /sacar-ofertas-idea con url
    → Respuesta: html_b64
    → Extraer hrefs de ofertas (productOffers-listItemOfferCtaLeadout)
    → Limpiar URLs idealo/relocate
    → Por cada URL: procesarUrlRedireccion (neo, /redireccion, guardar en neo/oferta)
    → Actualizar neoobjetivo.visitada = now()
    → Guardar en ejecucion_global (log)
```

*(Ya implementado.)*

---

## Rama 2: URL no contiene "idealo" (categorías de tiendas)

```
neoobjetivo (no idealo)
    → Detectar tienda desde URL (misma lógica que crear-masivo)
    → Si no hay tienda:
          → Crear aviso interno (si no existe ya para este neoobjetivo)
          → Saltar al siguiente neoobjetivo
    → Cargar Tienda (tienda_id del neoobjetivo o detectada)
    → Usar tienda->api para peticiones (mismo mecanismo que scraper: PeticionApiHTMLController)
```

Según **tipo de listado** de la tienda (`tipoListadoCategoria()`):

### 2a. Sitemap

```
    → Una petición: obtener contenido del sitemap (URL = neoobjetivo.url o derivada)
    → Método tienda: urlsProductosDesdeSitemap(contenido) → string[] URLs
    → (Sin más páginas)
```

### 2b. Paginación

```
    → Petición 1: obtenerHTML(url_categoria) con tienda->api (VPS obtener-html)
    → Método tienda: extraerProductosYSiguientePagina(html, urlPeticionActual)
          → { urls_productos: string[], siguiente_url: ?string }
    → Si siguiente_url es null o 404 → fin
    → Si siguiente_url puede ser construida (?page=2) si no viene en HTML, el método recibe urlPeticionActual para poder generarla
    → Petición 2: obtenerHTML(siguiente_url) → repetir hasta no haber más
```

### 2c. Mostrar más

```
    → Indicar al VPS: selector del botón "ver más" y URL de categoría
    → VPS hace N clics y devuelve HTML completo
    → Método tienda: urlsProductosDesdeHtmlMostrarMas(html) → string[] URLs
```

---

## Después de tener todas las URLs de productos (rama 2)

- Por cada URL: lógica similar a idealo (comprobar neo, redirección, guardar).
- Al **guardar nuevo neoobjetivo** (desde categoría): no guardar `producto_id`, `oferta_id`, `neo`; sí guardar `tienda_id`, `categoria_id`, `url`, `aniadida`.
- Avisos internos si falla algo (sin duplicar por neoobjetivo).
- Guardar en `ejecucion_global` para poder consultar en la vista de resultados.

---

## Resumen tipos de listado (tienda)

| Tipo         | Peticiones      | Método(s) tienda |
|-------------|-----------------|-------------------|
| **sitemap** | 1 (sitemap)     | `urlsProductosDesdeSitemap(contenido)` |
| **paginacion** | N (una por página) | `extraerProductosYSiguientePagina(html, urlPeticionActual)` → urls + siguiente_url |
| **mostrar_mas** | 1 (VPS clica "ver más") | `urlsProductosDesdeHtmlMostrarMas(html)` |
