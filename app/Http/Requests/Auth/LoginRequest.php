<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'usuario' => ['required', 'string', 'email'],
            'comentario' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Mapear 'usuario' a 'email' y 'comentario' a 'password' internamente
        $this->merge([
            'email' => $this->input('usuario'),
            'password' => $this->input('comentario'),
        ]);
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'usuario.required' => 'El campo nombre o correo es obligatorio.',
            'usuario.email' => 'Por favor, introduce un correo electrónico válido.',
            'comentario.required' => 'El campo comentario es obligatorio.',
            'comentario.min' => 'El comentario debe tener al menos 6 caracteres.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('recordar'))) {
            RateLimiter::hit($this->throttleKey());

            // Generar aviso interno cuando hay un intento erróneo
            try {
                $usuario = $this->input('usuario');
                $url = $this->fullUrl();
                $hora = now();
                $ip = $this->ip();
                $textoAviso = "Intento de acceso erróneo. Usuario: {$usuario} - URL: {$url} - Hora: {$hora} - IP: {$ip}";
                
                \DB::table('avisos')->insert([
                    'texto_aviso'     => $textoAviso,
                    'fecha_aviso'     => $hora,
                    'user_id'         => 1,
                    'avisoable_type'  => 'Interno',
                    'avisoable_id'    => 0,
                    'oculto'          => 0,
                    'created_at'      => $hora,
                    'updated_at'      => $hora,
                ]);
            } catch (\Exception $e) {
                \Log::error('Error al generar aviso: ' . $e->getMessage());
            }

            throw ValidationException::withMessages([
                'usuario' => 'No se pudo procesar tu comentario. Verifica que el correo sea válido.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'usuario' => 'Has enviado demasiados comentarios. Por favor, espera ' . ceil($seconds / 60) . ' minutos antes de intentar de nuevo.',
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        $usuario = $this->input('usuario', '');
        return Str::transliterate(Str::lower($usuario).'|'.$this->ip());
    }
}
