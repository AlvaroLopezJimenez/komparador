<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CorreoAvisoPrecio;
use App\Models\Producto;
use App\Models\Categoria;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AlertaPrecioController extends Controller
{
    /**
     * Guardar una nueva alerta de precio
     */
    public function guardarAlerta(Request $request)
    {
        // Validar los datos de entrada....
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'precio_limite' => 'required|numeric|min:0',
            'producto_id' => 'required|exists:productos,id',
            'especificaciones_internas_seleccionadas' => 'nullable|array',
            'acepto_politicas' => 'required|boolean'
        ], [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'precio_limite.required' => 'El precio límite es obligatorio.',
            'precio_limite.numeric' => 'El precio debe ser un número.',
            'precio_limite.min' => 'El precio debe ser mayor o igual a 0.',
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists' => 'El producto seleccionado no existe.',
            'acepto_politicas.required' => 'Debes aceptar las políticas de privacidad.',
            'acepto_politicas.boolean' => 'Debes aceptar las políticas de privacidad.'
        ]);

        // Verificar que acepto_politicas sea true
        if (!$request->boolean('acepto_politicas')) {
            return response()->json([
                'success' => false,
                'errors' => ['acepto_politicas' => ['Debes aceptar las políticas de privacidad.']]
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $seleccionNormalizada = $this->normalizarEspecificacionesInternasSeleccionadas(
                $request->input('especificaciones_internas_seleccionadas')
            );

            // Verificar si ya existe una alerta para este correo y producto
            $alertaExistente = CorreoAvisoPrecio::where('correo', $request->correo)
                ->where('producto_id', $request->producto_id)
                ->first();

            if ($alertaExistente) {
                // Actualizar la alerta existente
                $datosActualizacion = [
                    'precio_limite' => $request->precio_limite,
                    'especificaciones_internas_seleccionadas' => $seleccionNormalizada,
                    'confirmado' => 'no',
                    'ultimo_envio_correo' => null,
                    // Si veces_enviado es null, inicializarlo a 0
                    'veces_enviado' => 0
                ];
                
                // Generar token si no existe
                if (!$alertaExistente->token_cancelacion) {
                    $datosActualizacion['token_cancelacion'] = $this->generarTokenUnico();
                }
                
                $alertaExistente->update($datosActualizacion);
                $alerta = $alertaExistente->fresh();
            } else {
                // Crear nueva alerta
                $alerta = CorreoAvisoPrecio::create([
                    'correo' => $request->correo,
                    'precio_limite' => $request->precio_limite,
                    'producto_id' => $request->producto_id,
                    'especificaciones_internas_seleccionadas' => $seleccionNormalizada,
                    'token_cancelacion' => $this->generarTokenUnico(),
                    'confirmado' => 'no',
                    'veces_enviado' => 0
                ]);
            }

            $this->enviarCorreoConfirmacionAlerta($alerta);

            return response()->json([
                'success' => true,
                'message' => 'Te hemos enviado un correo de confirmación. Confirma tu aviso en menos de 1 hora para empezar a recibir alertas.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al guardar alerta de precio: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la alerta. Inténtalo de nuevo.'
            ], 500);
        }
    }

    /**
     * Guardar una nueva alerta de precio por categoría.
     */
    public function guardarAlertaCategoria(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'correo' => 'required|email',
            'precio_limite' => 'required|numeric|min:0',
            'categoria_id' => 'required|exists:categorias,id',
            'especificaciones_internas_seleccionadas' => 'nullable|array',
            'acepto_politicas' => 'required|boolean',
        ], [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email' => 'El correo electrónico debe tener un formato válido.',
            'precio_limite.required' => 'El precio límite es obligatorio.',
            'precio_limite.numeric' => 'El precio debe ser un número.',
            'precio_limite.min' => 'El precio debe ser mayor o igual a 0.',
            'categoria_id.required' => 'La categoría es obligatoria.',
            'categoria_id.exists' => 'La categoría seleccionada no existe.',
            'acepto_politicas.required' => 'Debes aceptar las políticas de privacidad.',
            'acepto_politicas.boolean' => 'Debes aceptar las políticas de privacidad.',
        ]);

        if (!$request->boolean('acepto_politicas')) {
            return response()->json([
                'success' => false,
                'errors' => ['acepto_politicas' => ['Debes aceptar las políticas de privacidad.']],
            ], 422);
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $seleccionNormalizadaBase = $this->normalizarEspecificacionesInternasSeleccionadas(
                $request->input('especificaciones_internas_seleccionadas')
            );
            $categoria = Categoria::query()->findOrFail((int) $request->categoria_id);
            $productoSoporte = $this->obtenerProductoSoporteCategoria($categoria);
            if (!$productoSoporte) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay productos válidos en esta categoría para crear la alerta.',
                ], 422);
            }
            $nombresSeleccionados = $this->obtenerNombresEspecificacionesSeleccionadasCategoria($categoria, $seleccionNormalizadaBase);
            $seleccionNormalizada = $this->inyectarMetaAlertaCategoria($seleccionNormalizadaBase, $categoria, $nombresSeleccionados);

            $alertaExistente = CorreoAvisoPrecio::query()
                ->where('correo', $request->correo)
                ->where('producto_id', $productoSoporte->id)
                ->first();

            if ($alertaExistente) {
                $datosActualizacion = [
                    'precio_limite' => $request->precio_limite,
                    'especificaciones_internas_seleccionadas' => $seleccionNormalizada,
                    'confirmado' => 'no',
                    'ultimo_envio_correo' => null,
                    'veces_enviado' => 0,
                ];
                if (!$alertaExistente->token_cancelacion) {
                    $datosActualizacion['token_cancelacion'] = $this->generarTokenUnico();
                }
                $alertaExistente->update($datosActualizacion);
                $alerta = $alertaExistente->fresh();
            } else {
                $alerta = CorreoAvisoPrecio::create([
                    'correo' => $request->correo,
                    'precio_limite' => $request->precio_limite,
                    'producto_id' => $productoSoporte->id,
                    'especificaciones_internas_seleccionadas' => $seleccionNormalizada,
                    'token_cancelacion' => $this->generarTokenUnico(),
                    'confirmado' => 'no',
                    'veces_enviado' => 0,
                ]);
            }

            $this->enviarCorreoConfirmacionAlerta($alerta);

            return response()->json([
                'success' => true,
                'message' => 'Te hemos enviado un correo de confirmación. Confirma tu aviso en menos de 1 hora para empezar a recibir alertas.',
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al guardar alerta de categoría: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la alerta de categoría. Inténtalo de nuevo.',
            ], 500);
        }
    }

    public function enviarAlertaIndividual(CorreoAvisoPrecio $alerta, float $precioActual): array
    {
        try {
            $producto = $alerta->producto;
            if (!$producto) {
                return [
                    'success' => false,
                    'message' => 'No se encontró el producto asociado a la alerta',
                ];
            }

            $urlProducto = $this->construirUrlProducto($producto);
            $vecesEnviadoActual = (int) ($alerta->veces_enviado ?? 0);
            $vecesEnviado = $vecesEnviadoActual + 1;
            $correoDestino = $alerta->correo;
            $precioLimiteSuscripcion = (float) $alerta->precio_limite;

            $this->enviarCorreoAlerta($alerta, $producto, $precioActual, $urlProducto, $vecesEnviado);

            $suscripcionEliminada = $vecesEnviado >= 4;
            if ($suscripcionEliminada) {
                $alerta->delete();
            } else {
                $alerta->update([
                    'ultimo_envio_correo' => now(),
                    'veces_enviado' => $vecesEnviado,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Correo enviado correctamente',
                'correo' => $correoDestino,
                'precio_actual' => round($precioActual, 4),
                'precio_limite' => round($precioLimiteSuscripcion, 2),
                'veces_enviado' => $vecesEnviado,
                'suscripcion_eliminada' => $suscripcionEliminada,
                'producto_nombre' => $producto->nombre ?? null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar correo: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar alerta individual para categoría (productos por debajo de un umbral en una categoría).
     *
     * @param  array<int, \App\Models\Producto>  $productosCoincidentes
     */
    public function enviarAlertaCategoriaIndividual(CorreoAvisoPrecio $alerta, float $precioActual, array $productosCoincidentes): array
    {
        try {
            $metaCategoria = $this->extraerMetaAlertaCategoria($alerta);
            $categoria = isset($metaCategoria['categoria_id'])
                ? Categoria::query()->find((int) $metaCategoria['categoria_id'])
                : null;
            if (!$categoria) {
                return [
                    'success' => false,
                    'message' => 'No se encontró la categoría asociada a la alerta',
                ];
            }

            $urlCategoria = $this->construirUrlCategoriaConFiltros(
                $categoria,
                is_array($alerta->especificaciones_internas_seleccionadas) ? $alerta->especificaciones_internas_seleccionadas : []
            );
            $nombresEspecificaciones = $this->obtenerNombresEspecificacionesSeleccionadasCategoria(
                $categoria,
                is_array($alerta->especificaciones_internas_seleccionadas) ? $alerta->especificaciones_internas_seleccionadas : []
            );
            $vecesEnviadoActual = (int) ($alerta->veces_enviado ?? 0);
            $vecesEnviado = $vecesEnviadoActual + 1;
            $correoDestino = $alerta->correo;
            $precioLimiteSuscripcion = (float) $alerta->precio_limite;
            $totalCoincidentes = count($productosCoincidentes);

            $this->enviarCorreoAlertaCategoria(
                $alerta,
                $categoria->nombre ?? 'Categoría',
                $precioActual,
                $urlCategoria,
                $vecesEnviado,
                $totalCoincidentes,
                $nombresEspecificaciones
            );

            $suscripcionEliminada = $vecesEnviado >= 4;
            if ($suscripcionEliminada) {
                $alerta->delete();
            } else {
                $alerta->update([
                    'ultimo_envio_correo' => now(),
                    'veces_enviado' => $vecesEnviado,
                ]);
            }

            return [
                'success' => true,
                'message' => 'Correo de categoría enviado correctamente',
                'correo' => $correoDestino,
                'precio_actual' => round($precioActual, 2),
                'precio_limite' => round($precioLimiteSuscripcion, 2),
                'veces_enviado' => $vecesEnviado,
                'suscripcion_eliminada' => $suscripcionEliminada,
                'categoria_nombre' => $categoria->nombre ?? null,
                'productos_coincidentes' => $totalCoincidentes,
                'url_categoria' => $urlCategoria,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar correo de categoría: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Enviar correo individual de alerta usando Brevo API
     */
    private function enviarCorreoAlerta($alerta, $producto, $precioActual, $urlProducto, $vecesEnviado = 1)
    {
        $brevoApiKey = env('BREVO_API_KEY');
        
        if (!$brevoApiKey) {
            throw new \Exception('BREVO_API_KEY no está configurada en el archivo .env');
        }

        $subject = "¡El Precio ha bajado! {$producto->nombre} ahora por {$precioActual} €";
        
        $urlCancelar = $this->generarUrlCancelarAlerta($alerta);
        $htmlContent = $this->generarContenidoHtml($producto, $precioActual, $urlProducto, $alerta->precio_limite, $urlCancelar, $vecesEnviado);
        $textContent = $this->generarContenidoTexto($producto, $precioActual, $urlProducto, $alerta->precio_limite, $urlCancelar, $vecesEnviado);

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => 'Komparador.com',
                'email' => 'info@komparador.com'
            ],
            'to' => [
                [
                    'email' => $alerta->correo,
                    'name' => 'Usuario'
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent
        ]);

        if (!$response->successful()) {
            throw new \Exception("Error enviando correo a {$alerta->correo}: [url] {$response->effectiveUri()} [http method] POST [status code] {$response->status()} [reason phrase] {$response->reason()}");
        }
    }

    private function enviarCorreoAlertaCategoria(
        CorreoAvisoPrecio $alerta,
        string $nombreCategoria,
        float $precioActual,
        string $urlCategoria,
        int $vecesEnviado = 1,
        int $totalCoincidentes = 0,
        array $nombresEspecificaciones = []
    ): void {
        $brevoApiKey = env('BREVO_API_KEY');
        if (!$brevoApiKey) {
            throw new \Exception('BREVO_API_KEY no está configurada en el archivo .env');
        }

        $subject = "Nuevos productos en {$nombreCategoria} por debajo de {$precioActual} €";
        $urlCancelar = $this->generarUrlCancelarAlerta($alerta);
        $htmlContent = $this->generarContenidoHtmlCategoria(
            $nombreCategoria,
            $precioActual,
            $urlCategoria,
            $alerta->precio_limite,
            $urlCancelar,
            $vecesEnviado,
            $totalCoincidentes,
            $nombresEspecificaciones
        );
        $textContent = $this->generarContenidoTextoCategoria(
            $nombreCategoria,
            $precioActual,
            $urlCategoria,
            $alerta->precio_limite,
            $urlCancelar,
            $vecesEnviado,
                $totalCoincidentes,
                $nombresEspecificaciones
        );

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => 'Komparador.com',
                'email' => 'info@komparador.com',
            ],
            'to' => [
                [
                    'email' => $alerta->correo,
                    'name' => 'Usuario',
                ],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent,
        ]);

        if (!$response->successful()) {
            throw new \Exception("Error enviando correo de categoría a {$alerta->correo}: [status code] {$response->status()}");
        }
    }

    /**
     * Generar contenido HTML del correo
     */
    private function generarContenidoHtml($producto, $precioActual, $urlProducto, $precioLimite, $urlCancelar, $vecesEnviado = 1)
    {
        // Obtener la unidad de medida
        $unidadMedida = $this->obtenerUnidadMedida($producto->unidadDeMedida);
        
        // Generar mensaje de veces avisado
        if ($vecesEnviado >= 4) {
            $mensajeVecesAvisado = "<p style=\"color: #666; margin: 10px 0 0 0; font-size: 14px; text-align: center;\">Has sido avisado 4 veces, tu aviso ha sido eliminado. Si quieres seguir recibiendo avisos tienes que volver a suscribirte en la web.</p>";
        } elseif ($vecesEnviado == 1) {
            $mensajeVecesAvisado = "<p style=\"color: #666; margin: 10px 0 0 0; font-size: 14px; text-align: center;\">Avisado 1 vez. Después del 4º aviso ya no recibirás más.</p>";
        } else {
            $mensajeVecesAvisado = "<p style=\"color: #666; margin: 10px 0 0 0; font-size: 14px; text-align: center;\">Avisado {$vecesEnviado} veces. Después del 4º aviso ya no recibirás más.</p>";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>¡El Precio ha bajado!</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
            <div style='background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #e91e63 0%, #ff6b9d 100%); padding: 25px 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px; font-weight: bold;'>¡El Precio ha bajado! 🎉</h1>
                    <p style='color: rgba(255, 255, 255, 0.9); margin: 8px 0 0 0; font-size: 14px;'>Tu alerta de precio se ha activado</p>
                </div>
                
                <!-- Contenido principal -->
                <div style='padding: 30px 20px; text-align: center;'>
                    <!-- Nombre del producto -->
                    <h2 style='color: #333; margin: 0 0 20px 0; font-size: 22px; line-height: 1.3;'>{$producto->nombre}</h2>
                    
                    <!-- Precio actual -->
                    <div style='margin-bottom: 25px;'>
                        <div style='font-size: 32px; font-weight: bold; color: #e91e63; margin-bottom: 8px;'>
                            <strong>{$precioActual} €</strong>
                            <span style='font-size: 18px; color: #666; font-weight: normal;'>{$unidadMedida}</span>
                        </div>
                        <div style='color: #666; font-size: 16px;'>
                            Tu precio límite era: <strong>{$precioLimite} € {$unidadMedida}</strong>
                        </div>
                    </div>
                    
                    <!-- Botón de acción -->
                    <a href='{$urlProducto}' style='display: inline-block; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 123, 255, 0.3); margin-bottom: 10px;'>
                        Ver ofertas disponibles
                    </a>
                    
                    <!-- Mensaje de veces avisado -->
                    {$mensajeVecesAvisado}
                </div>
                
                <!-- Footer -->
                <div style='background-color: #f8f9fa; padding: 20px; border-top: 1px solid #e9ecef;'>
                    <div style='text-align: center; margin-bottom: 15px;'>
                        <p style='color: #666; margin: 0; font-size: 14px;'>
                            Si ya no quieres recibir más alertas para este producto, puedes 
                            <a href='{$urlCancelar}' style='color: #e91e63; text-decoration: underline; font-weight: bold;'>cancelar tu alerta aquí</a>.
                        </p>
                    </div>
                    <div style='text-align: center;'>
                        <p style='color: #999; margin: 0; font-size: 12px;'>
                            Este correo es solo para notificaciones de precios. No será utilizado para publicidad.
                        </p>
                        <p style='color: #999; margin: 5px 0 0 0; font-size: 12px;'>
                            © " . date('Y') . " Komparador.com - Tu comparador de precios de confianza
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    /**
     * Generar contenido de texto plano del correo
     */
    private function generarContenidoTexto($producto, $precioActual, $urlProducto, $precioLimite, $urlCancelar, $vecesEnviado = 1)
    {
        // Obtener la unidad de medida
        $unidadMedida = $this->obtenerUnidadMedida($producto->unidadDeMedida);
        
        // Generar mensaje de veces avisado
        if ($vecesEnviado >= 4) {
            $mensajeAvisado = "Has sido avisado 4 veces, tu aviso ha sido eliminado. Si quieres seguir recibiendo avisos tienes que volver a suscribirte en la web.";
        } elseif ($vecesEnviado == 1) {
            $mensajeAvisado = "Avisado 1 vez. Después del 4º aviso ya no recibirás más.";
        } else {
            $mensajeAvisado = "Avisado {$vecesEnviado} veces. Después del 4º aviso ya no recibirás más.";
        }
        
        return "
¡El Precio ha bajado! 🎉

{$producto->nombre}

Ahora por {$precioActual} € {$unidadMedida}
Tu precio límite era: {$precioLimite}€{$unidadMedida}

Ver ofertas: {$urlProducto}

{$mensajeAvisado}

Para cancelar tu alerta y no recibir más notificaciones para este producto, visita: {$urlCancelar}

Este correo es solo para notificaciones de precios. No será utilizado para publicidad.

© " . date('Y') . " Komparador.com - Tu comparador de precios de confianza";
    }

    /**
     * Construir URL del producto basada en la jerarquía de categorías
     */
    private function construirUrlProducto($producto)
    {
        $path = $producto->categoria->construirUrlCategorias($producto->slug);
        return 'https://komparador.com' . $path;
    }

    /**
     * Construir URL de categoría con filtros aplicados y orden por precio.
     *
     * @param  array<string, array<int, string|int>>  $seleccionadas
     */
    private function construirUrlCategoriaConFiltros(Categoria $categoria, array $seleccionadas): string
    {
        $filtrosCategoria = [];
        if (is_array($categoria->especificaciones_internas) && isset($categoria->especificaciones_internas['filtros'])) {
            $filtrosCategoria = is_array($categoria->especificaciones_internas['filtros'])
                ? $categoria->especificaciones_internas['filtros']
                : [];
        }

        $mapaIdsASlugs = [];
        foreach ($filtrosCategoria as $filtro) {
            foreach (($filtro['subprincipales'] ?? []) as $sub) {
                $subId = strval($sub['id'] ?? '');
                if ($subId === '') {
                    continue;
                }
                $slug = $sub['slug'] ?? Str::slug($sub['texto'] ?? '');
                if ($slug !== '') {
                    $mapaIdsASlugs[$subId] = $slug;
                }
            }
        }

        $segmentos = [];
        foreach ($seleccionadas as $lineaId => $ids) {
            if (str_starts_with((string) $lineaId, '_') || $lineaId === 'precio_min' || $lineaId === 'precio_max' || !is_array($ids) || $ids === []) {
                continue;
            }
            $slugs = [];
            foreach ($ids as $subIdRaw) {
                $subId = strval($subIdRaw);
                if (isset($mapaIdsASlugs[$subId])) {
                    $slugs[] = $mapaIdsASlugs[$subId];
                }
            }
            $slugs = array_values(array_unique(array_filter($slugs)));
            if ($slugs !== []) {
                $segmentos[] = implode('-', $slugs);
            }
        }

        $path = '/categoria/' . $categoria->slug;
        if ($segmentos !== []) {
            $path .= '/' . implode('/', $segmentos);
        }

        return 'https://komparador.com' . $path . '?orden=precio';
    }

    private function generarContenidoHtmlCategoria(
        string $nombreCategoria,
        float $precioActual,
        string $urlCategoria,
        float $precioLimite,
        string $urlCancelar,
        int $vecesEnviado = 1,
        int $totalCoincidentes = 0,
        array $nombresEspecificaciones = []
    ): string {
        if ($vecesEnviado >= 4) {
            $mensajeVecesAvisado = "<p style=\"color: #666; margin: 10px 0 0 0; font-size: 14px; text-align: center;\">Has sido avisado 4 veces, tu aviso ha sido eliminado. Si quieres seguir recibiendo avisos tienes que volver a suscribirte en la web.</p>";
        } elseif ($vecesEnviado == 1) {
            $mensajeVecesAvisado = "<p style=\"color: #666; margin: 10px 0 0 0; font-size: 14px; text-align: center;\">Avisado 1 vez. Después del 4º aviso ya no recibirás más.</p>";
        } else {
            $mensajeVecesAvisado = "<p style=\"color: #666; margin: 10px 0 0 0; font-size: 14px; text-align: center;\">Avisado {$vecesEnviado} veces. Después del 4º aviso ya no recibirás más.</p>";
        }

        $precioActualFmt = number_format($precioActual, 2, ',', '.');
        $precioLimiteFmt = number_format($precioLimite, 2, ',', '.');
        $conteoFmt = number_format($totalCoincidentes, 0, ',', '.');
        $especificacionesTexto = trim(implode(' · ', array_filter(array_map('strval', $nombresEspecificaciones))));
        $titularEspecificaciones = $especificacionesTexto !== '' ? $especificacionesTexto : 'Hay productos';
        $lineaEspecificacionesHtml = $especificacionesTexto !== ''
            ? "<div style='margin-top: 8px; color: #8b95a1; font-size: 12px;'>Para tu selección de: {$especificacionesTexto}</div>"
            : '';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Productos por debajo de tu precio</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
            <div style='background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                <div style='background: linear-gradient(135deg, #e91e63 0%, #ff6b9d 100%); padding: 25px 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px; font-weight: bold;'>¡{$titularEspecificaciones} por debajo de {$precioLimiteFmt} €! 🎉</h1>
                    <p style='color: rgba(255, 255, 255, 0.9); margin: 8px 0 0 0; font-size: 14px;'>Tu alerta para la categoría {$nombreCategoria} se ha activado</p>
                </div>

                <div style='padding: 30px 20px; text-align: center;'>
                    {$lineaEspecificacionesHtml}

                    <div style='margin-bottom: 25px;'>
                        <div style='font-size: 32px; font-weight: bold; color: #e91e63; margin-bottom: 8px;'>
                            <strong>{$precioActualFmt} €</strong>
                        </div>
                        <div style='color: #666; font-size: 16px;'>
                            Tu precio límite era: <strong>{$precioLimiteFmt} €</strong>
                        </div>
                        <div style='margin-top: 12px; color: #555; font-size: 15px;'>
                            Se han encontrado <strong>{$conteoFmt}</strong> " . ($totalCoincidentes === 1 ? "producto" : "productos") . " por debajo de tu precio.
                        </div>
                    </div>

                    <a href='{$urlCategoria}' style='display: inline-block; background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 6px rgba(0, 123, 255, 0.3); margin-bottom: 10px;'>
                        Ver categoría filtrada
                    </a>

                    {$mensajeVecesAvisado}
                </div>

                <div style='background-color: #f8f9fa; padding: 20px; border-top: 1px solid #e9ecef;'>
                    <div style='text-align: center; margin-bottom: 15px;'>
                        <p style='color: #666; margin: 0; font-size: 14px;'>
                            Si ya no quieres recibir más alertas para esta categoría, puedes
                            <a href='{$urlCancelar}' style='color: #e91e63; text-decoration: underline; font-weight: bold;'>cancelar tu alerta aquí</a>.
                        </p>
                    </div>
                    <div style='text-align: center;'>
                        <p style='color: #999; margin: 0; font-size: 12px;'>
                            Este correo es solo para notificaciones de precios. No será utilizado para publicidad.
                        </p>
                        <p style='color: #999; margin: 5px 0 0 0; font-size: 12px;'>
                            © " . date('Y') . " Komparador.com - Tu comparador de precios de confianza
                        </p>
                    </div>
                </div>
            </div>
        </body>
        </html>";
    }

    private function generarContenidoTextoCategoria(
        string $nombreCategoria,
        float $precioActual,
        string $urlCategoria,
        float $precioLimite,
        string $urlCancelar,
        int $vecesEnviado = 1,
        int $totalCoincidentes = 0,
        array $nombresEspecificaciones = []
    ): string {
        $mensajeAvisado = $vecesEnviado >= 4
            ? 'Has sido avisado 4 veces, tu aviso ha sido eliminado.'
            : "Avisado {$vecesEnviado} veces. Después del 4º aviso ya no recibirás más.";

        $especificacionesTexto = trim(implode(' · ', array_filter(array_map('strval', $nombresEspecificaciones))));
        $titularEspecificaciones = $especificacionesTexto !== '' ? $especificacionesTexto : 'Hay productos';
        $lineaSeleccion = $especificacionesTexto !== '' ? "Para tu selección de: {$especificacionesTexto}\n" : '';

        return "{$titularEspecificaciones} por debajo de " . number_format($precioLimite, 2, ',', '.') . " €\n"
            . "Tu alerta para la categoría {$nombreCategoria} se ha activado\n"
            . $lineaSeleccion . "\n"
            . "Productos encontrados por debajo de tu precio: {$totalCoincidentes}\n"
            . 'Precio mínimo encontrado: ' . number_format($precioActual, 2, ',', '.') . " €\n"
            . 'Tu precio límite era: ' . number_format($precioLimite, 2, ',', '.') . " €\n\n"
            . "Ver categoría filtrada y ordenada por precio: {$urlCategoria}\n\n"
            . "{$mensajeAvisado}\n\n"
            . "Cancelar alerta: {$urlCancelar}\n";
    }

    /**
     * Normaliza el JSON de variantes para guardarlo en correos_aviso_precio (claves de línea string, ids string).
     *
     * @param  mixed  $raw
     * @return array<string, list<string>>
     */
    private function normalizarEspecificacionesInternasSeleccionadas($raw): array
    {
        if ($raw === null) {
            return [];
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $lineaId => $ids) {
            if ($lineaId === 'precio_min' || $lineaId === 'precio_max' || str_starts_with((string) $lineaId, '_')) {
                continue;
            }
            if (!is_array($ids)) {
                continue;
            }
            $lineaKey = strval($lineaId);
            $idsLimpios = array_values(array_filter(array_map('strval', $ids), fn ($v) => $v !== ''));
            if ($idsLimpios !== []) {
                $out[$lineaKey] = $idsLimpios;
            }
        }

        return $out;
    }

    /**
     * Marca el JSON como alerta de categoría sin nuevas columnas.
     */
    private function inyectarMetaAlertaCategoria(array $seleccion, Categoria $categoria, array $nombresSeleccionados = []): array
    {
        $seleccion['_alerta_tipo'] = 'categoria';
        $seleccion['_categoria_id'] = (string) $categoria->id;
        $seleccion['_categoria_slug'] = (string) $categoria->slug;
        $seleccion['_especificaciones_nombres'] = array_values(array_unique(array_filter(array_map('strval', $nombresSeleccionados))));
        return $seleccion;
    }

    /**
     * @return array{categoria_id?: int, categoria_slug?: string}
     */
    private function extraerMetaAlertaCategoria(CorreoAvisoPrecio $alerta): array
    {
        $raw = is_array($alerta->especificaciones_internas_seleccionadas)
            ? $alerta->especificaciones_internas_seleccionadas
            : [];
        $out = [];
        if (($raw['_alerta_tipo'] ?? null) === 'categoria') {
            if (isset($raw['_categoria_id']) && is_numeric($raw['_categoria_id'])) {
                $out['categoria_id'] = (int) $raw['_categoria_id'];
            }
            if (isset($raw['_categoria_slug']) && is_string($raw['_categoria_slug'])) {
                $out['categoria_slug'] = $raw['_categoria_slug'];
            }
        }
        return $out;
    }

    /**
     * @param  array<string, mixed>  $seleccionadas
     * @return array<int, string>
     */
    private function obtenerNombresEspecificacionesSeleccionadasCategoria(Categoria $categoria, array $seleccionadas): array
    {
        if (isset($seleccionadas['_especificaciones_nombres']) && is_array($seleccionadas['_especificaciones_nombres'])) {
            $directos = array_values(array_unique(array_filter(array_map('strval', $seleccionadas['_especificaciones_nombres']))));
            if ($directos !== []) {
                return $directos;
            }
        }

        $mapa = [];
        $filtros = (is_array($categoria->especificaciones_internas) && isset($categoria->especificaciones_internas['filtros']) && is_array($categoria->especificaciones_internas['filtros']))
            ? $categoria->especificaciones_internas['filtros']
            : [];

        foreach ($filtros as $filtro) {
            foreach (($filtro['subprincipales'] ?? []) as $sub) {
                $subId = strval($sub['id'] ?? '');
                $texto = trim((string) ($sub['texto'] ?? ''));
                if ($subId !== '' && $texto !== '') {
                    $mapa[$subId] = $texto;
                }
            }
        }

        $nombres = [];
        foreach ($seleccionadas as $lineaId => $ids) {
            if ($lineaId === 'precio_min' || $lineaId === 'precio_max' || str_starts_with((string) $lineaId, '_') || !is_array($ids)) {
                continue;
            }
            foreach ($ids as $id) {
                $k = strval($id);
                if ($k !== '' && isset($mapa[$k])) {
                    $nombres[] = $mapa[$k];
                }
            }
        }

        return array_values(array_unique($nombres));
    }

    private function obtenerProductoSoporteCategoria(Categoria $categoria): ?\App\Models\Producto
    {
        $ids = Categoria::idsSelfAndDescendants((int) $categoria->id);
        return \App\Models\Producto::query()
            ->whereIn('categoria_id', $ids)
            ->where('mostrar', 'si')
            ->where('precio', '>', 0)
            ->orderBy('id')
            ->first();
    }

    /**
     * Enviar correo de confirmación (doble opt-in).
     */
    private function enviarCorreoConfirmacionAlerta(CorreoAvisoPrecio $alerta): void
    {
        $brevoApiKey = env('BREVO_API_KEY');
        if (!$brevoApiKey) {
            throw new \Exception('BREVO_API_KEY no configurada');
        }

        $urlConfirmar = $this->generarUrlConfirmarAlerta($alerta);
        $metaCategoria = $this->extraerMetaAlertaCategoria($alerta);
        $categoria = isset($metaCategoria['categoria_id']) ? Categoria::query()->find((int) $metaCategoria['categoria_id']) : null;
        $producto = $alerta->producto;
        $esCategoria = (bool) $categoria;

        $tituloObjeto = $esCategoria
            ? ('la categoría ' . ($categoria->nombre ?? 'seleccionada'))
            : ('el producto ' . ($producto->nombre ?? 'seleccionado'));
        $subject = 'Confirma tu alerta de precio en Komparador.com';

        $htmlContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Confirma tu alerta</title>
        </head>
        <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;'>
            <div style='background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                <div style='background: linear-gradient(135deg, #e97b11 0%, #ffb347 100%); padding: 25px 20px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 24px; font-weight: bold;'>Confirma tu alerta de precio</h1>
                    <p style='color: rgba(255, 255, 255, 0.95); margin: 8px 0 0 0; font-size: 14px;'>Solo falta un paso para activarla</p>
                </div>
                <div style='padding: 26px 20px; text-align: center;'>
                    <p style='margin: 0 0 12px 0; font-size: 15px; color: #444;'>Has solicitado un aviso para {$tituloObjeto}.</p>
                    <p style='margin: 0 0 16px 0; font-size: 15px; color: #444;'>
                        Precio límite: <strong>" . number_format((float) $alerta->precio_limite, 2, ',', '.') . " €</strong>
                    </p>
                    <a href='{$urlConfirmar}' style='display: inline-block; background: linear-gradient(135deg, #e97b11 0%, #d16a0f 100%); color: white; padding: 14px 26px; text-decoration: none; border-radius: 24px; font-weight: bold; font-size: 16px;'>
                        Confirmar alerta
                    </a>
                    <p style='margin: 16px 0 0 0; font-size: 13px; color: #666;'>
                        Este enlace caduca en <strong>1 hora</strong>.
                    </p>
                </div>
            </div>
        </body>
        </html>";

        $textContent = "Confirma tu alerta de precio\n\n"
            . "Has solicitado un aviso para {$tituloObjeto}.\n"
            . "Precio límite: " . number_format((float) $alerta->precio_limite, 2, ',', '.') . " €\n\n"
            . "Confirma aquí (válido 1 hora): {$urlConfirmar}\n";

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'api-key' => $brevoApiKey,
            'content-type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => 'Komparador',
                'email' => 'no-reply@komparador.com',
            ],
            'to' => [[
                'email' => $alerta->correo,
            ]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
            'textContent' => $textContent,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Error al enviar correo de confirmación: ' . $response->body());
        }
    }

    private function generarUrlConfirmarAlerta(CorreoAvisoPrecio $alerta): string
    {
        if (!$alerta->token_cancelacion) {
            $alerta->update(['token_cancelacion' => $this->generarTokenUnico()]);
            $alerta->refresh();
        }

        return "https://komparador.com/confirmar-alerta/" . $alerta->token_cancelacion;
    }

    /**
     * Confirmar alerta por token (válido 1 hora).
     */
    public function confirmarAlerta(string $token)
    {
        $alerta = CorreoAvisoPrecio::with('producto.categoria', 'producto.categoriaEspecificaciones')
            ->where('token_cancelacion', $token)
            ->first();

        if (!$alerta) {
            return view('alertas.confirmacion', [
                'success' => false,
                'mensaje' => 'No se ha encontrado la solicitud de confirmación o ya no está disponible.',
            ]);
        }

        if ($alerta->confirmado === 'si') {
            return redirect()->route('home');
        }

        $fechaReferencia = $alerta->updated_at ?? $alerta->created_at;
        if ($fechaReferencia && $fechaReferencia->lt(now()->subHour())) {
            $alerta->delete();
            return view('alertas.confirmacion', [
                'success' => false,
                'mensaje' => 'El enlace de confirmación ha caducado (máximo 1 hora). Debes volver a solicitar el aviso de precio.',
            ]);
        }

        $alerta->update(['confirmado' => 'si']);
        $alerta->refresh();

        $metaCategoria = $this->extraerMetaAlertaCategoria($alerta);
        $categoria = isset($metaCategoria['categoria_id']) ? Categoria::query()->find((int) $metaCategoria['categoria_id']) : null;
        $producto = $alerta->producto;
        $categoriaFuenteEspecificaciones = $categoria
            ?: ($producto?->categoriaEspecificaciones ?: $producto?->categoria);
        $especificacionesAgrupadas = $this->obtenerEspecificacionesAgrupadasParaVista($alerta, $categoriaFuenteEspecificaciones, $producto);

        return view('alertas.confirmacion', [
            'success' => true,
            'mensaje' => 'Tu aviso de precio ha quedado confirmado correctamente.',
            'alerta' => $alerta,
            'producto' => $producto,
            'categoria' => $categoria,
            'especificacionesAgrupadas' => $especificacionesAgrupadas,
        ]);
    }

    /**
     * Especificaciones internas legibles para una alerta (misma lógica que la página de confirmación).
     *
     * @return array<string, array<int, string>>
     */
    public function especificacionesAgrupadasLegibles(CorreoAvisoPrecio $alerta): array
    {
        $metaCategoria = $this->extraerMetaAlertaCategoria($alerta);
        $categoria = isset($metaCategoria['categoria_id'])
            ? Categoria::query()->find((int) $metaCategoria['categoria_id'])
            : null;
        $producto = $alerta->producto;
        $categoriaFuenteEspecificaciones = $categoria
            ?: ($producto?->categoriaEspecificaciones ?: $producto?->categoria);

        return $this->obtenerEspecificacionesAgrupadasParaVista($alerta, $categoriaFuenteEspecificaciones, $producto);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function obtenerEspecificacionesAgrupadasParaVista(CorreoAvisoPrecio $alerta, ?Categoria $categoria, ?Producto $producto = null): array
    {
        if (!$categoria) {
            return [];
        }

        // Igual que en la vista de producto: combinar filtros de la categoría y sus padres.
        $categoriasJerarquia = [];
        $actual = $categoria;
        while ($actual) {
            $categoriasJerarquia[] = $actual;
            $actual = $actual->parent_id ? Categoria::query()->find((int) $actual->parent_id) : null;
        }

        $filtros = [];
        foreach ($categoriasJerarquia as $categoriaItem) {
            $esp = is_array($categoriaItem->especificaciones_internas) ? $categoriaItem->especificaciones_internas : [];
            $filtrosItem = $esp['filtros'] ?? [];
            if (is_array($filtrosItem) && $filtrosItem !== []) {
                $filtros = array_merge($filtros, $filtrosItem);
            }
        }

        // Añadir también los filtros propios del producto (p.ej. "Modelo").
        $elegidasProducto = ($producto && is_array($producto->categoria_especificaciones_internas_elegidas))
            ? $producto->categoria_especificaciones_internas_elegidas
            : [];
        $filtrosProducto = [];
        if (isset($elegidasProducto['_producto']['filtros']) && is_array($elegidasProducto['_producto']['filtros'])) {
            $filtrosProducto = array_merge($filtrosProducto, $elegidasProducto['_producto']['filtros']);
        }
        if (isset($elegidasProducto['filtros']) && is_array($elegidasProducto['filtros'])) {
            $filtrosProducto = array_merge($filtrosProducto, $elegidasProducto['filtros']);
        }
        if ($filtrosProducto !== []) {
            $filtros = array_merge($filtros, $filtrosProducto);
        }

        if ($filtros === []) {
            return [];
        }

        $mapaLineas = [];
        $mapaSublineaAGrupo = [];
        foreach ($filtros as $filtro) {
            $lineaId = strval($filtro['id'] ?? '');
            if ($lineaId === '') {
                continue;
            }

            $lineaTexto = trim((string) ($filtro['texto'] ?? ''));
            $sublineas = [];
            foreach (($filtro['subprincipales'] ?? []) as $sub) {
                $subId = strval($sub['id'] ?? '');
                $subTexto = trim((string) ($sub['texto'] ?? ''));
                if ($subId !== '' && $subTexto !== '') {
                    $sublineas[$subId] = $subTexto;
                    $mapaSublineaAGrupo[$subId] = [
                        'linea_texto' => $lineaTexto !== '' ? $lineaTexto : ('Especificación ' . $lineaId),
                        'sub_texto' => $subTexto,
                    ];
                }
            }

            $mapaLineas[$lineaId] = [
                'texto' => $lineaTexto !== '' ? $lineaTexto : ('Especificación ' . $lineaId),
                'sublineas' => $sublineas,
            ];
        }

        $seleccion = is_array($alerta->especificaciones_internas_seleccionadas)
            ? $alerta->especificaciones_internas_seleccionadas
            : [];

        $resultado = [];
        foreach ($seleccion as $lineaIdRaw => $idsRaw) {
            $lineaId = strval($lineaIdRaw);
            if ($lineaId === '' || str_starts_with($lineaId, '_') || $lineaId === 'precio_min' || $lineaId === 'precio_max') {
                continue;
            }
            if (!is_array($idsRaw) || $idsRaw === []) {
                continue;
            }

            $nombres = [];
            $lineaTexto = $mapaLineas[$lineaId]['texto'] ?? ('Especificación ' . $lineaId);

            // Intento principal: resolver por lineaId + sublineaId.
            if (isset($mapaLineas[$lineaId])) {
                foreach ($idsRaw as $subIdRaw) {
                    $subId = strval($subIdRaw);
                    if ($subId !== '' && isset($mapaLineas[$lineaId]['sublineas'][$subId])) {
                        $nombres[] = $mapaLineas[$lineaId]['sublineas'][$subId];
                    }
                }
            }

            // Fallback robusto: resolver por sublineaId aunque el lineaId no case.
            if ($nombres === []) {
                foreach ($idsRaw as $subIdRaw) {
                    $subId = strval($subIdRaw);
                    if ($subId !== '' && isset($mapaSublineaAGrupo[$subId])) {
                        $lineaTexto = $mapaSublineaAGrupo[$subId]['linea_texto'];
                        $nombres[] = $mapaSublineaAGrupo[$subId]['sub_texto'];
                    }
                }
            }

            // Último fallback: mostrar IDs para no perder ninguna selección.
            if ($nombres === []) {
                foreach ($idsRaw as $subIdRaw) {
                    $subId = strval($subIdRaw);
                    if ($subId !== '') {
                        $nombres[] = $subId;
                    }
                }
            }

            $nombres = array_values(array_unique($nombres));
            if ($nombres !== []) {
                if (!isset($resultado[$lineaTexto])) {
                    $resultado[$lineaTexto] = [];
                }
                $resultado[$lineaTexto] = array_values(array_unique(array_merge($resultado[$lineaTexto], $nombres)));
            }
        }

        return $resultado;
    }

    /**
     * Generar token único para cancelar alerta
     */
    private function generarTokenUnico()
    {
        do {
            $token = Str::random(64);
        } while (CorreoAvisoPrecio::where('token_cancelacion', $token)->exists());
        
        return $token;
    }

    /**
     * Generar URL para cancelar alerta
     */
    private function generarUrlCancelarAlerta($alerta)
    {
        // Si la alerta no tiene token, generarlo
        if (!$alerta->token_cancelacion) {
            $alerta->update(['token_cancelacion' => $this->generarTokenUnico()]);
            $alerta->refresh();
        }
        
        return "https://komparador.com/cancelar-alerta/" . $alerta->token_cancelacion;
    }

    /**
     * Mostrar formulario para cancelar alerta
     */
    public function mostrarCancelarAlerta($token)
    {
        $alerta = CorreoAvisoPrecio::with('producto.categoria')
            ->where('token_cancelacion', $token)
            ->firstOrFail();
        
        $producto = $alerta->producto;
        $metaCategoria = $this->extraerMetaAlertaCategoria($alerta);
        $categoria = isset($metaCategoria['categoria_id']) ? Categoria::query()->find((int) $metaCategoria['categoria_id']) : null;

        return view('alertas.cancelar', compact('producto', 'categoria', 'alerta'));
    }

    /**
     * Procesar cancelación de alerta
     */
    public function cancelarAlerta(Request $request)
    {
        $request->validate([
            'token' => 'required|string|size:64',
            'correo' => 'required|email',
            'confirmacion' => 'required|accepted'
        ]);

        try {
            // Buscar alerta verificando AMBOS: token Y correo
            $alerta = CorreoAvisoPrecio::where('token_cancelacion', $request->token)
                ->where('correo', $request->correo)
                ->first();

            if (!$alerta) {
                // No dar información específica sobre qué falló (seguridad)
                return view('alertas.cancelado', [
                    'success' => false,
                    'mensaje' => 'Token o correo inválido. Verifica que estás usando el enlace correcto del correo.'
                ]);
            }

            // Guardar información del producto antes de eliminar la alerta
            $producto = $alerta->producto;
            $metaCategoria = $this->extraerMetaAlertaCategoria($alerta);
            $categoria = isset($metaCategoria['categoria_id']) ? Categoria::query()->find((int) $metaCategoria['categoria_id']) : null;
            $correo = $alerta->correo;
            
            $alerta->delete();

            return view('alertas.cancelado', [
                'success' => true,
                'mensaje' => $producto
                    ? 'Alerta cancelada correctamente. Ya no recibirás más notificaciones para este producto.'
                    : 'Alerta cancelada correctamente. Ya no recibirás más notificaciones para esta categoría.',
                'producto' => $producto,
                'categoria' => $categoria,
                'correo' => $correo
            ]);

        } catch (\Exception $e) {
            return view('alertas.cancelado', [
                'success' => false,
                'mensaje' => 'Error al cancelar la alerta. Inténtalo de nuevo.'
            ]);
        }
    }

    /**
     * Obtener la unidad de medida formateada
     */
    private function obtenerUnidadMedida($unidadDeMedida)
    {
        switch ($unidadDeMedida) {
            case 'unidad':
                return '/Und.';
            case 'kilos':
                return '/kg.';
            case 'litros':
                return '/L.';
            default:
                return '';
        }
    }
}
