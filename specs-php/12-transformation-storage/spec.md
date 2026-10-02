# Feature: Transformation Result Storage and File Download

## 1. Overview

* **Purpose:** Optionally retain converted output and make it downloadable from transformation history.
* **Authentication:** Laravel session middleware.
* **Storage:** Laravel private filesystem disk / Flysystem abstraction.
* **Initial backend:** Private local disk; optional S3-compatible backend through `league/flysystem-aws-s3-v3`.
* **Retention:** Match the transformation-history retention policy.

## 2. Conversion Request Extension

* **Endpoints:** `POST /convert` and `POST /images/convert` (full paths under `/api/v1`).
* **Input:** Optional multipart `save` boolean, default `false`.
* When `save` is false, record history without a stored-file reference.
* When true, persist the converted result using a server-generated opaque file ID and link it to the history item. Never expose physical paths, bucket keys, or client-controlled filenames as storage identifiers.
* Return the converted response without waiting on a slow object-store write. Buffer or stage output as necessary; queue persistence after the history record is committed. If persistence fails, report/log that failure and keep the API/history state consistent; do not claim the file was saved.
* The successful conversion response remains streamed. The `save` flag does not silently turn conversion into an asynchronous `202` job.

## 3. Download Endpoints

### 3.1 Self Download

* **Endpoint:** `GET /transformations/history/{itemId}/download` (full path `/api/v1/transformations/history/{itemId}/download`).
* Fetch the history item scoped to the authenticated user's ID; require a non-expired stored-file reference.

### 3.2 Administrative Download

* **Endpoint:** `GET /admin/users/{userId}/transformations/history/{itemId}/download`
* Requires `transformations.history.admin`.
* Verify target user exists, and fetch the history item using both `itemId` and `userId`.

### 3.3 Download Response

Return a streamed/download response with MIME type derived from the stored target format and a safe generated filename. Storage references remain private. Authorize every download; no public disk URLs.

## 4. Retention and Cleanup

* Store creation and expiration timestamps with the history record/reference.
* A scheduled, bounded cleanup process removes expired files and related records consistently.
* Cleanup jobs MUST be retryable and idempotent. Do not delete the history row before recording enough information to retry physical-file deletion.
* Use queue jobs for asynchronous storage persistence and pruning, and protect scheduled execution against overlapping/multi-instance runs.

## 5. Errors and Audit

* `401 Unauthorized`: No valid session.
* `403 Forbidden`: IDOR attempt or missing administrative permission.
* `404 Not Found`: History item, target user, or saved file is missing/expired.
* `410 Gone`: Optional explicit response for known expired artifacts.
* `500 Internal Server Error`: Storage provider failure.

Log actor/user ID, transformation ID, opaque file ID, action, status, size, and duration. Never log binary content, raw storage paths, bucket keys, or signed URLs.

## 6. Security Requirements

* Store user-generated outputs on a private disk.
* Keep storage behind a Laravel filesystem abstraction so local, MinIO, and S3 backends can be changed without changing conversion domain logic.
* Do not trust original filenames; sanitize any user-visible download filename.
* Enforce ownership and RBAC in every download request. Short-lived signed URLs are optional only if their expiration and authorization model are explicitly defined.
