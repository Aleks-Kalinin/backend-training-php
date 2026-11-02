<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Per-flow verification switches
    |--------------------------------------------------------------------------
    |
    | Registration, login and password reset can require verification
    | independently (spec 01 section 1).
    |
    */

    'registration' => [
        'enabled' => env('REGISTRATION_VERIFICATION_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Log the user in after a successful registration?
    |--------------------------------------------------------------------------
    |
    | Spec 01 section 3.1: "Do not silently log in unless that behavior is
    | explicitly enabled by application configuration." Keep this false.
    |
    */

    'login_after_registration' => env('REGISTRATION_LOGIN_AFTER', false),

    /*
    |--------------------------------------------------------------------------
    | OTP parameters
    |--------------------------------------------------------------------------
    |
    | Fixed by spec 01 section 3.1: 6 digits, 10-minute expiry, 5 failed
    | attempts, 60-second resend cooldown.
    |
    */

    'otp' => [
        'length' => 6,
        'ttl' => 10 * 60,
        'max_attempts' => 5,
        'resend_cooldown' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limits
    |--------------------------------------------------------------------------
    |
    | Consumed by the named limiters in App\Providers\AppServiceProvider.
    | "per_email" limits are keyed by a hash of the normalized address, never
    | by the address itself (spec 03 section 2.1).
    |
    */

    'rate_limits' => [
        'register' => ['per_ip' => 5, 'per_email' => 3, 'decay_minutes' => 60],
        'verify' => ['per_ip' => 10, 'decay_minutes' => 60],
        'resend' => ['per_ip' => 5, 'decay_minutes' => 60],
    ],

];
