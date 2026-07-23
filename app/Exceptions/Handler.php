<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // En test-precio / scraping admin: devolver siempre el error real en JSON
        // (con APP_DEBUG=false Laravel solo manda {"message":"Server Error"}).
        $this->renderable(function (Throwable $e, $request) {
            $path = (string) $request->path();
            $esScrapingAdmin = str_contains($path, 'admin/scraping')
                || str_contains($path, 'scraping/test-precio');

            if (!$esScrapingAdmin) {
                return null;
            }

            $wantsJson = $request->expectsJson()
                || $request->ajax()
                || str_contains((string) $request->header('Accept'), 'application/json')
                || str_contains($path, 'test-precio/procesar');

            if (!$wantsJson) {
                return null;
            }

            return response()->json([
                'success' => false,
                'error' => $e->getMessage() !== '' ? $e->getMessage() : 'Excepción sin mensaje',
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Excepción sin mensaje',
                'debug' => [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => collect($e->getTrace())->take(10)->map(function ($frame) {
                        return [
                            'file' => $frame['file'] ?? null,
                            'line' => $frame['line'] ?? null,
                            'function' => $frame['function'] ?? null,
                            'class' => $frame['class'] ?? null,
                        ];
                    })->values()->all(),
                ],
            ], method_exists($e, 'getStatusCode') ? (int) $e->getStatusCode() : 500);
        });
    }
}
