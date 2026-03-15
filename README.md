# Laravel TSD Package

[![Latest Version](https://img.shields.io/packagist/v/daniardev/laravel-tsd.svg?style=flat-square)](https://packagist.org/packages/daniardev/laravel-tsd)
[![Total Downloads](https://img.shields.io/packagist/dt/daniardev/laravel-tsd.svg?style=flat-square)](https://packagist.org/packages/daniardev/laravel-tsd)
[![License](https://img.shields.io/packagist/l/daniardev/laravel-tsd.svg?style=flat-square)](https://packagist.org/packages/daniardev/laravel-tsd)

Laravel TSD Package provides **Traits, Services, Data classes** for Laravel applications following **Service Layer Pattern**.

## Installation

```bash
composer require daniardev/laravel-tsd
```

## Setup (REQUIRED)

Follow these steps to fully integrate the package into your project:

### Step 1: Setup Exception Handler

Update `app/Exceptions/Handler.php`:

```php
<?php

namespace App\Exceptions;

use Daniardev\LaravelTsd\Exceptions\AppHandler;

class Handler extends AppHandler
{
    // Your project now uses TSD exception handling
}
```

**For Laravel 11/12 only:** Also update `bootstrap/app.php`:

```php
use Daniardev\LaravelTsd\Exceptions\AppHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/* ... */)
    ->withMiddleware(/* ... */)
    ->withExceptions(fn (\Illuminate\Foundation\Configuration\Exceptions $e) => AppHandler::configure($e))
    ->create();
```

### Step 2: Update Base Model (Optional but Recommended)

Add TSD traits to your base model or individual models:

**Option A: Update app/Models/User.php**
```php
<?php

namespace App\Models;

use Daniardev\LaravelTsd\Traits\AppAuditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, HasUuids, AppAuditable, SoftDeletes;

    protected $guarded = ['id'];
}
```

**Option B: Create Base Model (app/Models/Model.php)**
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

Then extend all models from your base model:
```php
<?php

namespace App\Models;

class User extends \App\Models\Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = ['id'];
}
```

### Step 3: Update Base Controller (Optional but Recommended)

Add pagination trait to your base controller:

**app/Http/Controllers/Controller.php**
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

### Step 4: Publish Documentation

```bash
php artisan vendor:publish --tag=laravel-tsd-docs
```

This creates `docs/laravel-tsd/` directory with all documentation.

### Step 5: Configure Logging (REQUIRED for AppHandler)

The package uses a `json-daily` logging channel for structured JSON logs. You MUST configure this channel for proper exception logging.

**Create `config/logging.php` channel (no custom files needed!):**

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

**That's it!** The package provides `AppLogFormatJson` class with:
- ✅ `datetime` at the top
- ✅ Pretty print for non-production
- ✅ Compact JSON for production

**Update `.env` file:**

```env
LOG_CHANNEL=json-daily
LOG_LEVEL=debug
```

**Verify logging works:**

```bash
# Test logging
php artisan tinker
>>> app('log')->channel('json-daily')->info('Test log', ['test' => 'data']);

# Check the log file
cat storage/logs/laravel-$(date +%Y-%m-%d).log
```

### Step 6: Read Documentation

**Start here:** `docs/laravel-tsd/ai/quick-reference.md` - Complete coding rules

**Then read:**
- `docs/laravel-tsd/ai/templates.md` - Implementation templates
- `docs/laravel-tsd/ai/checklist.md` - Pre-commit validation

## What's Included

### Traits (5)
- **AppTransactional** - Transaction enforcement for services
- **AppAuditable** - Audit trail (created_by, updated_by, deleted_by)
- **AppPagination** - Pagination data extraction
- **AppRequestTrait** - Custom validation error handling
- **AppMigrationOrderScanner** - Migration execution order

### Helpers (11)
- **AppResponse** - JSON response (success, error, print, selection)
- **AppQuery** - Pagination & sorting with security
- **AppRequest** - Request validation rules helper
- **AppResource** - Resource transformation helper
- **AppHelper** - Utility functions
- **AppSafe** - Safe execution for non-critical operations
- **AppValidation** - Custom validation helpers
- **AppLog** - Logging helper
- **AppPermission** - Permission checking
- **AppSecure** - Encryption/decryption
- **AppMigration** - Migration helper

### Logging (1)
- **AppLogFormatJson** - JSON log formatter with datetime at top & pretty print (non-production)

### Exceptions (2)
- **AppException** - Business logic exception
- **AppHandler** - Standard exception handler

### Data (1)
- **PaginationData** - Pagination DTO

### Database Macros
- **auditFields()** - Add audit columns with foreign keys
- **auditFieldsSafe()** - Add audit columns without foreign keys

## Quick Example

After setup, your code will look like this:

```php
// Service Interface
interface UserInterface
{
    public function paginate(PaginationData $pagination, ?string $search = null): LengthAwarePaginator;
    public function create(string $name, string $email): User;
}

// Service Implementation
class UserService implements UserInterface
{
    use AppTransactional;

    public function create(string $name, string $email): User
    {
        $this->requireTransaction();

        if (User::where('email', $email)->exists()) {
            throw new AppException(__('tsd_message.duplicate', ['attribute' => 'email']), 422);
        }

        return User::create(['name' => $name, 'email' => $email]);
    }
}

// Controller (with AppPagination trait)
class UserController extends Controller
{
    public function __construct(private UserInterface $service) {}

    public function store(UserFormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $user = $this->service->create(
                name: $request->input('name'),
                email: $request->input('email')
            );
            return AppResponse::success(UserResource::make($user), __('tsd_message.successSaved'));
        });
    }
}

// Form Request (with AppRequestTrait trait)
class UserFormRequest extends FormRequest
{
    use AppRequestTrait; // Custom validation error format

    public function rules(): array
    {
        return [
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}

// Model (with AppAuditable trait)
class User extends Authenticatable
{
    use HasApiTokens, HasUuids, AppAuditable, SoftDeletes;

    protected $guarded = ['id'];
}

// Migration (with auditFields macro)
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');
    $table->string('email')->unique();
    $table->auditFields(); // Adds created_by, updated_by, deleted_by
    $table->timestamps();
    $table->softDeletes();
});
```

## Documentation

| File | Description |
|------|-------------|
| **[docs/ai/quick-reference.md](docs/ai/quick-reference.md)** | ⭐ **START HERE** - All rules & patterns |
| **[docs/patterns/logging-setup.md](docs/patterns/logging-setup.md)** | 🔧 **REQUIRED** - Logging configuration guide |
| **[docs/ai/templates.md](docs/ai/templates.md)** | Code templates for all components |
| **[docs/ai/checklist.md](docs/ai/checklist.md)** | Pre-commit validation checklist |
| **[docs/patterns/service-layer.md](docs/patterns/service-layer.md)** | Service layer pattern details |
| **[docs/patterns/database-transaction.md](docs/patterns/database-transaction.md)** | Transaction pattern details |
| **[docs/patterns/error-handling.md](docs/patterns/error-handling.md)** | Exception handling guide |
| **[docs/patterns/safe-execution.md](docs/patterns/safe-execution.md)** | AppSafe usage guide |

## Key Patterns

| Layer | Pattern | Example |
|-------|---------|---------|
| **Controller** | Wrap in `DB::transaction()` | `DB::transaction(fn() => $this->service->create(...))` |
| **Service** | Explicit parameters, use `AppTransactional` | `public function create(string $name): User` |
| **Request** | Use `AppRequestTrait` | `use AppRequestTrait;` |
| **Resource** | Return snake_case directly | `['user_name' => $this->user_name]` |
| **Model** | Use `AppAuditable`, UUID keys | `use AppAuditable;` |
| **Migration** | Use `auditFields()` macro | `$table->auditFields();` |

## Available Translations

The package includes these translations (auto-loaded):
- **tsd_message** - Success/error messages (EN + ID)
- **tsd_label** - Common labels (EN + ID)

Usage:
```php
__('tsd_message.successSaved')
__('tsd_label.all')
```

## Requirements

- PHP 8.3+
- Laravel 11.0 or 12.0

## License

MIT License. See [LICENSE](LICENSE) for details.