<?php

namespace App\Services\Auth;

use App\Enums\ChallengePurpose;
use App\Exceptions\Auth\ChallengeException;
use App\Exceptions\Auth\ResendCooldownException;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use App\Models\VerificationChallenge;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Issues, verifies and resends one-time codes.
 *
 * Rules this class is responsible for (spec 01 section 3.1):
 *  - the plaintext code leaves this class exactly once, towards the mailer;
 *  - only a hash is persisted;
 *  - a challenge is consumed atomically, so two simultaneous correct submissions
 *    can never both succeed;
 *  - nothing here is ever written to the log.
 */
class OtpChallengeService
{
    /**
     * Create a challenge for the user and queue the email carrying the code.
     *
     * Any previously open challenge for the same user and purpose should be
     * invalidated so that only the newest code works.
     */
    public function issue(User $user, ChallengePurpose $purpose): VerificationChallenge
    {
        // TODO 1: invalidate this user's other unconsumed challenges for $purpose.
        //         A conditional update is enough: set consumed_at = now() where
        //         user_id = ..., purpose = ... and consumed_at is null.

        // TODO 2: generate the code - see generateCode() below.
        $code = $this->generateCode();

        // TODO 3: create the VerificationChallenge row. Store Hash::make($code) in
        //         code_hash - never $code itself - and set expires_at from
        //         config('verification.otp.ttl') and last_sent_at to now().
        $challenge = new VerificationChallenge;

        $this->send($user, $challenge, $code);

        return $challenge;
    }

    /**
     * Validate a submitted code and consume the challenge.
     *
     * @throws ChallengeException on an unknown, expired, consumed, wrong-purpose
     *                            or incorrect code - all of which must look the
     *                            same from the outside.
     */
    public function verify(string $challengeId, string $code, ChallengePurpose $purpose): VerificationChallenge
    {
        // TODO 4: load the challenge by id AND purpose. Treat "not found" exactly
        //         like "expired": throw ChallengeException::invalid().

        // TODO 5: check isUsable(). When attempts are exhausted throw
        //         ChallengeException::attemptsExhausted(), otherwise invalid().

        // TODO 6: compare with Hash::check($code, $challenge->code_hash).
        //         On a mismatch, increment and persist `attempts`, then throw.

        // TODO 7: consume atomically. A plain save() is NOT enough - two parallel
        //         requests would both pass the checks above. Use a conditional
        //         update and treat "0 rows affected" as a failure:
        //
        //           $claimed = VerificationChallenge::whereKey($challenge->id)
        //               ->whereNull('consumed_at')
        //               ->update(['consumed_at' => now()]);
        //
        //         Run steps 4-7 inside DB::transaction() so that the account
        //         activation which follows commits together with the consumption.

        throw ChallengeException::invalid();
    }

    /**
     * Send a fresh code for the same user and purpose.
     *
     * @throws ResendCooldownException when called inside the cooldown window
     * @throws ChallengeException when the challenge is no longer usable
     */
    public function resend(VerificationChallenge $challenge): VerificationChallenge
    {
        // TODO 8: enforce $challenge->resendCooldownRemaining() and throw
        //         ResendCooldownException with the remaining seconds.

        // TODO 9: issue a fresh code for the same user and purpose. Reusing
        //         issue() is fine - it already invalidates the previous challenge.

        throw ChallengeException::invalid();
    }

    /**
     * Queue the mail and record the outcome. The code is passed by value and is
     * never stored on this object.
     */
    protected function send(User $user, VerificationChallenge $challenge, string $code): void
    {
        Mail::to($user->email)->queue(new VerificationCodeMail($code, $challenge->purpose));

        // Dispatch status is logged; the code is not (spec 01 section 5).
        Log::info('verification.code.queued', [
            'userId' => $user->id,
            'challengeId' => $challenge->id,
            'purpose' => $challenge->purpose?->value,
        ]);
    }

    /**
     * @return string exactly config('verification.otp.length') digits, leading
     *                zeros included, e.g. "043180"
     */
    protected function generateCode(): string
    {
        // TODO 2: random_int() is the right primitive here (rand()/mt_rand() are
        //         not cryptographically secure). str_pad() the result so that a
        //         small number still produces a full-length code.
        return '';
    }
}
