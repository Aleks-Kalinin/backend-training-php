# Feature: Transformation History

## 1. Overview

* **Purpose:** Retrieve a user's history of data and image transformations.
* **Authentication:** Laravel session middleware.
* **Persistence:** PostgreSQL.
* **Pagination:** Cursor-based with deterministic ordering.

## 2. Technical Contract

### 2.1 Own History

* **Endpoint:** `GET /transformations/history` (full path `/api/v1/transformations/history`).
* Authenticated users see only records whose `user_id` equals their own ID.
* Query parameters:
  * `cursor` optional opaque cursor.
  * `limit` default 20, maximum 100.
  * `type`: `file` or `image`.
  * `sourceFormat`, `targetFormat`.
  * `status`: `success` or `error`.
  * `createdAtFrom`, `createdAtTo`: ISO-8601 bounds.
* Validate ranges and filter values. Sort by `created_at DESC, id DESC` and use a stable cursor containing both ordering values.

### 2.2 Administrative History

* **Endpoint:** `GET /admin/users/{userId}/transformations/history`
* Requires `transformations.history.admin`.
* Verify target user existence and scope the query to that user ID.
* Supports the same filters, ordering, and response shape as own history.

### 2.3 Response

```json
{
  "items": [
    {
      "id": "string",
      "type": "file|image",
      "sourceFormat": "string",
      "targetFormat": "string",
      "status": "success|error",
      "fileSize": 1024,
      "durationMs": 145,
      "errorCode": "INVALID_SYNTAX",
      "createdAt": "ISO-8601 string"
    }
  ],
  "nextCursor": "string|null"
}
```

## 3. Persistence and Retention

Store user ID, type, formats, result status, input file size, duration, safe error code, optional stored-file reference, and timestamps. Never store binary content in history.

Create indexes appropriate to the queries, including `(user_id, created_at, id)` and supporting filter indexes for type/status where query volume justifies them. Apply an admin-configurable retention period (90 days by default) with bounded scheduled cleanup; coordinate deletion with associated stored files.

## 4. Errors, Audit, and Security

* `400 Bad Request`: Invalid cursor, limit, or filter.
* `401 Unauthorized`: No valid session.
* `403 Forbidden`: Missing admin permission or out-of-scope access.
* `404 Not Found`: Admin target user does not exist.
* `429 Too Many Requests`: History query throttling.

Log actor ID, optional target user ID, filter names, status, and returned item count. Do not log raw files or PII-bearing search data. Enforce ownership in the database query, not only after fetching results.
