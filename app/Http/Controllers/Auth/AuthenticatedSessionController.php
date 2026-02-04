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
     * Display the article view.
     */
    public function create(Request $request)
    {
        return response()
            ->view('auth.login')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }


    /**
     * Handle an incoming form submission.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Registrar actividad
        try {
            $user = Auth::user();
            if ($user) {
                \App\Models\UserActivity::create([
                    'user_id' => $user->id,
                    'action_type' => \App\Models\UserActivity::ACTION_LOGIN,
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error al registrar actividad: ' . $e->getMessage());
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
