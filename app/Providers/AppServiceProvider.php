<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePasswordPolicy();
        $this->configureRateLimiters();
    }

    /**
     * The one definition of "configured password policy" (spec 01 section 3.1).
     * Password::defaults() is what the Form Requests reference.
     */
    protected function configurePasswordPolicy(): void
    {
        Password::defaults(function () {
            $rule = Password::min(8)->letters()->mixedCase()->numbers()->symbols();

            // uncompromised() calls the Have I Been Pwned API, which would make
            // tests depend on the network.
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });
    }

    /**
     * Named limiters used by routes/api.php. Limits come from
     * config/verification.php so the numbers stay in one place.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('register', function (Request $request) {
            // TODO 1: return an array of two limits so that both apply:
            //   - per IP:    Limit::perMinutes($decay, $perIp)->by('register-ip:'.$request->ip())
            //   - per email: keyed by a HASH of the normalized address, never the
            //                address itself (spec 03 section 2.1) - e.g.
            //                hash('sha256', User::normalizeEmail($request->input('email', '')))
            //
            // Read the numbers from config('verification.rate_limits.register').
            return Limit::none();
        });

        RateLimiter::for('register-verify', function (Request $request) {
            // TODO 2: limit per IP and per attemptId, so that guessing a 6-digit
            //         code is bounded by more than the 5 per-challenge attempts.
            return Limit::none();
        });

        RateLimiter::for('register-resend', function (Request $request) {
            // TODO 3: limit per IP and per attemptId. The 60-second cooldown is
            //         enforced separately, on the challenge itself.
            return Limit::none();
        });
    }
}
