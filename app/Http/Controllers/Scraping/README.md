# Sistema de Scraping de Ofertas

## Descripción General

Este sistema permite hacer scraping de precios de ofertas de diferentes tiendas de forma automatizada. Está diseñado para ser modular y fácilmente extensible para nuevas tiendas.

## Estructura del Sistema

```
app/Http/Controllers/Scraping/
├── ScrapingController.php              # Punto de entrada principal
├── PeticionApiHTMLController.php       # Controlador global para obtener HTML
├── TestScrapingController.php          # Controlador para testing
├── Tiendas/
│   ├── PlantillaTiendaController.php   # Plantilla base para tiendas
│   ├── PrimorController.php            # Ejemplo de implementación
│   └── INSTRUCCIONES_TIENDAS.txt       # Instrucciones detalladas
└── README.md                           # Este archivo
```

## Flujo de Funcionamiento

1. **OfertaProductoController** hace una llamada al sistema de scraping
2. **ScrapingController** (punto de entrada) recibe la petición con URL, tienda y variante
3. **ScrapingController** normaliza el nombre de la tienda y busca el controlador correspondiente
4. **Controlador de la tienda específica** recibe la petición
5. **PeticionApiHTMLController** obtiene el HTML de la página usando la API de scraping
6. **Controlador de la tienda** extrae el precio del HTML usando selectores específicos
7. Se devuelve el precio al **OfertaProductoController**

## Normalización de Nombres de Tiendas

El sistema normaliza automáticamente los nombres de tiendas para buscar el controlador correspondiente:

- "EL Corte Inglés" → `ElcorteinglesController.php`
- "Primor" → `PrimorController.php`
- "Carrefour" → `CarrefourController.php`

## API de Scraping

El sistema usa la API de ScrapingAnt para obtener el HTML de las páginas:

- **URL**: `https://scrapingant.p.rapidapi.com/get`
- **API Key**: Configurada en `PeticionApiHTMLController.php`
- **Parámetros**: URL, proxy_country=ES, response_format=html

## Rutas Disponibles

### Scraping Principal
- `POST /scraping/obtener-precio` - Punto de entrada para scraping

### Testing (Solo Admin)
- `GET /admin/scraping/test` - Vista de testing
- `POST /admin/scraping/test/procesar` - Procesar URL de testing

## Cómo Añadir una Nueva Tienda

1. **Crear el controlador de la tienda**:
   ```bash
   cp app/Http/Controllers/Scraping/Tiendas/PlantillaTiendaController.php app/Http/Controllers/Scraping/Tiendas/[NombreTienda]Controller.php
   ```

2. **Modificar la clase**:
   - Cambiar el nombre de la clase a `[NombreTienda]Controller`
   - Implementar el método `obtenerPrecio()` con la lógica específica de la tienda

3. **Implementar la extracción de precios**:
   - Usar selectores CSS específicos de la tienda
   - Usar expresiones regulares para patrones de precio
   - Manejar diferentes formatos de precio

4. **Probar la implementación**:
   - Usar la vista de testing en `/admin/scraping/test`
   - Verificar que extrae correctamente los precios

## Ejemplo de Implementación

Ver `PrimorController.php` para un ejemplo completo de implementación.

## Testing del Sistema

### Vista de Testing
Accede a `/admin/scraping/test` desde el panel de administración para:

- Probar URLs de diferentes tiendas
- Ver el HTML obtenido
- Verificar que los selectores funcionan correctamente
- Copiar el HTML para análisis

### Desde el Dashboard
En el dashboard de admin, en el modal "Ejecuciones Scraper", hay un nuevo botón "🔍 Testing de Scraping" que lleva a la vista de testing.

## Configuración

### API Key
La API key de ScrapingAnt está configurada en `PeticionApiHTMLController.php`. Para cambiar la API key:

1. Editar `PeticionApiHTMLController.php`
2. Cambiar el valor de `$apiKey`

### Timeouts
Los timeouts están configurados en 30 segundos por defecto. Se pueden ajustar en `PeticionApiHTMLController.php`.

## Manejo de Errores

El sistema maneja varios tipos de errores:

- **Errores de API**: Problemas con la API de ScrapingAnt
- **Errores de extracción**: No se puede encontrar el precio en el HTML
- **Errores de validación**: Precios extraídos no válidos
- **Errores de controlador**: Controlador de tienda no encontrado

## Logs y Monitoreo

Los errores se registran en los logs de Laravel. Para monitorear:

1. Revisar `storage/logs/laravel.log`
2. Usar la vista de testing para verificar URLs problemáticas
3. Revisar las ejecuciones de scraping en el dashboard

## Optimizaciones

### Rate Limiting
El sistema incluye delays entre peticiones para evitar ser bloqueado:

- 10 segundos entre bloques de peticiones
- Round-robin entre tiendas para distribuir la carga

### Caching
Considerar implementar cache para URLs frecuentemente consultadas.

### Fallbacks
El sistema intenta múltiples patrones de extracción antes de fallar.

## Troubleshooting

### Problema: No se encuentra el controlador de la tienda
**Solución**: Verificar que el nombre del controlador coincide con la normalización del nombre de la tienda.

### Problema: No se extrae el precio
**Solución**: 
1. Usar la vista de testing para ver el HTML
2. Ajustar los selectores en el controlador de la tienda
3. Verificar que la estructura HTML no ha cambiado

### Problema: Error de API
**Solución**:
1. Verificar que la API key es válida
2. Comprobar el límite de peticiones de la API
3. Verificar la conectividad de red

## Próximas Mejoras

- [ ] Implementar cache de HTML
- [ ] Añadir más métodos de extracción (XPath avanzado)
- [ ] Implementar detección automática de cambios en estructura HTML
- [ ] Añadir métricas de rendimiento
- [ ] Implementar sistema de notificaciones para errores
