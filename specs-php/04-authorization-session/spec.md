# Feature: Session Authorization and Cookie Handling

## 1. Overview

This feature is governed by `specs-php/authorization.md`, which is authoritative and supersedes the JWT-specific design in the original Node specification.

* **Purpose:** Authenticate browser/first-party API requests with Laravel's server-side session guard.
* **Session backend initially:** PostgreSQL through Laravel's database session driver. The optional migration to Redis is specified in `../14-redis-session-storage/spec.md`.
* **Prohibited:** JWT access/refresh tokens, refresh endpoints, token rotation, persistent JWT identifiers, token allowlists, and token denylists.

## 2. Session Lifecycle

### 2.1 Login

After successful credential or verification flow:

1. Authenticate the eligible user with Laravel.
2. Regenerate the session ID to prevent session fixation.
3. Return a safe user response; do not expose the session identifier.

### 2.2 Protected Requests

* Use Laravel's session authentication middleware and authenticated request context (`$request->user()`).
* Confirm the user's current account status still allows access.
* Return `401 Unauthorized` when no valid session exists; return `403 Forbidden` for an authenticated but unauthorized request.
* Do not parse session cookies in feature controllers or application services.

### 2.3 Logout and Session Invalidation

* `POST /auth/logout` logs out the user, invalidates the current server-side session, and rotates the CSRF token.
* Blocking or deleting an account MUST prevent subsequent protected access. Invalidate the user's other sessions when required by account-deletion and security policy.
* Password reset/change SHOULD invalidate other sessions according to the password-reset specification.

## 3. Cookie and CSRF Requirements

* Configure session cookies as `HttpOnly`; set `Secure` in production; configure `SameSite` (`Lax` by default unless deployment requires `Strict`); set an appropriate path and lifetime.
* Keep Laravel CSRF middleware enabled for state-changing cookie-authenticated browser requests.
* `HttpOnly` does not prevent CSRF. A first-party SPA may use Laravel Sanctum's stateful SPA/CSRF facilities if appropriate; Sanctum personal access tokens are not this session architecture.

## 4. Storage and Scalability

* Use Laravel's supported database session driver with PostgreSQL initially.
* Authentication and authorization business logic MUST NOT access the session table directly or depend on Redis.
* Redis MAY be introduced as a Laravel-supported session backend in the future without changing business logic. Until the migration is explicitly adopted, PostgreSQL remains the session backend.

## 5. Error Handling and Logging

* `401 Unauthorized`: Missing, invalid, or expired authenticated session.
* `403 Forbidden`: Authenticated identity lacks authorization.
* Never log session IDs, cookies, credentials, CSRF tokens, or verification secrets.
