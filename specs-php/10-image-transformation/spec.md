# Feature: Image Transformation

## 1. Overview

* **Purpose:** Convert PNG and JPEG images and rasterize SVG into PNG/JPEG.
* **Target Role:** Authenticated user.
* **Authentication:** Laravel session cookie and CSRF protection for multipart POST.
* **Libraries:** `intervention/image` 3.x; `enshrined/svg-sanitize` as one defensive layer; ImageMagick with SVG delegate support or a separately selected renderer for SVG rasterization.
* **Execution:** Dedicated, resource-constrained worker process.

## 2. Supported Conversion Matrix

* PNG → JPEG.
* JPEG → PNG.
* SVG → PNG.
* SVG → JPEG.
* Raster-to-SVG vectorization is not supported.

## 3. Technical Contract

### 3.1 Convert Image

* **Endpoint:** `POST /images/convert` (full path `/api/v1/images/convert`).
* **Content type:** `multipart/form-data`.
* **Input:** `file` (required), `targetFormat` (`png`, `jpeg`, `svg`), optional `options` (`quality`, `width`, `height`, `background`), and optional `save`.
* `quality`: integer 1–100; default 80 for JPEG output.
* `width` and `height`: optional positive integer dimensions, bounded by configured maximums. Define aspect-ratio behavior consistently; default is preserve aspect ratio.
* `background`: validated hex color; default `#ffffff` when transparency must be flattened for JPEG.
* Validate actual decoded image format, dimensions, pixel count, and file size against per-format configuration.
* For SVG, sanitize then render in an isolated process with external resource/network access disabled. Reject script, event-handler, DTD/entity, external URL, and embedded active content. Sanitizer is defense in depth and MUST NOT be treated as the sole security control.
* Return a streamed download with appropriate image MIME type and safe filename.

### 3.2 Formats Discovery

* **Endpoint:** `GET /images/convert/formats` (full path `/api/v1/images/convert/formats`).
* Authenticated users only.

```json
[
  { "source": "png", "target": ["jpeg"] },
  { "source": "jpeg", "target": ["png"] },
  { "source": "svg", "target": ["png", "jpeg"] }
]
```

## 4. Errors

* `400 Bad Request`: Corrupt media, unsupported conversion direction, invalid options, or dimensions outside bounds.
* `401 Unauthorized`: No valid session.
* `413 Payload Too Large`: Upload exceeds configured limit.
* `415 Unsupported Media Type`: Unsupported or mismatched input format.
* `429 Too Many Requests`: Conversion limit exceeded.
* `500 Internal Server Error` or `504 Gateway Timeout`: Worker/rasterizer failure or timeout.

## 5. Security and Performance

* Configure ImageMagick policy to restrict coders, delegates, paths, memory, disk, and runtime; do not expose an unrestricted ImageMagick installation to untrusted input.
* Cap total pixels, frame/page count, dimensions, memory, and processing time to mitigate decompression bombs.
* Use a maintained Intervention Image driver and verify the required PHP extensions/system libraries at startup or deployment.
* Fill transparent pixels with the configured background when producing JPEG.
* Log user ID, formats, byte size, status, duration, and safe error code only; never log image bytes or internal storage paths.
