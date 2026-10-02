# Feature: User Account Deletion and Anonymization

## 1. Overview

* **Purpose:** Delete or anonymize user data under controlled self-service or administrative authorization.
* **Authentication:** Laravel session middleware.
* **Dependencies:** RBAC, PostgreSQL, queued jobs, private filesystem storage, verification challenges.

## 2. Technical Contract

### 2.1 Deletion Request

* **Endpoint:** `DELETE /users/{userId}`
* **Input:** Optional `reason`; self-service requests also require a valid email verification challenge.
* **Self deletion:** Allowed only for the authenticated user's own ID and MUST require OTP or magic-link confirmation regardless of registration/login verification settings.
* **Administrative deletion:** Requires `users.delete`; does not require user email confirmation.
* Mark the account as `deleting` and block future authentication before dispatching slow cleanup.
* Invalidate the caller's session immediately for self deletion. Invalidate all sessions for the target account when account status changes to deleting/blocked, using Laravel's supported session management approach; do not manually depend on session table schema in domain services.
* Queue PII anonymization, uploaded-asset deletion, and domain-specific cleanup when asynchronous execution is selected. Preserve records subject to retention/legal requirements.
* Return `202 Accepted` with `{ jobId, status: "pending" }` for queued deletion; return `204 No Content` only when synchronous deletion is complete.

### 2.2 Deletion Status

* **Endpoint:** `GET /users/{userId}/deletion-status`
* User may inspect their own deletion; an administrator requires appropriate user-management permission.
* Return `{ status: "pending" | "in_progress" | "done" | "failed" }`.

## 3. Errors and Idempotency

* `401 Unauthorized`: No valid session.
* `403 Forbidden`: IDOR, missing admin permission, or self deletion not confirmed.
* `404 Not Found`: Target user/job does not exist.
* `409 Conflict`: Incompatible deletion already in progress; repeat completed deletion requests return a deterministic response.
* `429 Too Many Requests`: Deletion throttling.
* Deletion jobs MUST be idempotent and safe to retry.

## 4. Audit and Security

Log actor/target IDs, self/admin operation type, job ID, status, and timestamps. Never log deletion reason if it may contain PII, deleted values, verification secrets, or storage paths. Do not claim revocation is complete until session invalidation and account-state checks prevent reuse.
