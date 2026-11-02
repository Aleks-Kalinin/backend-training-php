<?php

namespace App\Models;

use App\Enums\ChallengePurpose;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single-use OTP challenge. The plaintext code exists only in the email that
 * was sent; this record holds nothing but its hash.
 */
class VerificationChallenge extends Model
{
    use HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'purpose' => ChallengePurpose::class,
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'consumed_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function hasAttemptsLeft(): bool
    {
        return $this->attempts < config('verification.otp.max_attempts');
    }

    /**
     * Usable means: not consumed, not expired, attempts not exhausted.
     */
    public function isUsable(): bool
    {
        return ! $this->isConsumed() && ! $this->isExpired() && $this->hasAttemptsLeft();
    }

    /**
     * Seconds the caller must still wait before /resend will send another code.
     * Returns 0 when a resend is allowed now.
     */
    public function resendCooldownRemaining(): int
    {
        // TODO: compare last_sent_at against config('verification.otp.resend_cooldown')
        // and return the remaining whole seconds (never negative).
        return 0;
    }
}
