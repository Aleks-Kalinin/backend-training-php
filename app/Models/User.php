<?php

namespace App\Models;

use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * `status`, not `email_verified_at`, decides whether an account may be used.
 * Nothing that comes from a client request may ever be mass-assigned into
 * `status` - see spec 01 section 3.1 ("Do not trust client-provided account state").
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    /**
     * @return HasMany<VerificationChallenge, $this>
     */
    public function verificationChallenges(): HasMany
    {
        return $this->hasMany(VerificationChallenge::class);
    }

    /**
     * Trim and lowercase an address so that lookups, the unique index and the
     * stored value all agree. This is the single definition of "normalized".
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
