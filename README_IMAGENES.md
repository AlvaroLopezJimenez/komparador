# Sistema de Gestión de Imágenes - Configuración para Hosting Compartido

## Descripción
Este sistema permite gestionar imágenes de productos con dos métodos:
1. **Subida de imágenes**: Subir nuevas imágenes a carpetas específicas
2. **Selección manual**: Usar nombres de archivo existentes

## Características
- ✅ Compatible con hosting compartido
- ✅ Validación de tipos de archivo (jpg, png, gif, webp)
- ✅ Límite de tamaño (5MB máximo)
- ✅ Vista previa de imágenes
- ✅ Drag & drop para subida
- ✅ Organización por carpetas (panales, categorias, tiendas)
- ✅ Modal para ver imágenes existentes
- ✅ Fallback a sistema manual si hay problemas
- ✅ **NUEVO**: Validación inteligente (solo requiere una forma de imagen)
- ✅ **NUEVO**: Sincronización bidireccional entre pestañas
- ✅ **NUEVO**: Vista previa integrada en indicadores verdes
- ✅ **NUEVO**: Botón "Limpiar" para resetear campos
- ✅ **NUEVO**: Auto-selección de carpetas para imágenes existentes
- ✅ **NUEVO**: Detección automática de carpetas en diferentes ubicaciones de hosting
- ✅ **NUEVO**: Carga dinámica de carpetas disponibles en el formulario

## Configuración del Hosting

### 1. Estructura de Carpetas en Hosting Compartido
El sistema está diseñado para funcionar con diferentes configuraciones de hosting:

**Configuración típica:**
```
/home/usuario/
├── laravel/          # Código de Laravel
└── public_html/      # Directorio público del servidor web
    └── images/       # Carpeta de imágenes (accesible desde web)
        ├── panales/
        ├── categorias/
        └── tiendas/
```

**Configuración alternativa:**
```
/home/usuario/
├── laravel/          # Código de Laravel
└── www/             # Directorio público del servidor web
    └── images/      # Carpeta de imágenes
```

### 2. Permisos de Carpetas
Asegúrate de que las carpetas de imágenes tengan permisos de escritura (755 o 775):
```bash
# Para la carpeta principal de imágenes
chmod 755 public_html/images/
chmod 755 www/images/

# Para las subcarpetas (se crean automáticamente)
chmod 755 public_html/images/panales/
chmod 755 public_html/images/categorias/
chmod 755 public_html/images/tiendas/
```

### 3. Configuración del Controlador
El sistema incluye validación inteligente en `ProductoController.php`:

```php
// Validación flexible para imágenes
'imagen_grande' => 'nullable|string',
'imagen_pequena' => 'nullable|string',
'imagen_grande_manual' => 'nullable|string',
'imagen_pequena_manual' => 'nullable|string',

// Validación personalizada
if (empty($validated['imagen_grande']) && empty($validated['imagen_grande_manual'])) {
    throw ValidationException::withMessages([
        'imagen_grande' => 'Debe proporcionar una imagen grande, ya sea subiendo un archivo o especificando la ruta manualmente.'
    ]);
}
```

### 4. Límites del Servidor
Verifica que tu hosting permita:
- **Tamaño máximo de archivo**: 5MB o más
- **Tipos MIME**: image/jpeg, image/png, image/gif, image/webp
- **Tiempo de ejecución**: 30 segundos o más para subidas

### 5. Configuración de PHP
Añade o modifica en tu `php.ini` o `.htaccess`:
```apache
# .htaccess
php_value upload_max_filesize 10M
php_value post_max_size 10M
php_value max_execution_time 60
php_value memory_limit 256M
```

### 6. Verificación de Funcionamiento
El sistema incluye un middleware que:
- Crea automáticamente las carpetas si no existen
- Verifica permisos de escritura
- Registra errores en los logs

## Uso del Sistema

### Pestaña "Subir imagen"
1. Selecciona la carpeta destino
2. Haz clic en "Seleccionar archivo" o arrastra la imagen
3. La imagen se sube automáticamente al servidor
4. Se genera la ruta: `carpeta/nombre_archivo.extension`

### Pestaña "Nombre manual"
1. Escribe manualmente la ruta de la imagen
2. Usa el formato: `carpeta/nombre_archivo.extension`
3. Haz clic en "Buscar imagen" para vista previa

### Botón "Ver"
- Muestra todas las imágenes existentes en la carpeta seleccionada
- Permite seleccionar una imagen existente
- Útil para reutilizar imágenes ya subidas

## Nuevas Funcionalidades (v2.0)

### Validación Inteligente
- **Antes**: Requería rellenar ambos campos (upload + manual)
- **Ahora**: Solo requiere una de las dos formas de imagen
- **Beneficio**: Mayor flexibilidad y menos errores de validación

### Sincronización Bidireccional
- Los campos se sincronizan automáticamente entre pestañas
- Si escribes en "Nombre manual", se actualiza el campo oculto de upload
- Si subes una imagen, se actualiza el campo manual
- **Beneficio**: No hay duplicación de datos

### Vista Previa Integrada
- Las imágenes existentes se muestran directamente en el indicador verde
- Vista previa compacta (12x12 píxeles) junto al botón "Limpiar"
- **Beneficio**: Mejor organización visual y menos espacio ocupado

### Botón "Limpiar"
- Permite resetear rápidamente los campos de imagen
- Limpia tanto la vista previa como los campos ocultos
- **Beneficio**: Facilita la corrección de errores

### Auto-selección de Carpetas
- Al editar un producto existente, se selecciona automáticamente la carpeta correcta
- **Beneficio**: Mejor experiencia de usuario al editar productos

### Detección Automática de Carpetas
- **Búsqueda inteligente**: El sistema busca carpetas en múltiples ubicaciones posibles:
  - `public/images/` (Laravel estándar)
  - `../public_html/images/` (Hosting compartido típico)
  - `../www/images/` (Hosting compartido alternativo)
  - `../htdocs/images/` (Hosting compartido alternativo)
  - `../public/images/` (Configuración personalizada)
  - `$_SERVER['DOCUMENT_ROOT']/images/` (Ubicación del servidor web)
- **Creación automática**: Si no existe la carpeta, intenta crearla en la primera ubicación disponible
- **Carga dinámica**: Las carpetas disponibles se cargan automáticamente en el formulario
- **Beneficio**: Compatible con cualquier configuración de hosting compartido

## Solución de Problemas

### Error "No se pudo crear la carpeta"
```bash
# Verificar permisos del directorio padre
chmod 755 public/images/
chmod 755 public/images/panales/
chmod 755 public/images/categorias/
chmod 755 public/images/tiendas/
```

### Error "Permisos de escritura"
```bash
# Cambiar propietario (reemplaza 'usuario' con tu usuario del hosting)
chown usuario:usuario public/images/panales/
chown usuario:usuario public/images/categorias/
chown usuario:usuario public/images/tiendas/
```

### Error "Tamaño de archivo excede el límite"
1. Verifica `upload_max_filesize` en php.ini
2. Verifica `post_max_size` en php.ini
3. Contacta a tu proveedor de hosting

### Error "Tipo de archivo no permitido"
1. Verifica que la imagen sea jpg, png, gif o webp
2. Verifica que la extensión coincida con el tipo MIME
3. Revisa la configuración de `mime.types`

## Logs y Debugging

### Habilitar logs detallados
En `.env`:
```env
APP_DEBUG=true
LOG_LEVEL=debug
```

### Verificar logs
```bash
tail -f storage/logs/laravel.log
```

### Información del servidor
```php
// Añade temporalmente en una vista para debug
<?php phpinfo(); ?>
```

## Seguridad

### Validaciones implementadas
- ✅ Tipos de archivo permitidos
- ✅ Tamaño máximo de archivo
- ✅ Verificación de carpeta destino
- ✅ Sanitización de nombres de archivo
- ✅ CSRF protection
- ✅ Autenticación requerida

### Recomendaciones adicionales
- Configura un firewall en el servidor
- Usa HTTPS para todas las comunicaciones
- Monitorea los logs regularmente
- Limpia archivos no utilizados periódicamente

## Rendimiento

### Optimizaciones recomendadas
1. **Compresión de imágenes**: Usa formatos WebP cuando sea posible
2. **Redimensionamiento**: Considera crear múltiples tamaños
3. **CDN**: Usa un CDN para servir imágenes estáticas
4. **Cache**: Implementa cache de imágenes

### Monitoreo
- Verifica el uso de disco regularmente
- Monitorea el tiempo de respuesta de subidas
- Revisa el tamaño de los logs

## Soporte

### Comandos útiles
```bash
# Verificar permisos
ls -la public/images/

# Verificar espacio en disco
df -h

# Verificar logs de error
grep -i "error" storage/logs/laravel.log

# Limpiar archivos temporales
php artisan cache:clear
php artisan config:clear
```

### Contacto
Si encuentras problemas específicos:
1. Revisa los logs de Laravel
2. Verifica la configuración del hosting
3. Consulta la documentación de tu proveedor
4. Contacta al soporte técnico del hosting

## Notas de Implementación

### Compatibilidad
- ✅ Laravel 8+
- ✅ PHP 7.4+
- ✅ Navegadores modernos (ES6+)
- ✅ Hosting compartido estándar

### Dependencias
- No requiere paquetes adicionales
- Usa JavaScript nativo para drag & drop
- Compatible con cualquier tema CSS

### Migración
El sistema es compatible con el sistema anterior:
- Los campos existentes siguen funcionando
- Las rutas de imágenes se mantienen
- No requiere cambios en la base de datos

## Casos de Uso Comunes

### Crear Producto Nuevo
1. **Solo imagen upload**: Sube archivo → ✅ Funciona
2. **Solo imagen manual**: Escribe ruta → ✅ Funciona  
3. **Ambas formas**: Se usa la imagen de upload por prioridad → ✅ Funciona
4. **Sin imagen**: ❌ Error claro y específico

### Editar Producto Existente
1. **Mantener imagen actual**: Se muestra en indicador verde → ✅ Funciona
2. **Cambiar por upload**: Sube nueva imagen → ✅ Funciona
3. **Cambiar por manual**: Escribe nueva ruta → ✅ Funciona
4. **Limpiar imagen**: Usa botón "Limpiar" → ✅ Funciona

### Solución de Errores de Validación
- **Error**: "Debe proporcionar una imagen grande..."
- **Solución**: Rellena al menos una de las dos formas de imagen
- **Prevención**: Los campos se sincronizan automáticamente
