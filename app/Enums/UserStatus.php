<?php

namespace App\Enums;

/**
 * Account lifecycle states used across the specs:
 * - Pending  : created, awaiting verification (spec 01)
 * - Active   : may authenticate (specs 01, 03)
 * - Blocked  : may not authenticate (specs 03, 08)
 * - Deleting : deletion accepted, cleanup queued (spec 07)
 * - Deleted  : cleanup finished (specs 07, 08)
 */
enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Blocked = 'blocked';
    case Deleting = 'deleting';
    case Deleted = 'deleted';

    /**
     * Whether an account in this state is allowed to start a session.
     */
    public function canAuthenticate(): bool
    {
        return $this === self::Active;
    }
}
