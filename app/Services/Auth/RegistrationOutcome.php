<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\VerificationChallenge;

/**
 * What the controller needs in order to choose between 201 and 202.
 */
final readonly class RegistrationOutcome
{
    public function __construct(
        public User $user,
        public ?VerificationChallenge $challenge = null,
    ) {}

    public function requiresVerification(): bool
    {
        return $this->challenge !== null;
    }
}
