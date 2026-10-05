# Feature: Admin User List and Search

## 1. Overview

* **Purpose:** Provide administrative user search with deterministic cursor pagination.
* **Authentication:** Laravel session middleware.
* **Authorization:** `users.list.admin` RBAC permission.
* **Dependencies:** PostgreSQL indexes and response resources.

## 2. Technical Contract

### 2.1 List Users

* **Endpoint:** `GET /admin/users`
* **Query parameters:**
  * `cursor` (optional): Opaque pagination cursor.
  * `limit` (default 20, maximum 100).
  * `q` (optional): Search email, ID, and configured name fields.
  * `status` (optional): `active`, `blocked`, or `deleted`.
  * `sort` (default `created_at`): `created_at`, `last_login`, or `email`.
  * `order` (default `desc`): `asc` or `desc`.
* Require `users.list.admin`.
* Validate each sort/filter value against an allowlist; never interpolate user-supplied SQL identifiers.
* Use cursor pagination with a unique tie-breaker for deterministic ordering. Ensure selected sort columns and search predicates have suitable PostgreSQL indexes.

### 2.2 Response

```json
{
  "items": [
    {
      "id": "string",
      "email": "string",
      "photo": "string|null",
      "createdAt": "ISO-8601 string",
      "status": "string",
      "lastLoginAt": "ISO-8601 string|null"
    }
  ],
  "nextCursor": "string|null"
}
```

## 3. Errors and Limits

* `400 Bad Request`: Invalid cursor, limit, sort, order, or filter.
* `401 Unauthorized`: No valid session.
* `403 Forbidden`: Missing `users.list.admin`.
* `429 Too Many Requests`: Administrative search throttling.

## 4. Audit and Security

Log actor ID, filter names, status, and returned row count. Do not log raw `q` text. Serialize only approved fields; never return password hashes, secrets, session data, or internal security state.
