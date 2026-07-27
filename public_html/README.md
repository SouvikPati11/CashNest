# CashNest — Backend Foundation

Production-ready **PHP 8.3+ MVC** foundation for the CashNest REST API. Shared-hosting
compatible, PSR-12, SOLID, PHPUnit-ready. This milestone contains **only the
foundation** — no authentication or feature modules yet.

Built to the finalized specs: `ARCHITECTURE.md`, `DATABASE_DESIGN.md`,
`API_SPECIFICATION.md`, `UI_UX_DESIGN_SYSTEM.md` (in the repository root).

## Requirements

- PHP **8.3+** with extensions: `pdo`, `pdo_mysql`, `mbstring`, `openssl`, `json`, `fileinfo`.
- Composer 2.x.
- MySQL 8.x (InnoDB).

## Installation

This folder deploys **as the web root** (shared-hosting friendly). `vendor/` is
committed, so no Composer step is required on the server — just upload the
contents of this folder into the document root (e.g. Hostinger `public_html/`).
See [`DEPLOYMENT.md`](DEPLOYMENT.md) for the File-Manager-only procedure.

For local development:

```bash
composer install         # optional; vendor/ is already committed
cp .env.example .env      # then edit values (DB, JWT_SECRET, APP_KEY, ...)
php -S 127.0.0.1:8000 -t . index.php
```

The front controller (`index.php`) and `.htaccess` live at the top of this
folder; the `.htaccess` routes requests to `index.php` and blocks direct HTTP
access to the framework folders (`app/`, `core/`, `vendor/`, `.env`, ...).

## Directory layout

```
public_html/         # deploys as the web root
├── index.php        # front controller
├── .htaccess        # routing + blocks the framework folders below
├── core/            # Framework kernel (container, router, http, db, security, ...)
│   ├── Cache/       # FileCache (CacheInterface)
│   ├── Console/     # QueueWorker (cron)
│   ├── Contracts/   # Interfaces (Container, Logger, Cache, Queue, Middleware)
│   ├── Database/    # PDO Database layer
│   ├── Exceptions/  # Framework-level exceptions
│   ├── Http/        # Request, Response, Pipeline
│   ├── Logging/     # FileLogger
│   ├── Queue/       # FileQueue (QueueInterface)
│   ├── Routing/     # Router, Route
│   ├── Security/    # JwtService
│   ├── Application.php  # Boot + dispatch kernel
│   ├── Config.php   # Config repository
│   └── Env.php      # .env loader
├── app/             # Application layer
│   ├── Contracts/   # Mail & Firebase service interfaces
│   ├── Controllers/ # BaseController, HealthController, ApiInfoController
│   ├── Exceptions/  # HttpException hierarchy + Handler
│   ├── Helpers/     # ApiResponse, Pagination, Security
│   ├── Jobs/        # JobInterface (queue jobs)
│   ├── Middleware/  # Cors, RateLimit
│   ├── Models/      # BaseModel
│   ├── Repositories/# BaseRepository
│   ├── Requests/    # FormRequest
│   ├── Services/    # BaseService, RateLimiter, FileUpload, Log mail, Null FCM
│   └── Validation/  # Validator
├── config/          # app, api, database, jwt, cors, cache, queue, logging, mail, firebase, filesystems
├── routes/          # api.php, postback.php
├── database/        # migrations/, seeders/
├── storage/         # logs/, cache/, uploads/, queue/  (writable, non-public)
├── bin/cron.php     # Cron entry point (queue worker)
├── tests/           # PHPUnit unit + feature tests
├── composer.json
└── phpunit.xml
```

## Foundation endpoints

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/` | API discovery document. |
| GET | `/v1` | Versioned API root. |
| GET | `/v1/health` | Liveness/readiness (+ DB check). |

All responses use the standard envelope from `API_SPECIFICATION.md` §1.5.

## What's included

Container/DI · Router · Request/Response · PDO database layer · Env & Config
loaders · Base Controller/Model/Repository/Service · Middleware pipeline (CORS,
rate limiting) · JWT service (`firebase/php-jwt`) · Validation system · Exception
handling · File logger · API response / pagination / security helpers · Rate
limiter · File upload service · Mail & Firebase **interfaces** (safe default
drivers) · Queue & Cache **interfaces** (file drivers) · Cron foundation
(queue worker) · Health check · API versioning.

## Cron

Register a per-minute cron pointing at the worker:

```
* * * * * /usr/bin/php /home/USER/public_html/bin/cron.php >> /dev/null 2>&1
```

## Testing & quality gates

```bash
composer test        # PHPUnit
composer lint        # PSR-12 (phpcs)
composer stan        # Static analysis (PHPStan, level 8)
composer check       # lint + stan + test
```

Quality bar for this milestone: **PSR-12 clean**, **PHPStan level 8 clean**,
and **all tests green** (unit + feature).

## Not included yet (by design)

Authentication, Wallet, Rewards, and all other feature modules. This is the
foundation only.
