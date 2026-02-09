<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('srtocoque', [AuthenticatedSessionController::class, 'create'])
                ->name('login');

    Route::post('srtocoque', [AuthenticatedSessionController::class, 'store'])
                ->middleware('throttle:5,1'); // 5 intentos de login por minuto

    // Recuperación de contraseña (debe estar en guest para usuarios no autenticados)
    // Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
    //             ->name('password.request');

    // Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
    //             ->name('password.email')
    //             ->middleware('throttle:3,1'); // 3 solicitudes de reset por minuto

    // Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
    //             ->name('password.reset');

    // Route::post('reset-password', [NewPasswordController::class, 'store'])
    //             ->name('password.store')
    //             ->middleware('throttle:3,1'); // 3 intentos de reset por minuto
});

Route::middleware('auth')->group(function () {

    //SOLO EL USUARIO CON EL ID1 PUEDE REGISTRAR USUARIOS
    Route::get('register', [RegisteredUserController::class, 'create'])
                ->name('register')
                ->middleware('admin');

    Route::post('register', [RegisteredUserController::class, 'store'])
                ->middleware(['admin', 'throttle:5,1']);
                
    Route::get('verify-email', EmailVerificationPromptController::class)
                ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
                ->middleware(['signed', 'throttle:6,1'])
                ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
                ->middleware('throttle:6,1')
                ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
                ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
                ->name('logout');
});
