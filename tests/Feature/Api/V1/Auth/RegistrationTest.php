<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\UserStatus;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Spec: specs-php/01-registration/spec.md
 *
 * Two tests are written out in full to set the pattern; the rest are named and
 * left for you. Delete the markTestIncomplete() line as you fill each one in.
 */
class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Verification is off by default; the tests that need it turn it on.
        config()->set('verification.registration.enabled', false);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'ada@example.com',
            'password' => 'Str0ng-Passw0rd!',
        ], $overrides);
    }

    // --- Verification disabled -------------------------------------------------

    public function test_it_creates_an_active_user_and_returns_201(): void
    {
        $response = $this->postJson('/api/v1/auth/register', $this->validPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('data.email', 'ada@example.com')
            ->assertJsonPath('data.status', UserStatus::Active->value)
            ->assertJsonStructure(['data' => ['id', 'email', 'status', 'createdAt']]);

        $user = User::firstWhere('email', 'ada@example.com');

        $this->assertNotNull($user);
        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotSame('Str0ng-Passw0rd!', $user->password, 'The password must be hashed.');
    }

    public function test_it_does_not_establish_a_session_after_registration(): void
    {
        $this->postJson('/api/v1/auth/register', $this->validPayload())->assertCreated();

        // Spec 01 section 3.1: no silent login unless explicitly configured.
        $this->assertGuest();
    }

    public function test_it_normalizes_the_email_before_storing_it(): void
    {
        // Given '  ADA@Example.COM  ', the stored email is 'ada@example.com'.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_409_for_an_already_registered_email(): void
    {
        // Register once, then again with a differently-cased version of the same
        // address. Expect 409 and error.code = EMAIL_ALREADY_REGISTERED.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_ignores_client_supplied_status_and_role_fields(): void
    {
        // Post status/role/id alongside the valid payload and assert the created
        // user is unaffected (spec 01 section 3.1).
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_422_for_a_password_that_fails_the_policy(): void
    {
        // Assert the shared error shape: error.code = VALIDATION_FAILED and
        // error.details.password present.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_422_for_a_malformed_email(): void
    {
        $this->markTestIncomplete('TODO');
    }

    // --- Verification enabled --------------------------------------------------

    public function test_it_creates_a_pending_user_and_returns_202_with_an_attempt_id(): void
    {
        config()->set('verification.registration.enabled', true);
        Mail::fake();

        // Expect 202, data.verificationRequired === true, a uuid data.attemptId,
        // a user with status pending, one VerificationCodeMail queued, and a
        // challenge row whose code_hash is NOT the plaintext code.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_activates_the_account_on_a_correct_code(): void
    {
        // Register, read the code from the queued mailable, post it to
        // /auth/register/verify, assert 200, status active, and that the
        // challenge row is consumed.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_422_for_an_incorrect_code_and_counts_the_attempt(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_422_after_five_failed_attempts(): void
    {
        // error.code = CHALLENGE_ATTEMPTS_EXHAUSTED; the sixth try must fail even
        // if the code is right.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_422_for_an_expired_code(): void
    {
        // $this->travel(11)->minutes();
        $this->markTestIncomplete('TODO');
    }

    public function test_it_rejects_a_code_that_was_already_used(): void
    {
        // Single use: the second verify with the same code must fail.
        $this->markTestIncomplete('TODO');
    }

    // --- Resend ----------------------------------------------------------------

    public function test_it_sends_a_new_code_and_invalidates_the_previous_one(): void
    {
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_429_inside_the_resend_cooldown(): void
    {
        // Expect a Retry-After header.
        $this->markTestIncomplete('TODO');
    }

    public function test_it_returns_202_for_an_unknown_attempt_id(): void
    {
        // Enumeration resistance: an unknown attemptId looks exactly like a
        // pending one (spec 01 section 3.3).
        $this->markTestIncomplete('TODO');
    }

    // --- Throttling ------------------------------------------------------------

    public function test_it_returns_429_when_the_registration_rate_limit_is_exceeded(): void
    {
        // Remember that RateLimiter uses the cache store, which phpunit.xml sets
        // to "array" - so limits reset between tests on their own.
        $this->markTestIncomplete('TODO');
    }
}
