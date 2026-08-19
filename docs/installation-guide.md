# Installation Guide — laravel-tsd

A **single page** guide: install → setup → verify. Follow in order; required steps are marked REQUIRED, the rest are recommended.

> After finishing, read [`ai/quick-reference.md`](ai/quick-reference.md) for the complete coding rules.

---

## 1. Install

```bash
composer require daniardev/laravel-tsd
```

Or via a path repository (for local development):

```bash
composer config repositories.laravel_tsd path ../packages/daniardev/laravel_tsd
composer require daniardev/laravel-tsd:dev-main
```

## 2. Publish Docs & Config (REQUIRED)

```bash
php artisan vendor:publish --provider="Daniardev\LaravelTsd\LaravelTsdServiceProvider"
```

This publishes:
- `config/laravel-tsd.php`
- `docs/laravel-tsd/` — the full pattern documentation
- `AI_INSTRUCTIONS_LARAVEL_TSD.md` — pattern instructions for AI agents

## 3. Exception Handler (REQUIRED)

**`app/Exceptions/Handler.php`** — create it if it does not exist yet:

```php
<?php

namespace App\Exceptions;

use Daniardev\LaravelTsd\Exceptions\AppHandler;

class Handler extends AppHandler
{
    // Your project now uses TSD exception handling
}
```

**Important rule:** controllers and services **must not** use try-catch blocks. All exception handling is centralized in `AppHandler`. Details: [`patterns/error-handling.md`](patterns/error-handling.md).

## 4. `bootstrap/app.php` (REQUIRED — Laravel 11+)

One place for four configurations: providers, exceptions, middleware, and trusted proxies.

```php
<?php

use App\Providers\SanctumServiceProvider; // only if step 8 (Sanctum) is applied
use Daniardev\LaravelTsd\Exceptions\AppHandler;
use Daniardev\LaravelTsd\Middleware\AppParseBoolAndNull;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([SanctumServiceProvider::class]) // only if step 8 (Sanctum) is applied
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',           // create this file first if missing
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Reverse proxy (nginx, Cloudflare, etc.) — required in production behind a proxy,
        // so the request scheme/host is detected correctly. Also safe for local dev.
        $middleware->trustProxies(at: '*');

        // Normalizes booleans & nulls from the frontend (true → "1", "null" → null, etc.)
        // so `boolean`/`nullable` validation always passes. Details: patterns/middleware.md
        $middleware->append(AppParseBoolAndNull::class);
    })
    ->withExceptions(fn (Exceptions $e) => AppHandler::configure($e))
    ->create();
```

Notes:
- **`trustProxies(at: '*')`** — needed when the app runs behind a reverse proxy/CDN (a purely local Laravel setup may skip it; `'*'` does not break anything locally).
- **`AppParseBoolAndNull`** — registering it globally via `append` is enough; a `parse.bool` route alias is available if you prefer per-route usage.
- The old-style `app/Http/Kernel.php` example (Laravel 10) shown in `patterns/middleware.md` does **not** apply to Laravel 11+ — use the form above.

## 5. Logging Channel `json-daily` (REQUIRED for AppHandler)

**`config/logging.php`** — add the channel:

```php
'channels' => [
    // ... other channels

    'json-daily' => [
        'driver' => 'daily',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 14,
        'tap' => [Daniardev\LaravelTsd\Logging\AppLogFormatJson::class],
    ],
],
```

**`.env`:**

```env
LOG_CHANNEL=json-daily
LOG_LEVEL=debug
```

Without this channel, `AppHandler` / `AppSafe` / `AppLog` **will not write any logs**. Details: [`patterns/logging-setup.md`](patterns/logging-setup.md).

## 6. Base Model (Recommended)

Pick one option:

**Option A — a base model for all models:**

```php
<?php

namespace App\Models;

use Daniardev\LaravelTsd\Traits\AppAuditable;
use Illuminate\Database\Eloquent\Model as BaseModel;

abstract class Model extends BaseModel
{
    use AppAuditable;
}
```

Then have every model extend `App\Models\Model`.

**Option B — apply the trait directly per model:**

```php
class User extends Authenticatable
{
    use HasUuids, AppAuditable, SoftDeletes;

    protected $guarded = ['id'];
}
```

## 7. Base Controller (Recommended)

**`app/Http/Controllers/Controller.php`:**

```php
<?php

namespace App\Http\Controllers;

use Daniardev\LaravelTsd\Traits\AppPagination;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;

abstract class Controller
{
    use AuthorizesRequests, ValidatesRequests, AppPagination;
}
```

## 8. API Auth with Sanctum (Recommended — standard for API projects)

Install Sanctum and adapt it to UUID primary keys:

```bash
composer require laravel/sanctum
php artisan vendor:publish --tag=sanctum-migrations
```

**8a. Custom UUID token model — `app/Models/Sanctum/PersonalAccessToken.php`:**

```php
<?php

namespace App\Models\Sanctum;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuids;
}
```

**8b. Service provider — `app/Providers/SanctumServiceProvider.php`:**

```php
<?php

namespace App\Providers;

use App\Models\Sanctum\PersonalAccessToken;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class SanctumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PersonalAccessToken::class, PersonalAccessToken::class);
    }

    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}
```

**8c. Migration — edit the published `*_create_personal_access_tokens_table.php`** to use UUID (required because all project models use UUID keys):

```php
Schema::create('personal_access_tokens', function (Blueprint $table) {
    $table->uuid('id')->primary();
    // Custom morphs with UUID for tokenable_id (since User uses UUID)
    $table->string('tokenable_type');
    $table->uuid('tokenable_id')->index();
    $table->index(['tokenable_type', 'tokenable_id']);

    $table->text('name');
    $table->string('token', 64)->unique();
    $table->text('abilities')->nullable();
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('expires_at')->nullable()->index();
    $table->timestamps();
});
```

**8d. Register in `bootstrap/app.php`** (see step 4 for the full file):

```php
->withProviders([SanctumServiceProvider::class])
->withRouting(
    api: __DIR__.'/../routes/api.php',
    // ...
)
```

**8e. Create `routes/api.php`** if it does not exist yet (required by the `api:` routing entry above).

## 9. API Docs with Scramble (Recommended)

```bash
composer require dedoc/scramble
php artisan vendor:publish --tag=scramble-config
```

Docs UI is served at `/docs/api` and the spec at `/docs/api.json` (automatic from your `routes/api.php` + PHPDoc types). Access is restricted by `RestrictedDocsAccess` middleware in production via `config/scramble.php`.

## 10. Verify

```bash
# a. Logging works
php artisan tinker --execute 'app("log")->channel("json-daily")->info("test", ["ok" => true]);'
cat storage/logs/laravel-$(date +%Y-%m-%d).log   # must contain JSON with {"ok": true}

# b. Middleware & routes registered
php artisan route:list   # no errors; sanctum/csrf-cookie + docs/api listed

# c. Exception handler active — create a test route that throws; the response must use the AppHandler format
```

If (a) produces JSON and the app boots without errors, the installation is complete.

---

## Quick Checklist

| # | Step | Status |
|---|------|--------|
| 1 | `composer require` | REQUIRED |
| 2 | `vendor:publish` (config + docs) | REQUIRED |
| 3 | `Handler.php` extends `AppHandler` | REQUIRED |
| 4 | `bootstrap/app.php`: `AppHandler::configure` + `trustProxies` + `AppParseBoolAndNull` | REQUIRED |
| 5 | `json-daily` channel + `.env` | REQUIRED |
| 6 | Base model with `AppAuditable` | Recommended |
| 7 | Base controller with `AppPagination` | Recommended |
| 8 | Sanctum (UUID token model + provider + migration + `api.php`) | Recommended |
| 9 | Scramble (config publish) | Recommended |
| 10 | Verify logging & boot | REQUIRED |