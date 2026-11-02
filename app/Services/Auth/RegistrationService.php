<?php

namespace App\Services\Auth;

use App\Enums\ChallengePurpose;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VerificationChallenge;
use LogicException;

/**
 * The registration flow of spec 01 section 3.1, minus the HTTP concerns.
 */
class RegistrationService
{
    public function __construct(private readonly OtpChallengeService $challenges) {}

    /**
     * Create the account, plus a verification challenge when verification is on.
     *
     * $email arrives already normalized from the Form Request.
     */
    public function register(string $email, string $password): RegistrationOutcome
    {
        $verificationRequired = (bool) config('verification.registration.enabled');

        // TODO 1: inside DB::transaction(), create the user with
        //           status = $verificationRequired ? UserStatus::Pending : UserStatus::Active
        //           email_verified_at = $verificationRequired ? null : now()
        //         Assign `status` explicitly on the model: it is not fillable, so
        //         it can never arrive from the request body. The 'password' cast
        //         hashes the value, so assign it in plaintext.

        // TODO 2: when $verificationRequired, issue the challenge through
        //         $this->challenges->issue($user, ChallengePurpose::Registration).
        //         The mail is queued, and spec 00 section 4 requires jobs that
        //         depend on committed rows to be dispatched after the transaction
        //         commits - so issue the challenge after the commit, or register
        //         it with DB::afterCommit().

        // TODO 3: log the outcome - user id and whether verification was required,
        //         never the password (spec 01 section 5).

        throw new LogicException('TODO: implement RegistrationService::register()');
    }

    /**
     * Verify a pending registration and activate the account.
     *
     * Activation and challenge consumption must land in the same transaction.
     */
    public function verify(string $attemptId, string $otpCode): User
    {
        // TODO 4: $challenge = $this->challenges->verify(
        //             $attemptId, $otpCode, ChallengePurpose::Registration
        //         );

        // TODO 5: activate the challenge's user - status = UserStatus::Active and
        //         email_verified_at = now(). Only a Pending account may become
        //         Active; a blocked/deleting/deleted one must not be revived.

        // TODO 6: spec 01 section 3.2 - do NOT start a session here unless
        //         config('verification.login_after_registration') is true.

        throw new LogicException('TODO: implement RegistrationService::verify()');
    }

    /**
     * Resend the code for a pending attempt.
     *
     * Returns null when the attempt is unknown: the controller answers with the
     * same generic response either way, so that /resend cannot be used to probe
     * which attemptIds exist (spec 01 section 3.3).
     */
    public function resend(string $attemptId): ?VerificationChallenge
    {
        // TODO 7: find the unconsumed registration challenge; return null when it
        //         does not exist, otherwise delegate to OtpChallengeService::resend().
        return null;
    }
}
