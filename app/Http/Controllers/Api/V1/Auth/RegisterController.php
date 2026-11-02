<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResendRegistrationRequest;
use App\Http\Requests\Api\V1\Auth\VerifyRegistrationRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

/**
 * Spec: specs-php/01-registration/spec.md
 *
 * The controller stays thin: validate, delegate, choose a status code. All the
 * rules live in RegistrationService and OtpChallengeService.
 */
class RegisterController extends Controller
{
    public function __construct(private readonly RegistrationService $registration) {}

    /**
     * POST /api/v1/auth/register
     *
     * 201 when the account is active immediately, 202 when a code was sent,
     * 409 when the address is already registered.
     */
    public function store(RegisterRequest $request): JsonResponse
    {
        ['email' => $email, 'password' => $password] = $request->validated();

        // TODO 1: return a 409 when $email already exists. Keep the body generic -
        //         no hint about whether that account is pending, active or blocked:
        //
        //           return response()->json([
        //               'error' => ['code' => 'EMAIL_ALREADY_REGISTERED', 'message' => '...'],
        //           ], Response::HTTP_CONFLICT);
        //
        //         Mind the race: two simultaneous requests can both pass this
        //         check, so catch the unique-constraint violation from the insert
        //         and answer 409 there too.

        $outcome = $this->registration->register($email, $password);

        // TODO 2: branch on $outcome->requiresVerification().
        //         - verification off -> 201 with UserResource
        //         - verification on  -> 202 with
        //             ['data' => ['attemptId' => ..., 'verificationRequired' => true]]
        //         Do not log the user in in either branch unless
        //         config('verification.login_after_registration') is true.

        return response()->json([], Response::HTTP_NOT_IMPLEMENTED);
    }

    /**
     * POST /api/v1/auth/register/verify
     *
     * 200 with the activated user. No session is established.
     */
    public function verify(VerifyRegistrationRequest $request): JsonResponse
    {
        ['attemptId' => $attemptId, 'otpCode' => $otpCode] = $request->validated();

        // ChallengeException is rendered as 422 by bootstrap/app.php, so there is
        // nothing to catch here.
        $user = $this->registration->verify($attemptId, $otpCode);

        return UserResource::make($user)->response();
    }

    /**
     * POST /api/v1/auth/register/resend
     *
     * Always 202, whether or not the attempt exists (spec 01 section 3.3).
     */
    public function resend(ResendRegistrationRequest $request): JsonResponse
    {
        // ResendCooldownException is rendered as 429 with a Retry-After header.
        $this->registration->resend($request->validated('attemptId'));

        return response()->json([
            'data' => ['message' => 'If the attempt is still pending, a new code has been sent.'],
        ], Response::HTTP_ACCEPTED);
    }
}
