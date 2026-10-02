# Feature: Password Reset

## 1. Overview

* **Purpose:** Let a user securely regain access after forgetting a password.
* **Authentication:** Unauthenticated flow until password update; user session is invalidated or rotated after a successful reset.
* **Configuration:** Password-reset verification may be enabled independently, as specified by registration requirements.
* **Dependencies:** PostgreSQL, Laravel password hashing, queued mail, shared rate limiting where deployed.

## 2. Technical Contract

### 2.1 Request Reset

* **Endpoint:** `POST /auth/password/forgot`
* **Input:** `email`.
* Always return a generic `202 Accepted` response that does not disclose whether the email exists.
* If the account exists and is eligible, generate a cryptographically random single-use reset token, persist only its hash with an expiry (60 minutes by default), and queue an email.
* Rate-limit by source IP and normalized account identifier. Do not send reset messages for blocked/deleted accounts.

### 2.2 Confirm Reset

* **Endpoint:** `POST /auth/password/reset`
* **Input:** `email`, `token`, `password`, `passwordConfirmation`.
* Validate token hash, account association, expiry, single-use status, and password policy.
* Within a database transaction, update the hashed password, consume the reset token, and record the password-change timestamp.
* Invalidate the user's existing sessions where supported by Laravel's session/auth infrastructure. Require a new login unless product policy explicitly chooses to authenticate the user after reset.

## 3. Error Handling

* `202 Accepted`: Generic reset request response regardless of account existence.
* `422 Unprocessable Entity`: Invalid/expired/consumed token or invalid password.
* `429 Too Many Requests`: Reset request/confirmation throttling.

## 4. Audit and Security

Never log passwords or raw tokens. Store only reset-token hashes, enforce expiration and single use, and protect email delivery via the Laravel queue. Notify the user after successful reset without including secrets.
