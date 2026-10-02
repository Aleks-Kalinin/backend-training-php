# Feature: User Profile View

## 1. Overview

* **Purpose:** Return user profile fields according to self-access and explicit administrative field policies.
* **Authentication:** Laravel session middleware.
* **Authorization:** Laravel policies/Gates backed by RBAC.

## 2. Technical Contract

### 2.1 Retrieve Profile

* **Endpoint:** `GET /users/{userId}`
* **Access rules:**
  * Self-access is allowed when the authenticated user ID equals `userId`.
  * Access to another user's profile requires `users.read`.
* **Execution:**
  1. Authenticate and verify the target user exists.
  2. Authorize self access or `users.read`.
  3. Return a server-constructed response resource, not an unrestricted Eloquent model serialization.
  4. Self responses may include the user's permitted profile data.
  5. Administrative/support responses include only fields explicitly permitted by the RBAC field policy. All other fields are omitted.

## 3. Response and Errors

* `200 OK`: Safe, role-filtered profile response.
* `401 Unauthorized`: No valid authenticated session.
* `403 Forbidden`: Caller is neither the target user nor authorized to read that profile.
* `404 Not Found`: Target user does not exist.
* `429 Too Many Requests`: Read limit exceeded.

## 4. Audit and Security

Log viewer ID, target ID, and response status, but not profile values. Scope database access and authorization to prevent IDOR. New profile fields MUST remain hidden to administrative readers until explicitly allowlisted.
