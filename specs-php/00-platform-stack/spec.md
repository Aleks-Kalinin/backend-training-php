# Platform Specification: PHP and Laravel Stack

## 1. Purpose

Define the platform and library choices for the PHP recreation of the file-conversion application. Feature-specific requirements are defined in numbered directories alongside this document.

## 2. Runtime and Framework

* **PHP:** PHP 8.5.
* **Framework:** Laravel 13.
* **Database:** PostgreSQL, accessed through Laravel Eloquent and database migrations.
* **Cache, queues, and distributed rate limits:** Redis in shared environments.
* **Authentication sessions:** Laravel's database session driver backed by PostgreSQL for the initial implementation. A later Redis session-store migration is described in `../14-redis-session-storage/spec.md`; Redis MUST NOT be required for the initial implementation.
* **Web runtime:** Nginx with PHP-FPM. Run queue workers as separately supervised processes.

Use Laravel's first-party facilities before introducing additional packages: request validation, Eloquent, policies/gates, mail, queues, scheduler, filesystem, rate limiter, and streamed HTTP responses.

## 3. Proposed Composer Libraries and System Dependencies

| Capability | Choice | Notes |
|---|---|---|
| CSV parsing and writing | `league/csv` 9.x, when native `fgetcsv`/`fputcsv` do not meet the implementation needs | Explicitly configure CSV delimiters, enclosure, and escape behavior. If native CSV functions are used on PHP 8.5, pass the escape argument explicitly. |
| YAML parsing and writing | `symfony/yaml` | Parse untrusted input without enabling PHP object or constant parsing. |
| XML parsing and writing | PHP DOM, XMLReader, and XMLWriter extensions | Use XMLReader for large inputs where practical. Disable DTD loading and external entity resolution; do not enable entity substitution. |
| Image decoding and encoding | `intervention/image` 3.x | Use an explicitly selected driver (Imagick or GD); validate supported input/output formats and impose resource limits. |
| SVG sanitization | `enshrined/svg-sanitize` | Defense in depth only; sanitization does not replace disabling external resources and isolating the rasterizer. |
| SVG rasterization | ImageMagick with SVG delegate support, or a separately maintained SVG renderer | Installation and secure policy configuration are deployment requirements, not Composer-only dependencies. Reject SVG-to-SVG and raster-to-SVG conversion. |
| Object storage | Laravel Filesystem / Flysystem; `league/flysystem-aws-s3-v3` when S3-compatible storage is enabled | Start with a private local disk; MinIO and S3 can use the S3-compatible adapter. |
| Conversion process isolation | Dedicated PHP CLI worker process, invoked/managed using Symfony Process if needed | Synchronous HTTP conversions must have a hard timeout and bounded memory/CPU. Do not rely on PHP-FPM request concurrency as a CPU-isolation strategy. |

Pin compatible package versions through Composer and verify PHP extension and ImageMagick delegate requirements in the deployment image. Do not add an image driver or S3 adapter until that backend is selected.

## 4. Process and Workload Boundaries

* Keep conversion requests synchronous and stream a converted result for the current API contract, subject to strict upload, execution-time, memory, and pixel limits.
* Run CPU-heavy parsing/rasterization in an isolated worker process. The worker MUST have a hard timeout and resource limits; terminate it on timeout.
* Use Laravel queue jobs for email delivery, asynchronous account deletion, optional saved-file persistence, and retention cleanup.
* If conversion workload cannot meet synchronous limits, introduce an explicit asynchronous API contract (`202 Accepted`, job status and download) rather than silently changing the existing `200` streamed response.
* Queue jobs that depend on committed database records MUST be dispatched after the relevant transaction commits.
* Use Redis-backed cache invalidation, queues, and rate limiting consistently across application instances. PostgreSQL-backed cache/queue options MAY be used for local development or fallback, but must not become process-local in a multi-instance deployment.

## 5. API Conventions

* JSON APIs use the `/api/v1` base path; the endpoint paths in feature specs are suffixes beneath this base unless expressly identified as a browser callback.
* Use Laravel Form Requests or equivalent request validation and return a consistent JSON validation/error shape.
* Preserve the specified HTTP statuses and streamed download headers.
* Store timestamps in UTC and serialize them as ISO-8601.
* Use UUID identifiers for user, history, job, and file records unless an existing migration decision specifies otherwise.

## 6. Cross-Cutting Security and Operations

* Enforce HTTPS in deployed environments. Session cookies MUST be `HttpOnly`, `Secure` in production, and use the configured `SameSite` policy. Keep Laravel CSRF protection enabled for cookie-authenticated state-changing requests.
* Validate actual file content in addition to extensions and client-supplied MIME types. Never use user filenames as storage paths.
* Do not log file contents, credentials, session IDs, OTPs, magic-link tokens, or raw search text that may contain PII.
* Apply request-size limits at both the reverse proxy and application layers.
* Expose health checks for the HTTP service, PostgreSQL, Redis (where enabled), and queue workers.
* Test conversion parsers and media workers with malformed, oversized, and adversarial fixtures, including XML XXE cases and image decompression bombs.

## 7. Authentication Decision

`specs-php/authorization.md` is the authoritative authentication architecture. It intentionally replaces the original JWT-cookie design with Laravel server-side sessions. In particular:

* Do not implement JWT access or refresh tokens or `/auth/refresh`.
* Store initial Laravel sessions in PostgreSQL.
* Redis may be used for queues, shared cache, and distributed rate limiting, but authentication business logic MUST NOT depend directly on Redis.
* Regenerate the session ID after login and invalidate the server-side session on logout.
* Apply Laravel session authentication and CSRF middleware to browser/first-party cookie-authenticated routes.
* The optional Redis session-store improvement is defined in `../14-redis-session-storage/spec.md`; it does not change the session-guard architecture or make Redis a requirement before that improvement is adopted.

## 8. Reference Documentation

* [Laravel 13 release notes](https://laravel.com/docs/13.x/releases)
* [Laravel authentication](https://laravel.com/docs/13.x/authentication)
* [Laravel queues](https://laravel.com/docs/13.x/queues)
* [Laravel cache](https://laravel.com/docs/13.x/cache)
* [Laravel filesystem](https://laravel.com/docs/13.x/filesystem)
* [Laravel streamed responses](https://laravel.com/docs/13.x/responses#streamed-responses)
* [League CSV](https://csv.thephpleague.com/9.0/)
* [Symfony YAML](https://symfony.com/doc/current/components/yaml.html)
* [Intervention Image](https://image.intervention.io/v3)
