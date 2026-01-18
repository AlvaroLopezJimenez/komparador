# Verificación de URLs de Ofertas

## Descripción
Esta funcionalidad permite verificar si una lista de URLs ya existen en la base de datos de ofertas, con soporte para normalización inteligente de URLs según la tienda.

## Características

### 1. Selección de Producto (Opcional)
- Puedes seleccionar un producto específico para buscar solo en sus ofertas
- Si no seleccionas ningún producto, se buscarán en todas las ofertas de la base de datos
- Búsqueda en tiempo real con autocompletado

### 2. Creación Automática de Ofertas
- **Botón individual**: Cada URL que no existe tiene un botón "Crear oferta" que abre el formulario con la URL pre-llenada
- **Botón masivo**: Si hay URLs nuevas, aparece un botón "Crear todas las ofertas (X)" que abre múltiples pestañas
- **Confirmación**: El botón masivo pide confirmación antes de abrir múltiples pestañas
- **Contador**: Muestra cuántas URLs nuevas hay disponibles para crear ofertas

### 3. Normalización Inteligente de URLs
La funcionalidad normaliza las URLs según la tienda para mejorar la detección de duplicados:

#### Amazon
- **URL original**: `https://www.amazon.es/dp/B0B5LM8XG9/?smid=A9HKY51G&ref=...`
- **URL normalizada**: `https://www.amazon.es/dp/B0B5LM8XG9?smid=A9HKY51G`
- Mantiene el parámetro `smid` pero elimina otros parámetros

#### Miravia
- **URL original**: `https://www.miravia.es/p/dodot-bebe-seco-paales-talla-3-6-10kg-62-unidades-caja-3-bolsas-total-186-paales-i1361106617267911.html?spm=...`
- **URL normalizada**: `https://www.miravia.es/p/i1361106617267911.html`
- Extrae solo el ID del producto

#### Otras Tiendas
- Elimina todos los parámetros de query (?param=value)
- Mantiene la estructura básica del path

### 3. Detección de Duplicados
- Comparación exacta de URLs normalizadas
- Comparación sin barra final (ej: `/producto` vs `/producto/`)
- URLs que ya existen aparecen en **rojo**
- URLs disponibles aparecen en **verde**

### 4. Información de Ofertas Existentes
Para URLs que ya existen, muestra:
- Producto asociado
- Tienda
- Precio total y por unidad
- Enlaces para:
  - Ver el producto en la web
  - Editar la oferta

### 5. Enlaces Automáticos
- Todas las URLs se convierten automáticamente en enlaces clickeables
- Se abren en pestañas nuevas

## Cómo Usar

1. **Acceder**: Ve al panel de administración → Scraping → Verificar URLs

2. **Seleccionar Producto** (opcional):
   - Escribe el nombre del producto
   - Selecciona de la lista de sugerencias

3. **Pegar URLs**:
   - Pega las URLs en el campo de texto
   - Una URL por línea

4. **Verificar**:
   - Haz clic en "Verificar URLs"
   - Los resultados aparecerán debajo

5. **Crear Ofertas** (para URLs nuevas):
   - **Individual**: Haz clic en "Crear oferta" para cada URL verde
   - **Masivo**: Usa "Crear todas las ofertas (X)" para abrir múltiples pestañas

## Ejemplos de URLs Soportadas

### Amazon
```
https://www.amazon.es/dp/B0B5LM8XG9/?smid=A9HKY51G
https://www.amazon.es/dp/B091FMY6SV/
https://www.amazon.es/dp/B091FMY6SV
```

### Miravia
```
https://www.miravia.es/p/dodot-bebe-seco-paales-talla-3-6-10kg-62-unidades-caja-3-bolsas-total-186-paales-i1361106617267911.html?spm=euspain.searchlist.list.ditem_8.7a3b4d1eskLEdp&clickTrace=...
```

### Otras Tiendas
```
https://www.ejemplo.com/producto/123?param=value&otro=param
https://www.ejemplo.com/producto/123/
https://www.ejemplo.com/producto/123
```

## Rutas del Sistema

- **Vista principal**: `/panel-privado/admin/scraping/verificar-urls`
- **API de verificación**: `/panel-privado/admin/scraping/verificar-urls/procesar`
- **Búsqueda de productos**: `/panel-privado/admin/ofertas/buscar-productos`

## Archivos Creados

- **Controlador**: `app/Http/Controllers/Scraping/VerificarUrlsController.php`
- **Vista**: `resources/views/admin/scraping/verificar-urls.blade.php`
- **Rutas**: Añadidas en `routes/web.php`

## Notas Técnicas

- La normalización de URLs se hace en el servidor para mayor seguridad
- Las comparaciones son case-sensitive
- Se manejan automáticamente las barras finales
- Soporte completo para URLs con y sin protocolo (http/https)

## Debug y Solución de Problemas

### Si no se abren múltiples pestañas:
1. Abre la consola del navegador (F12)
2. Ejecuta la verificación de URLs
3. Haz clic en "Crear todas las ofertas"
4. Revisa los logs en la consola para ver:
   - Cuántos elementos verdes se encontraron
   - Qué URLs se detectaron
   - Si las pestañas se abrieron correctamente

### Si la URL no aparece pre-llenada:
1. Verifica que el parámetro `url` se está pasando correctamente en la URL
2. Revisa que el controlador `createGeneral` esté recibiendo el parámetro
3. Confirma que la vista está usando la variable `$url`

### Logs esperados en consola:
```
Buscando URLs nuevas...
Elementos verdes encontrados: 2
Elemento 0: https://ejemplo.com/producto1
Elemento 1: https://ejemplo.com/producto2
URLs nuevas encontradas: ["https://ejemplo.com/producto1", "https://ejemplo.com/producto2"]
Abriendo pestaña 1: /panel-privado/ofertas/create?url=https%3A//ejemplo.com/producto1
Pestaña 1 abierta correctamente
Abriendo pestaña 2: /panel-privado/ofertas/create?url=https%3A//ejemplo.com/producto2
Pestaña 2 abierta correctamente
```
