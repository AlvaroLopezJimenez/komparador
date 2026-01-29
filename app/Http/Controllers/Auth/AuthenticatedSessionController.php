<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request)
    {
        return response()
            ->view('auth.login') // o la vista correspondiente si usas otra
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }


    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Registrar actividad de login
        try {
            $user = Auth::user();
            if ($user) {
                \App\Models\UserActivity::create([
                    'user_id' => $user->id,
                    'action_type' => \App\Models\UserActivity::ACTION_LOGIN,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad de login: ' . $e->getMessage());
        }

        return redirect()->intended(RouteServiceProvider::HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
