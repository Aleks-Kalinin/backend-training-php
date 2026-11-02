<?php

namespace App\Exceptions\Auth;

use RuntimeException;

/**
 * Every failure mode of a verification challenge maps to 422 (spec 01 section 4),
 * with a stable machine-readable code. The messages are deliberately vague: they
 * must not tell a caller whether an attemptId exists, is expired or belongs to
 * someone else.
 */
class ChallengeException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }

    public static function invalid(): self
    {
        return new self('CHALLENGE_INVALID', 'The verification challenge is invalid or has expired.');
    }

    public static function attemptsExhausted(): self
    {
        return new self('CHALLENGE_ATTEMPTS_EXHAUSTED', 'Too many incorrect codes were submitted. Request a new code.');
    }
}
