# Backend Training (PHP / Laravel)

A PHP recreation of the file-conversion application. The requirements live in [`specs-php/`](specs-php/):
start with [`00-platform-stack`](specs-php/00-platform-stack/spec.md), then implement the numbered features in order.

## Stack

| Piece | Local development |
|---|---|
| PHP 8.5 + Composer | [Laravel Herd](https://herd.laravel.com) (native, on Windows) |
| Laravel 13 | this repository |
| PostgreSQL 18 | Docker (`compose.yaml`), exposed on host port **5433** |
| Sessions, cache, queues | `database` drivers (PostgreSQL) — no Redis yet |
| Mail | `log` driver — emails are written to `storage/logs/laravel.log` |

## First-time setup

```bash
composer install
cp .env.example .env          # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
docker compose up -d          # start PostgreSQL
php artisan migrate           # create the tables
```

## Everyday commands

```bash
docker compose up -d                  # start the database
php artisan serve                     # run the app at http://127.0.0.1:8000
php artisan test                      # run the test suite
php artisan route:list                # show all routes
php artisan migrate                   # apply new migrations
php artisan migrate:fresh             # drop everything and re-migrate (local only!)
php artisan tinker                    # interactive PHP shell with the app loaded
php artisan queue:work                # process queued jobs (needed once you queue mail)
php artisan make:<thing> --help       # generators: model, controller, request, policy, migration, test...
vendor/bin/pint                       # format code (Laravel's code style)
```

Instead of `php artisan serve` you can link the folder in Herd (`herd link backend-training`) and use
`http://backend-training.test`.

Try it: `curl http://127.0.0.1:8000/api/v1/ping` → `{"message":"pong","time":"..."}`.

## Where things go

```
app/Http/Controllers/Api/V1/   controllers for /api/v1 endpoints (see PingController)
app/Http/Requests/             Form Requests — validation (php artisan make:request)
app/Models/                    Eloquent models
app/Policies/                  authorization rules (spec 02-rbac)
app/Jobs/, app/Mail/           queued work and emails
bootstrap/app.php              routing, middleware and exception handling configuration
config/                        configuration, driven by .env
database/migrations/           schema changes
database/factories/, seeders/  test and demo data
routes/api.php                 API routes — automatically prefixed with /api/v1
routes/web.php                 browser routes (e.g. magic-link callbacks)
tests/Feature/                 HTTP-level tests (see tests/Feature/Api/V1/PingTest.php)
```

## Notes

* **Tests** use an in-memory SQLite database (see `phpunit.xml`), so they run without Docker. If you hit
  PostgreSQL-specific behaviour, point the tests at a separate PostgreSQL database instead.
* **`routes/api.php` is stateless by default** — no session or CSRF middleware. The specs use cookie-based
  Laravel sessions (`04-authorization-session`), so you will need to decide how API routes get the session
  middleware when you get there (see "Middleware Groups" in the Laravel middleware docs and `bootstrap/app.php`).
* **Windows limitations:** the `pcntl` extension does not exist on Windows, so `queue:work --timeout` cannot
  kill stuck jobs, and Imagick with SVG support is hard to install. This only matters for the conversion
  features (09, 10); by then consider running PHP in WSL2 or Docker.

## Suggested first exercises

1. Read the users migration and `App\Models\User`, then switch users to UUID primary keys (platform spec §5).
2. Make API errors (validation, 404, 401...) return one consistent JSON shape (`withExceptions` in `bootstrap/app.php`).
3. Implement [`01-registration`](specs-php/01-registration/spec.md): route → Form Request → controller → test.
