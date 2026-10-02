# Improvement: Redis-Backed Laravel Authentication Sessions

## 1. Overview

* **Purpose:** Replace PostgreSQL-backed Laravel session storage with Redis to reduce database session read/write load and support a shared, low-latency session store across application instances.
* **Prerequisite:** The initial session implementation described in `../authorization.md` uses PostgreSQL. This improvement is optional and is not required for the initial release.
* **Scope:** Change Laravel's session storage backend only. Keep Laravel's session guard, cookie contract, login/verification flows, CSRF protection, and authorization/RBAC behavior unchanged.
* **Redis roles:** Redis may also be used for queues, cache, and distributed rate limits. Session keys MUST be logically isolated from other Redis data.

## 2. Goals and Non-Goals

### Goals

* Store Laravel-managed session payloads in a shared Redis deployment.
* Retain the existing session cookie name, security attributes, and configured session lifetime unless a separately reviewed change is approved.
* Support multiple web application instances using the same session store.
* Preserve logout and account-security behavior using Laravel's supported session/authentication facilities.
* Ensure failure modes, deployment, monitoring, and rollback are explicit.

### Non-Goals

* Do not introduce JWT access or refresh tokens.
* Do not implement custom Redis commands or access Redis directly from controllers/domain services for session operations.
* Do not change session-based authentication into a stateless design.
* Do not use Redis as the source of truth for users, roles, permissions, or verification challenges.
* Do not claim that changing a user's account status automatically deletes all sessions unless the chosen Laravel-supported implementation demonstrably enforces that behavior.

## 3. Technical Design

* Configure Laravel's supported Redis session driver using framework configuration/environment settings; do not build a custom session handler unless a documented framework limitation requires it.
* Configure all application instances to use the same Redis service, session connection, key prefix, serialization/configuration, and session cookie settings.
* Use a dedicated Redis logical database or dedicated deployment where practical, plus a unique key prefix for this application and environment. Session keys MUST NOT collide with queue, cache, or other applications' keys.
* Require Redis authentication and encrypted transport where supported by the hosting environment. Restrict network access to application/worker subnets and use least-privilege Redis ACLs.
* Set Redis key expiration consistently with the configured Laravel session lifetime. Configure Redis memory limits and an eviction policy that does not unpredictably evict active authentication sessions; monitor memory headroom.
* Keep secrets and connection configuration in environment/secret management, not in source control.
* Continue using Laravel's session APIs for session creation, regeneration, invalidation, and authentication. Business logic MUST NOT depend on Redis key names or serialized session representation.

## 4. Session Security and Lifecycle

* Preserve session-ID regeneration after successful login and verification.
* Preserve logout behavior: invalidate the current server-side session and regenerate the CSRF token as appropriate.
* Continue enforcing current account status on protected requests. Blocking/deleting an account MUST prevent later authenticated requests even if a session key remains in Redis.
* Where policy requires immediate invalidation of all sessions belonging to a user, implement and test that behavior through Laravel-supported session/authentication mechanisms. Document any required user session version or session index; do not scan arbitrary Redis keys or rely on private Laravel key formats.
* Password reset/change SHOULD invalidate other sessions according to `13-password-reset/spec.md` and the authentication policy.
* Session cookies remain `HttpOnly`, `Secure` in production, and configured with the selected `SameSite`, path, and expiration. CSRF protection remains enabled.
* Redis session contents MUST NOT be logged or exposed in diagnostics. Do not store unnecessary secrets or personal data in session payloads.

## 5. Availability and Failure Behavior

* Redis becomes a runtime dependency for authenticated requests after this improvement is enabled. A Redis outage may make session-backed routes unavailable; the application MUST fail closed and MUST NOT silently treat users as unauthenticated guests for protected actions or bypass authentication.
* Return the application's standard service-unavailable response (`503 Service Unavailable`) when session storage cannot be reached, where the failure can be safely classified and handled. Do not reveal Redis addresses, credentials, or exception internals.
* Define Redis high availability, backup/persistence, capacity, and recovery objectives for the target environment. Redis data loss may invalidate existing sessions; users must be required to log in again rather than restoring untrusted/stale authentication state.
* PostgreSQL remains the system of record and continues storing application data. Session-store errors MUST NOT result in user/role/permission writes being skipped or reported as successful.

## 6. Migration and Rollback

* Provision and secure Redis, configure Laravel's Redis client/connection, set a unique application/environment prefix, and test connectivity before switching the session driver.
* Deploy a coordinated configuration change so all web instances use the same session backend. Mixed PostgreSQL- and Redis-backed application instances do not share sessions and MUST NOT be used during ordinary rolling deployment unless traffic is explicitly drained/routed to avoid cross-backend session requests.
* At cutover, existing PostgreSQL sessions are not automatically present in Redis. The default migration behavior is to invalidate existing sessions and require users to log in again. Do not attempt to copy Laravel's internal serialized session rows unless a separately designed, tested, and security-reviewed migration is approved.
* Keep the PostgreSQL session table and configuration available during a defined rollback window, but do not run instances against different session stores concurrently for the same browser traffic.
* Rollback consists of restoring the PostgreSQL session driver consistently across all web instances. Redis-created sessions will not exist in PostgreSQL, so rollback may require users to log in again.
* Document the chosen cutover window, expected reauthentication impact, configuration rollback procedure, and operator ownership before production adoption.

## 7. Operations and Observability

* Add readiness/dependency checks for Redis session connectivity. Separate liveness from readiness so transient Redis failures do not create restart loops.
* Monitor Redis availability, command latency, connection count, memory/evictions, rejected connections, and session-related 5xx responses.
* Alert on authentication/session failures and Redis memory pressure without including cookie values, session IDs, or session payloads.
* Ensure queue/cache key usage and session TTLs are included in capacity planning; use separate Redis instances when workload isolation or security requirements demand it.

## 8. Acceptance Criteria

1. Login and verification create a Laravel session in Redis, and subsequent requests to any application instance authenticate the same user.
2. Session IDs regenerate after login; old identifiers no longer authenticate.
3. Logout invalidates the current session across application instances.
4. Expired sessions stop authenticating after the configured lifetime.
5. Account blocking prevents further protected access according to the account-status check, independent of session key expiry.
6. CSRF protection and secure cookie settings remain enabled.
7. Redis failure does not bypass authentication and is surfaced as a safe service failure.
8. Existing PostgreSQL sessions are handled according to the documented cutover policy; no unsafe or implicit migration occurs.
9. Automated tests cover cross-instance persistence semantics, regeneration, logout, expiration, and Redis-unavailable behavior using the project's supported test approach.

## 9. Reference Documentation

* [Laravel session configuration](https://laravel.com/docs/13.x/session)
* [Laravel Redis](https://laravel.com/docs/13.x/redis)
* [Laravel authentication](https://laravel.com/docs/13.x/authentication)
* [Laravel deployment](https://laravel.com/docs/13.x/deployment)
