<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

/**
 * POST /api/v1/auth/register/verify
 *
 * Structural validation only. Whether the attempt exists, is expired or has run
 * out of attempts is decided by the challenge service, which answers 422 for all
 * of those alike - a `exists:` rule here would leak which attemptIds are real.
 */
class VerifyRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'attemptId' => ['required', 'string', 'uuid'],
            'otpCode' => ['required', 'string', 'digits:'.config('verification.otp.length')],
        ];
    }
}
