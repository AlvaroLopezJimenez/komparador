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
                    // Si veces_enviado es null, inicializarlo a 0
                    'veces_enviado' => $alertaExistente->veces_enviado ?? 0
                ];
                
                // Generar token si no existe
                if (!$alertaExistente->token_cancelacion) {
                    $datosActualizacion['token_cancelacion'] = $this->generarTokenUnico();
                }
                
                $alertaExistente->update($datosActualizacion);
            } else {
                // Crear nueva alerta
                CorreoAvisoPrecio::create([
                    'correo' => $request->correo,
                    'precio_limite' => $request->precio_limite,
                    'producto_id' => $request->producto_id,
                    'especificaciones_internas_seleccionadas' => $seleccionNormalizada,
                    'token_cancelacion' => $this->generarTokenUnico(),
                    'veces_enviado' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Alerta guardada correctamente. Te avisaremos cuando el precio baje.'
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
     * Enviar correos de alerta cuando el precio baja
     */
    public function enviarAlertasPrecio($productoId, $precioActual)
    {
        try {
            // Obtener todas las alertas activas para este producto
            $alertas = CorreoAvisoPrecio::with('producto')
                ->where('producto_id', $productoId)
                ->where('precio_limite', '>=', $precioActual)
                ->get();

            $totalAlertas = $alertas->count();

            if ($alertas->isEmpty()) {
                return [
                    'success' => true,
                    'message' => 'No hay alertas activas para este producto.',
                    'enviados' => 0,
                    'total_alertas' => 0,
                    'alertas_filtradas' => 0
                ];
            }

            $producto = $alertas->first()->producto;
            $urlProducto = $this->construirUrlProducto($producto);
            
            $enviados = 0;
            $filtrados = 0;
            $errores = [];

            foreach ($alertas as $alerta) {
                // Filtrar correos que ya recibieron un correo en la última semana
                if ($alerta->ultimo_envio_correo && $alerta->ultimo_envio_correo > now()->subWeek()) {
                    $filtrados++;
                    $errores[] = "Correo {$alerta->correo} filtrado: último envío hace " . 
                                 now()->diffInDays($alerta->ultimo_envio_correo) . " días (" . 
                                 $alerta->ultimo_envio_correo->format('Y-m-d H:i:s') . ")";
                    continue; // Saltar este correo
                }
                
                try {
                    // Obtener el valor actual de veces_enviado (asegurarse de que sea un entero)
                    $vecesEnviadoActual = (int)($alerta->veces_enviado ?? 0);
                    
                    // Calcular veces_enviado antes de enviar el correo (valor actual + 1)
                    $vecesEnviado = $vecesEnviadoActual + 1;
                    
                    // Enviar el correo con el número de veces que se está enviando
                    $this->enviarCorreoAlerta($alerta, $producto, $precioActual, $urlProducto, $vecesEnviado);
                    $enviados++;
                    
                    // Si llega a 4, eliminar el registro después de enviar el correo
                    if ($vecesEnviado >= 4) {
                        $alerta->delete();
                    } else {
                        // Actualizar la fecha de último envío y veces_enviado solo si el correo se envió correctamente
                        $alerta->update([
                            'ultimo_envio_correo' => now(),
                            'veces_enviado' => $vecesEnviado
                        ]);
                    }
                    
                } catch (\Exception $e) {
                    $errores[] = "Error enviando correo a {$alerta->correo}: " . $e->getMessage();
                }
            }

            $alertasFiltradas = $totalAlertas - $filtrados;

            return [
                'success' => true,
                'message' => "Se enviaron {$enviados} de {$alertasFiltradas} correos válidos.",
                'enviados' => $enviados,
                'total_alertas' => $totalAlertas,
                'alertas_filtradas' => $alertasFiltradas,
                'alertas_omitidas_por_fecha' => $filtrados,
                'errores' => $errores
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al enviar alertas: ' . $e->getMessage(),
                'enviados' => 0
            ];
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
            if ($lineaId === 'precio_min' || $lineaId === 'precio_max') {
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
        $alerta = CorreoAvisoPrecio::with('producto')
            ->where('token_cancelacion', $token)
            ->firstOrFail();
        
        $producto = $alerta->producto;
        
        return view('alertas.cancelar', compact('producto', 'alerta'));
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
            $correo = $alerta->correo;
            
            $alerta->delete();

            return view('alertas.cancelado', [
                'success' => true,
                'mensaje' => 'Alerta cancelada correctamente. Ya no recibirás más notificaciones para este producto.',
                'producto' => $producto,
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
