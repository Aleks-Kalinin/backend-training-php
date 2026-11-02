<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * POST /api/v1/auth/register
 *
 * Only `email` and `password` are accepted. Anything else in the body - role,
 * status, id - is ignored, because validated() is the only thing the controller
 * passes on (spec 01 section 3.1).
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalize before validating, so the unique check and the stored value use
     * the same string.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => User::normalizeEmail($this->input('email'))]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            // Uniqueness is deliberately NOT checked here: a `unique` rule would
            // return 422, and this flow owes a 409 instead (spec 01 section 4).
            // The controller handles the duplicate case.
            'password' => ['required', 'string', Password::defaults()],
        ];
    }
}
