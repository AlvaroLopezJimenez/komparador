<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImagenController extends Controller
{
    /**
     * Subir una imagen a la carpeta especificada
     */
    public function subir(Request $request)
    {
        try {
            $request->validate([
                'imagen' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB máximo
                'carpeta' => 'required|string'
            ]);

            $imagen = $request->file('imagen');
            $carpeta = $request->input('carpeta');

            if (!$imagen || !$imagen->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo temporal no es válido o no se recibió correctamente'
                ], 400);
            }

            // Verificar que la extensión GD esté disponible
            if (!function_exists('imagecreatefromstring')) {
                \Log::error('GD extension not available');
                return response()->json([
                    'success' => false,
                    'message' => 'La extensión GD de PHP no está disponible.'
                ], 500);
            }

            // Verificar soporte para WebP
            if (!function_exists('imagewebp')) {
                \Log::error('GD WebP support not available');
                return response()->json([
                    'success' => false,
                    'message' => 'La extensión GD no soporta WebP.'
                ], 500);
            }

            $extension = $imagen->getClientOriginalExtension();
            $nombreBase = Str::slug(pathinfo($imagen->getClientOriginalName(), PATHINFO_FILENAME)) . '_' . time();
            $nombreArchivo = $nombreBase . '.webp';

            // Leer la imagen
            $imagenPath = $imagen->getRealPath();
            $imagenData = file_get_contents($imagenPath);
            
            if ($imagenData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo leer el archivo de imagen'
                ], 400);
            }

            $sourceImage = @imagecreatefromstring($imagenData);
            if (!$sourceImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar la imagen. Verifica que sea un formato válido.'
                ], 400);
            }

            // Obtener dimensiones de la imagen
            $anchoOriginal = imagesx($sourceImage);
            $altoOriginal = imagesy($sourceImage);

            if ($anchoOriginal === false || $altoOriginal === false) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener las dimensiones de la imagen'
                ], 500);
            }

            // Asegurar que el directorio existe
            if (!Storage::disk('web_images')->exists($carpeta)) {
                Storage::disk('web_images')->makeDirectory($carpeta);
            }

            // Guardar imagen original en WebP
            $rutaRelativa = $carpeta . '/' . $nombreArchivo;
            $rutaCompleta = Storage::disk('web_images')->path($rutaRelativa);
            $directorio = dirname($rutaCompleta);
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            // Redimensionar a tamaño razonable si es muy grande (máximo 300x250)
            $anchoMaximo = 300;
            $altoMaximo = 250;
            $ratioOriginal = $anchoOriginal / $altoOriginal;
            
            if ($anchoOriginal > $anchoMaximo || $altoOriginal > $altoMaximo) {
                if ($ratioOriginal > ($anchoMaximo / $altoMaximo)) {
                    $anchoFinal = $anchoMaximo;
                    $altoFinal = (int)($anchoMaximo / $ratioOriginal);
                } else {
                    $altoFinal = $altoMaximo;
                    $anchoFinal = (int)($altoMaximo * $ratioOriginal);
                }
            } else {
                $anchoFinal = $anchoOriginal;
                $altoFinal = $altoOriginal;
            }

            $imagenFinal = @imagecreatetruecolor($anchoFinal, $altoFinal);
            if (!$imagenFinal) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la imagen redimensionada'
                ], 500);
            }

            imagealphablending($imagenFinal, false);
            imagesavealpha($imagenFinal, true);
            imagecopyresampled($imagenFinal, $sourceImage, 0, 0, 0, 0, $anchoFinal, $altoFinal, $anchoOriginal, $altoOriginal);

            if (!@imagewebp($imagenFinal, $rutaCompleta, 90)) {
                imagedestroy($imagenFinal);
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la imagen'
                ], 500);
            }
            imagedestroy($imagenFinal);

            // Crear thumbnail (144x120)
            $nombreThumbnail = $nombreBase . '-thumbnail.webp';
            $rutaThumbnail = $carpeta . '/' . $nombreThumbnail;
            $rutaCompletaThumbnail = Storage::disk('web_images')->path($rutaThumbnail);

            $anchoThumbnail = 144;
            $altoThumbnail = 120;
            
            if ($ratioOriginal > ($anchoThumbnail / $altoThumbnail)) {
                $altoThumbnail = (int)($anchoThumbnail / $ratioOriginal);
            } else {
                $anchoThumbnail = (int)($altoThumbnail * $ratioOriginal);
            }

            $imagenThumbnail = @imagecreatetruecolor($anchoThumbnail, $altoThumbnail);
            if (!$imagenThumbnail) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear el thumbnail'
                ], 500);
            }

            imagealphablending($imagenThumbnail, false);
            imagesavealpha($imagenThumbnail, true);
            imagecopyresampled($imagenThumbnail, $sourceImage, 0, 0, 0, 0, $anchoThumbnail, $altoThumbnail, $anchoOriginal, $altoOriginal);

            if (!@imagewebp($imagenThumbnail, $rutaCompletaThumbnail, 90)) {
                imagedestroy($imagenThumbnail);
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar el thumbnail'
                ], 500);
            }
            imagedestroy($imagenThumbnail);
            imagedestroy($sourceImage);

            $url = Storage::disk('web_images')->url($rutaRelativa);

            return response()->json([
                'success' => true,
                'data' => [
                    'nombre' => $nombreArchivo,
                    'ruta' => $rutaRelativa,
                    'ruta_relativa' => $rutaRelativa,
                    'url' => $url,
                    'tamaño' => filesize($rutaCompleta),
                    'tipo' => 'image/webp'
                ],
                'message' => 'Imagen subida correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en subir: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar imágenes de una carpeta específica
     */
    public function listar(Request $request)
    {
        try {
            $request->validate([
                'carpeta' => 'required|string'
            ]);

            $carpeta = $request->input('carpeta');
            $archivos = Storage::disk('web_images')->files($carpeta);
            $imagenes = [];

            foreach ($archivos as $rutaRelativa) {
                $nombre = basename($rutaRelativa);
                $url = Storage::disk('web_images')->url($rutaRelativa);
                $tamaño = Storage::disk('web_images')->size($rutaRelativa);
                $fechaModificacion = Storage::disk('web_images')->lastModified($rutaRelativa);

                $extension = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $imagenes[] = [
                        'nombre' => $nombre,
                        'ruta' => $rutaRelativa,
                        'url' => $url,
                        'tamaño' => $tamaño,
                        'fecha_modificacion' => $fechaModificacion
                    ];
                }
            }

            // Ordenar por fecha de modificación (más recientes primero)
            usort($imagenes, function($a, $b) {
                return $b['fecha_modificacion'] - $a['fecha_modificacion'];
            });

            return response()->json([
                'success' => true,
                'data' => $imagenes,
                'message' => 'Imágenes listadas correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al listar las imágenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una imagen
     */
    public function eliminar(Request $request)
    {
        try {
            $request->validate([
                'ruta' => 'required|string'
            ]);

            $ruta = $request->input('ruta');

            if (!Storage::disk('web_images')->exists($ruta)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La imagen no existe'
                ], 404);
            }

            Storage::disk('web_images')->delete($ruta);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener las carpetas de imágenes disponibles
     */
    public function carpetasDisponibles()
    {
        try {
            $directories = Storage::disk('web_images')->directories('');
            $carpetas = array_map('basename', $directories);

            // Si no hay carpetas, devolver las carpetas por defecto
            if (empty($carpetas)) {
                $carpetas = ['panales', 'categorias', 'tiendas'];
            }

            return response()->json([
                'success' => true,
                'data' => $carpetas,
                'message' => 'Carpetas encontradas correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en carpetasDisponibles: ' . $e->getMessage());
            // Devolver carpetas por defecto en caso de error
            return response()->json([
                'success' => true,
                'data' => ['panales', 'categorias', 'tiendas'],
                'message' => 'Carpetas por defecto'
            ]);
        }
    }

    /**
     * Servir imagen como proxy (para evitar problemas CORS)
     */
    public function servirImagenProxy(Request $request)
    {
        try {
            $request->validate([
                'url' => 'required|string'
            ]);

            $url = trim($request->input('url'));

            // Validar que sea una URL válida
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                abort(400, 'URL no válida');
            }

            // Descargar la imagen
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept: image/*'
                    ],
                    'timeout' => 30,
                    'follow_location' => true,
                    'max_redirects' => 5
                ]
            ]);

            $imageData = @file_get_contents($url, false, $context);

            if ($imageData === false) {
                abort(404, 'No se pudo descargar la imagen');
            }

            // Obtener el tipo MIME de la imagen
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                abort(400, 'No es una imagen válida');
            }

            $mimeType = $imageInfo['mime'];

            // Devolver la imagen con los headers correctos
            return response($imageData, 200)
                ->header('Content-Type', $mimeType)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Cache-Control', 'no-cache');
        } catch (\Exception $e) {
            abort(500, 'Error al servir la imagen: ' . $e->getMessage());
        }
    }

    /**
     * Descargar imagen desde URL y guardarla temporalmente (DEPRECADO - usar servirImagenProxy)
     */
    public function descargarDesdeUrl(Request $request)
    {
        try {
            $request->validate([
                'url' => 'required|string',
                'titulo' => 'required|string|min:1'
            ]);

            $url = trim($request->input('url'));
            $titulo = $request->input('titulo');

            // Validar que sea una URL válida manualmente
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La URL proporcionada no es válida'
                ], 400);
            }

            // Descargar la imagen
            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => [
                        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                        'Accept: image/*'
                    ],
                    'timeout' => 30,
                    'follow_location' => true,
                    'max_redirects' => 5
                ]
            ]);

            $imageData = @file_get_contents($url, false, $context);

            if ($imageData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo descargar la imagen desde la URL proporcionada'
                ], 400);
            }

            // Validar que sea una imagen válida
            $imageInfo = @getimagesizefromstring($imageData);
            if ($imageInfo === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo descargado no es una imagen válida'
                ], 400);
            }

            // Guardar temporalmente
            $tempFileName = 'temp_' . Str::slug($titulo) . '_' . time() . '.tmp';
            $tempPath = storage_path('app/temp/' . $tempFileName);
            
            // Crear directorio temporal si no existe
            if (!file_exists(storage_path('app/temp'))) {
                mkdir(storage_path('app/temp'), 0755, true);
            }

            file_put_contents($tempPath, $imageData);

            // Crear URL temporal accesible
            $urlTemporal = asset('storage/temp/' . $tempFileName);
            
            // También guardar en storage público temporal
            $publicTempPath = public_path('storage/temp');
            if (!file_exists($publicTempPath)) {
                mkdir($publicTempPath, 0755, true);
            }
            file_put_contents($publicTempPath . '/' . $tempFileName, $imageData);

            return response()->json([
                'success' => true,
                'data' => [
                    'url_temporal' => asset('storage/temp/' . $tempFileName),
                    'archivo_temporal' => $tempFileName,
                    'ancho' => $imageInfo[0],
                    'alto' => $imageInfo[1],
                    'tipo' => $imageInfo['mime']
                ],
                'message' => 'Imagen descargada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir imagen simple (sin procesamiento, ya viene procesada desde el frontend)
     */
    public function subirSimple(Request $request)
    {
        try {
            $request->validate([
                'imagen' => 'required|image|mimes:webp|max:5120',
                'carpeta' => 'required|string'
            ]);

            $imagen = $request->file('imagen');
            $carpeta = $request->input('carpeta');

            if (!$imagen || !$imagen->isValid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo temporal no es válido o no se recibió correctamente'
                ], 400);
            }

            // Verificar que la extensión GD esté disponible
            if (!function_exists('imagecreatefromstring')) {
                \Log::error('GD extension not available');
                return response()->json([
                    'success' => false,
                    'message' => 'La extensión GD de PHP no está disponible.'
                ], 500);
            }

            // Verificar soporte para WebP
            if (!function_exists('imagewebp')) {
                \Log::error('GD WebP support not available');
                return response()->json([
                    'success' => false,
                    'message' => 'La extensión GD no soporta WebP.'
                ], 500);
            }

            // Obtener el nombre del archivo del request
            $nombreArchivo = $imagen->getClientOriginalName();
            
            // Si el nombre viene vacío, generar uno
            if (empty($nombreArchivo) || $nombreArchivo === 'blob') {
                $nombreArchivo = 'imagen_' . time() . '.webp';
            }

            // Extraer nombre base (sin extensión)
            $nombreBase = pathinfo($nombreArchivo, PATHINFO_FILENAME);
            // Asegurar que termina en .webp
            if (!str_ends_with($nombreArchivo, '.webp')) {
                $nombreArchivo = $nombreBase . '.webp';
            }

            // Leer la imagen
            $imagenPath = $imagen->getRealPath();
            $imagenData = file_get_contents($imagenPath);
            
            if ($imagenData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo leer el archivo de imagen'
                ], 400);
            }

            $sourceImage = @imagecreatefromstring($imagenData);
            if (!$sourceImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar la imagen. Verifica que sea un formato válido.'
                ], 400);
            }

            // Obtener dimensiones de la imagen
            $anchoOriginal = imagesx($sourceImage);
            $altoOriginal = imagesy($sourceImage);

            if ($anchoOriginal === false || $altoOriginal === false) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener las dimensiones de la imagen'
                ], 500);
            }

            // Asegurar que el directorio existe
            if (!Storage::disk('web_images')->exists($carpeta)) {
                Storage::disk('web_images')->makeDirectory($carpeta);
            }

            // Guardar imagen original
            $rutaRelativa = $carpeta . '/' . $nombreArchivo;
            $rutaCompleta = Storage::disk('web_images')->path($rutaRelativa);
            $directorio = dirname($rutaCompleta);
            if (!is_dir($directorio)) {
                mkdir($directorio, 0755, true);
            }

            if (!@imagewebp($sourceImage, $rutaCompleta, 90)) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la imagen'
                ], 500);
            }

            // Liberar memoria de la imagen
            imagedestroy($sourceImage);

            $url = Storage::disk('web_images')->url($rutaRelativa);

            return response()->json([
                'success' => true,
                'data' => [
                    'nombre' => $nombreArchivo,
                    'ruta' => $rutaRelativa,
                    'ruta_relativa' => $rutaRelativa,
                    'url' => $url,
                    'tamaño' => filesize($rutaCompleta),
                    'tipo' => 'image/webp'
                ],
                'message' => 'Imagen subida correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en subirSimple: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Procesar imagen recortada y guardarla en los tamaños especificados (DEPRECADO - usar subirSimple)
     */
    public function procesarRecorte(Request $request)
    {
        try {
            $request->validate([
                'imagen' => 'required|image',
                'carpeta' => 'required|string',
                'titulo' => 'required|string',
                'tipo' => 'required|in:grande,pequena,ambas'
            ]);

            $imagen = $request->file('imagen');
            $carpeta = $request->input('carpeta');
            $titulo = $request->input('titulo');
            $tipo = $request->input('tipo');

            // Verificar que la extensión GD esté disponible
            if (!function_exists('imagecreatefromstring')) {
                \Log::error('GD extension not available');
                return response()->json([
                    'success' => false,
                    'message' => 'La extensión GD de PHP no está disponible. Por favor, instala la extensión GD de PHP en el servidor.'
                ], 500);
            }

            // Verificar soporte para WebP
            if (!function_exists('imagewebp')) {
                \Log::error('GD WebP support not available');
                return response()->json([
                    'success' => false,
                    'message' => 'La extensión GD no soporta WebP. Por favor, instala la extensión GD con soporte WebP en el servidor.'
                ], 500);
            }

            // Crear nombre base del archivo desde el título
            $nombreBase = Str::slug($titulo);

            // Leer la imagen desde el archivo (ya viene recortada y redimensionada desde el frontend)
            $imagenPath = $imagen->getRealPath();
            if (!file_exists($imagenPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo temporal no existe'
                ], 400);
            }

            $imagenData = file_get_contents($imagenPath);
            if ($imagenData === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo leer el archivo de imagen'
                ], 400);
            }

            $sourceImage = @imagecreatefromstring($imagenData);
            if (!$sourceImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo procesar la imagen. Verifica que sea un formato válido.'
                ], 400);
            }

            // Obtener dimensiones de la imagen recortada
            $anchoOriginal = imagesx($sourceImage);
            $altoOriginal = imagesy($sourceImage);

            if ($anchoOriginal === false || $altoOriginal === false) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudieron obtener las dimensiones de la imagen'
                ], 400);
            }

            // Asegurar que el directorio existe
            if (!Storage::disk('web_images')->exists($carpeta)) {
                Storage::disk('web_images')->makeDirectory($carpeta);
            }

            // Guardar imagen grande (300x250) - siempre redimensionar desde la imagen recortada
            $nombreGrande = $nombreBase . '.webp';
            $rutaGrande = $carpeta . '/' . $nombreGrande;
            
            // Redimensionar a 300x250 manteniendo aspecto
            $anchoGrande = 300;
            $altoGrande = 250;
            $ratioOriginal = $anchoOriginal / $altoOriginal;
            $ratioDestino = $anchoGrande / $altoGrande;

            if ($ratioOriginal > $ratioDestino) {
                $altoGrande = (int)($anchoGrande / $ratioOriginal);
            } else {
                $anchoGrande = (int)($altoGrande * $ratioOriginal);
            }

            $imagenGrande = @imagecreatetruecolor($anchoGrande, $altoGrande);
            if (!$imagenGrande) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la imagen grande'
                ], 500);
            }

            imagealphablending($imagenGrande, false);
            imagesavealpha($imagenGrande, true);
            imagecopyresampled($imagenGrande, $sourceImage, 0, 0, 0, 0, $anchoGrande, $altoGrande, $anchoOriginal, $altoOriginal);
            
            $rutaCompletaGrande = Storage::disk('web_images')->path($rutaGrande);
            $directorioGrande = dirname($rutaCompletaGrande);
            if (!is_dir($directorioGrande)) {
                mkdir($directorioGrande, 0755, true);
            }

            if (!@imagewebp($imagenGrande, $rutaCompletaGrande, 90)) {
                imagedestroy($imagenGrande);
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la imagen grande'
                ], 500);
            }
            imagedestroy($imagenGrande);

            // Guardar imagen pequeña (144x120) con sufijo -thumbnail
            $nombrePequena = $nombreBase . '-thumbnail.webp';
            $rutaPequena = $carpeta . '/' . $nombrePequena;
            
            // Redimensionar a 144x120 manteniendo aspecto
            $anchoPequena = 144;
            $altoPequena = 120;
            $ratioOriginal = $anchoOriginal / $altoOriginal;
            
            if ($ratioOriginal > ($anchoPequena / $altoPequena)) {
                $altoPequena = (int)($anchoPequena / $ratioOriginal);
            } else {
                $anchoPequena = (int)($altoPequena * $ratioOriginal);
            }

            $imagenPequena = @imagecreatetruecolor($anchoPequena, $altoPequena);
            if (!$imagenPequena) {
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear la imagen pequeña'
                ], 500);
            }

            imagealphablending($imagenPequena, false);
            imagesavealpha($imagenPequena, true);
            imagecopyresampled($imagenPequena, $sourceImage, 0, 0, 0, 0, $anchoPequena, $altoPequena, $anchoOriginal, $altoOriginal);
            
            $rutaCompletaPequena = Storage::disk('web_images')->path($rutaPequena);
            $directorioPequena = dirname($rutaCompletaPequena);
            if (!is_dir($directorioPequena)) {
                mkdir($directorioPequena, 0755, true);
            }

            if (!@imagewebp($imagenPequena, $rutaCompletaPequena, 90)) {
                imagedestroy($imagenPequena);
                imagedestroy($sourceImage);
                return response()->json([
                    'success' => false,
                    'message' => 'Error al guardar la imagen pequeña'
                ], 500);
            }
            imagedestroy($imagenPequena);
            imagedestroy($sourceImage);

            // Verificar que los archivos se guardaron correctamente
            if (!Storage::disk('web_images')->exists($rutaGrande) || !Storage::disk('web_images')->exists($rutaPequena)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: Los archivos no se guardaron correctamente'
                ], 500);
            }

            // Siempre devolver ambas rutas (siempre se generan ambas versiones)
            return response()->json([
                'success' => true,
                'data' => [
                    'ruta_grande' => $rutaGrande,
                    'ruta_pequena' => $rutaPequena,
                    'url_grande' => Storage::disk('web_images')->url($rutaGrande),
                    'url_pequena' => Storage::disk('web_images')->url($rutaPequena)
                ],
                'message' => 'Imágenes procesadas correctamente'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error en procesarRecorte: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la imagen: ' . $e->getMessage() . ' en línea ' . $e->getLine()
            ], 500);
        }
    }
}