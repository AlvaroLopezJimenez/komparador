<?php

namespace App\Http\Controllers;

use App\Models\VisitaUsuario;
use App\Services\VisitorTrackingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VisitaUsuarioController extends Controller
{
    public function __construct(
        protected VisitorTrackingService $visitorTracking
    ) {}

    public function registrar(Request $request)
    {
        if (Auth::check()) {
            return response()->json(['ok' => true, 'omitida' => true]);
        }

        $validated = $request->validate([
            'producto_id' => 'required|integer|exists:productos,id',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'origen' => 'nullable|string|max:2048',
        ]);

        $tracking = $this->visitorTracking->resolver($request);

        $origen = $validated['origen'] ?? $request->header('Referer');
        if ($origen) {
            $origen = Str::limit($origen, 2048, '');
        }

        $existe = VisitaUsuario::where('visitor_id', $tracking['visitor_id'])
            ->where('session_id', $tracking['session_id'])
            ->where('producto_id', $validated['producto_id'])
            ->exists();

        if (!$existe) {
            VisitaUsuario::create([
                'visitor_id' => $tracking['visitor_id'],
                'session_id' => $tracking['session_id'],
                'producto_id' => $validated['producto_id'],
                'categoria_id' => $validated['categoria_id'],
                'origen' => $origen,
            ]);
        }

        $response = response()->json(['ok' => true], $existe ? 200 : 201);

        return $this->visitorTracking->adjuntarCookies($response, $tracking['cookies']);
    }
}
