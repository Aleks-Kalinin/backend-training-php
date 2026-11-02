<?php

use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Http\Controllers\Api\V1\PingController;
use Illuminate\Support\Facades\Route;

// Every route in this file is prefixed with /api/v1 (see bootstrap/app.php).

Route::get('/ping', PingController::class);

// Spec: specs-php/01-registration/spec.md
// The named limiters live in App\Providers\AppServiceProvider.
Route::prefix('auth')->group(function (): void {
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:register');

    Route::post('/register/verify', [RegisterController::class, 'verify'])
        ->middleware('throttle:register-verify');

    Route::post('/register/resend', [RegisterController::class, 'resend'])
        ->middleware('throttle:register-resend');
});
