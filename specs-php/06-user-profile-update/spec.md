# Feature: User Profile Update

## 1. Overview

* **Purpose:** Update permitted profile fields with role-aware field allowlists.
* **Authentication:** Laravel session middleware.
* **Authorization:** Self policy or `users.update` RBAC permission.
* **Dependencies:** PostgreSQL transactions, queued email, private file storage for profile photos.

## 2. Technical Contract

### 2.1 Profile Patch

* **Endpoint:** `PATCH /users/{userId}`
* **Input:** JSON object of fields to update.
* Validate field types and constraints. Reject unrecognized fields.
* A user updating their own profile MUST NOT directly patch `email`; return `403 Forbidden`.
* An administrator with `users.update` may update fields expressly permitted to administrators, including email, subject to uniqueness and validation.
* Enforce default-deny writable field allowlists; never mass-assign request data directly to an Eloquent model.
* Apply each update transactionally and return a filtered profile resource.

### 2.2 Initiate Self Email Change

* **Endpoint:** `POST /users/{userId}/email-change`
* **Input:** `newEmail`, optional `method` (`otp` or `magic_link`).
* Only the authenticated user themself may initiate.
* Validate email and uniqueness; create a 10-minute challenge; queue verification delivery to the proposed address.
* OTP is 6 digits with a 60-second resend cooldown and maximum 5 attempts. Persist only a secure hash. A magic-link token is random, single-use, expires after 10 minutes, and is stored only as a hash.
* Return `200 OK` with `requiresConfirmation: true` and an opaque `challengeId`.

### 2.3 Confirm Email Change

* **Endpoint:** `POST /users/{userId}/email-change/confirm`
* **Input:** OTP `{ challengeId, code }` or magic link `{ token }`.
* Verify caller identity, challenge ownership, expiry, attempt count, and current email uniqueness.
* In one transaction, update the email and consume the challenge. Return `200 OK`.

## 3. Errors

* `400 Bad Request`: Invalid structure or fields.
* `401 Unauthorized`: No valid session.
* `403 Forbidden`: IDOR, forbidden field, direct self email patch, or insufficient permission.
* `404 Not Found`: User or challenge not found.
* `409 Conflict`: Email already in use.
* `422 Unprocessable Entity`: Invalid/expired challenge or attempts exhausted.
* `429 Too Many Requests`: Update or verification throttling.

## 4. Audit and Security

Log actor, target, changed field names, and status; never log field values, OTPs, tokens, or raw cookies. Queue email notifications. Use explicit response resources and allowlists for returned fields.
