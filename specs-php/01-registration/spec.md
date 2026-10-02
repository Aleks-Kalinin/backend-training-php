# Feature: User Registration

## 1. Overview

* **Purpose:** Create an account using an email address and password.
* **Target Role:** Guest.
* **Authentication:** Laravel server-side session, as specified in `../authorization.md`.
* **Dependencies:** PostgreSQL, Laravel password hashing and validation, queued email delivery.
* **Configurable verification:** Registration, password reset, and login verification can be enabled independently.

## 2. User Stories and Scenarios

* **Verification disabled:** Create an active user; the user can log in immediately.
* **Verification enabled:** Create a pending user and send an OTP or magic link; activate the account only after successful confirmation.

## 3. Technical Contract

### 3.1 Registration

* **Endpoint:** `POST /auth/register`
* **Input:** `email` (required, valid email), `password` (required).
* **Validation:** Enforce configured password policy and unique normalized email. Do not accept role, status, or privilege fields from the client.
* **Execution:**
  1. Validate input and apply IP/email rate limits.
  2. Hash the password using Laravel's configured password hasher.
  3. If registration verification is disabled, create an active account and return `201 Created`. Do not silently log in unless that behavior is explicitly enabled by application configuration.
  4. If enabled, create a pending account and single-use verification challenge, queue the email, and return `202 Accepted` with `verificationRequired: true` and an opaque `attemptId`.
* **Verification methods:** 6-digit OTP (10-minute expiry, maximum 5 failed attempts, 60-second resend cooldown) or a cryptographically random single-use magic link with a configured expiry.
* Persist only a hash of OTPs and magic-link tokens. Consume the challenge atomically on success.

### 3.2 Registration Verification

* **Endpoint:** `POST /auth/register/verify`
* **Input:** `attemptId` and either `otpCode` or a verification `token`.
* A successful verification activates the account and consumes the challenge. It does not establish a session unless the API is explicitly configured to log the user in after registration.

### 3.3 Resend

* **Endpoint:** `POST /auth/register/resend`
* Require the pending attempt identifier; enforce resend cooldown and per-IP/account limits. Return a generic response where necessary to prevent account enumeration.

## 4. Error Handling

* `400 Bad Request`: Malformed request structure.
* `409 Conflict`: Duplicate email where enumeration policy permits; otherwise return a generic response.
* `422 Unprocessable Entity`: Invalid, expired, or consumed verification challenge; password policy validation errors.
* `429 Too Many Requests`: Registration, verification, or resend throttling.

## 5. Audit and Logging

Log registration and verification outcomes and dispatch status without recording passwords, OTP values, tokens, or unnecessary PII.

## 6. Security Requirements

* Use Laravel validation, password hashing, and mail/queue facilities.
* Do not trust client-provided account state or roles.
* Enforce enumeration resistance and rate limits consistently.
