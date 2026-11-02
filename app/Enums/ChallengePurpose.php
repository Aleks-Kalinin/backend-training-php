<?php

namespace App\Enums;

/**
 * Which flow a verification challenge belongs to. A challenge issued for one
 * purpose must never be accepted by another flow's endpoint.
 */
enum ChallengePurpose: string
{
    case Registration = 'registration';
    // Added by later specs: Login (03), EmailChange (06), AccountDeletion (07),
    // PasswordReset (13).
}
