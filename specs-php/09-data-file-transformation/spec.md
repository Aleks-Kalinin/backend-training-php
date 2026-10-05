# Feature: Data File Transformation (CSV, JSON, XML, YAML)

## 1. Overview

* **Purpose:** Convert structured data files between CSV, JSON, XML, and YAML.
* **Target Role:** Authenticated user.
* **Authentication:** Laravel session cookie and CSRF protection for the multipart POST.
* **Libraries:** PHP JSON functions; `league/csv` 9.x (or native CSV functions with explicit escape configuration); `symfony/yaml`; PHP DOM/XMLReader/XMLWriter extensions.
* **Execution:** Bounded synchronous request with conversion isolated in a constrained PHP CLI worker process.

## 2. Supported Conversion Matrix

All 12 non-identity directional conversions are supported:

* CSV ↔ JSON, CSV ↔ XML, CSV ↔ YAML.
* JSON ↔ CSV, JSON ↔ XML, JSON ↔ YAML.
* XML ↔ JSON, XML ↔ CSV, XML ↔ YAML.
* YAML ↔ JSON, YAML ↔ CSV, YAML ↔ XML.

## 3. Technical Contract

### 3.1 Convert File

* **Endpoint:** `POST /convert` (full path `/api/v1/convert`).
* **Content type:** `multipart/form-data`.
* **Input:** `file` (required), `targetFormat` (`csv`, `json`, `xml`, `yaml`), optional `save` boolean as defined in transformation storage.
* Detect source format using actual content, MIME type, and extension; do not trust the filename or client MIME type alone.
* Enforce source-format-specific configurable byte limits before parsing.
* Handle UTF-8 BOM and reject invalid encodings unless an explicit supported BOM/encoding policy is implemented. Normalize supported text to UTF-8.
* Validate syntax and structural mapping; disallow unsupported source/target identity pair according to the formats endpoint contract.
* Convert using a normalized internal data representation while preserving scalar types where formats permit.
* Return a streamed download with matching MIME type and safe generated filename `converted.<ext>`.
* Enforce a hard conversion timeout (30 seconds by default), bounded memory, and worker process limits. On timeout, terminate the worker and return an error.

### 3.2 Formats Discovery

* **Endpoint:** `GET /convert/formats` (full path `/api/v1/convert/formats`).
* Authenticated users only.

```json
[
  { "source": "csv", "target": ["json", "xml", "yaml"] },
  { "source": "json", "target": ["csv", "xml", "yaml"] },
  { "source": "xml", "target": ["json", "csv", "yaml"] },
  { "source": "yaml", "target": ["json", "csv", "xml"] }
]
```

## 4. Mapping and Format Rules

* **CSV → data structure:** First row is headers; subsequent rows become objects keyed by those headers.
* **Data structure → CSV:** Require a list of objects or a single object; produce a header row. Reject incompatible nested/primitive values with a validation error or a clearly documented deterministic encoding.
* **XML → data structure:** Attributes use `@attributeKey`; repeated tags become arrays; define stable behavior for text nodes, namespaces, and empty elements.
* **Data structure → XML:** Default root is `<root>`; array items use `<item>`; escape text and attributes correctly.
* YAML parsing MUST not instantiate PHP objects or evaluate tags/constructors from untrusted input.
* XML MUST reject DTDs and external entities; disable network access/entity substitution and do not enable DTD loading.

## 5. Errors

* `400 Bad Request`: Invalid parameters, syntax, encoding, or structure.
* `401 Unauthorized`: No valid session.
* `413 Payload Too Large`: Format-specific or global request limit exceeded.
* `415 Unsupported Media Type`: Unknown or unsupported source format.
* `429 Too Many Requests`: Conversion limit exceeded.
* `500 Internal Server Error` or `504 Gateway Timeout`: Worker failure or timeout; return a stable public error without parser internals.

## 6. Audit and Security

Log user ID, source/target formats, byte size, duration, status, and safe error code. Never log file contents or raw parser exceptions that may include input fragments. Test XXE, parser expansion, malformed CSV/YAML, oversized input, and worker timeout cases.
