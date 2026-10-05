# Feature: User Authentication (Login)

## 1. Overview

* **Purpose:** Authenticate an existing account using email and password.
* **Authentication mechanism:** Laravel server-side session and session cookie; see `../authorization.md`.
* **Dependencies:** PostgreSQL user store and sessions, Laravel password hashing, Redis-backed rate limits where deployed, queued mail.

## 2. Technical Contract

### 2.1 Login

* **Endpoint:** `POST /auth/login`
* **Input:** `email`, `password`.
* **Execution:**
  1. Validate payload and apply limits keyed by both source IP and a normalized, non-reversible email identifier.
  2. Authenticate using Laravel's authentication facilities and stored password hash.
  3. Reject pending, blocked, deleted, or otherwise ineligible accounts without revealing account existence through a credential-specific response.
  4. If login verification is disabled, authenticate through Laravel, regenerate the session ID, and return `200 OK` with a safe user representation.
  5. If enabled, do not establish an authenticated session yet. Create a pending login attempt, queue the configured OTP or magic-link notification, and return `202 Accepted` with `attemptId` and `verificationRequired: true`.
* **OTP:** 6 digits, 10-minute expiry, maximum 5 failed attempts, 60-second resend cooldown; store only a secure hash.
* **Magic link:** Cryptographically random, single-use, 10-minute expiry, bound to a specific attempt; persist only a secure hash.

### 2.2 Login Verification

* **OTP endpoint:** `POST /auth/login/verify` with `attemptId` and `otpCode`.
* **Magic-link callback:** `GET /auth/confirm?token=...`.
* Atomically validate and consume a challenge. On success, verify the user remains eligible, authenticate through Laravel, regenerate the session ID, and return the authenticated response.
* On failure, do not create an authenticated session.

### 2.3 Logout

* **Endpoint:** `POST /auth/logout`
* Invalidate the server-side Laravel session and regenerate the CSRF token as appropriate. Return `200 OK`.

## 3. Error Handling

* `401 Unauthorized`: Generic invalid credential response.
* `403 Forbidden`: Explicit account state prevents authentication, where policy allows disclosure.
* `422 Unprocessable Entity`: Invalid/expired/consumed verification challenge.
* `429 Too Many Requests`: Login, verification, or resend limit exceeded.

## 4. Audit and Security Requirements

Log outcomes and dispatch events without logging passwords, session identifiers, OTP values, magic-link tokens, or raw cookies. Protect against brute force and account enumeration. Keep CSRF protection enabled for cookie-authenticated state-changing requests.
