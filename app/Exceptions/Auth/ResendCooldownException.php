<?php

namespace App\Exceptions\Auth;

use RuntimeException;

/**
 * Resending sooner than the cooldown allows is a 429 (spec 01 section 4).
 */
class ResendCooldownException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct('A new code was requested too soon.');
    }
}
