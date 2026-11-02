<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A server-constructed representation - never a raw model serialization
 * (spec 05 section 2.1). Fields are added here deliberately, one at a time.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'status' => $this->status->value,
            'createdAt' => $this->created_at?->toIso8601ZuluString(),
        ];
    }
}
