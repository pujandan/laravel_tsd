# Error Handling & Logging Pattern

Detailed guide for exception handling and logging in Laravel applications using `AppHandler`.

---

## Overview

**Philosophy:** "Log what matters, ignore what's expected"

The system uses selective logging based on HTTP status codes to reduce noise and focus on important issues.

### Logging Behavior

| Exception Type | HTTP Code | Log Level | Logged? | Reason |
|----------------|-----------|-----------|---------|--------|
| `AuthenticationException` | 401 | WARNING | ✅ Yes | Security monitoring |
| `AuthorizationException` | 403 | WARNING | ✅ Yes | Security monitoring |
| `AppException (4xx)` | 400-499 | WARNING | ✅ Yes | Client error (business logic) |
| `NotFoundHttpException` | 404 | - | ❌ No | Normal user error |
| `ModelNotFoundException` | 404 | - | ❌ No | Normal user error |
| `ValidationException` | 422 | - | ❌ No | Expected validation behavior |
| `ThrottleRequestsException` | 429 | - | ❌ No | Rate limiting working |
| `MethodNotAllowedHttpException` | 405 | - | ❌ No | User error |
| `TokenMismatchException` | 419 | - | ❌ No | Normal session expiry |
| `PostTooLargeException` | 413 | - | ❌ No | Validation error |
| `QueryException` | 500 | ERROR | ✅ Yes | Database error |
| `RuntimeException` | 500 | ERROR | ✅ Yes | System error |
| `Exception` (other) | 500 | ERROR | ✅ Yes | Unexpected error |

**Key Points:**
- **4xx errors** are logged as WARNING for security monitoring (401/403) and business logic tracking
- **5xx errors** are logged as ERROR for system issues
- **Normal/expected errors** (404, 422, 429, 419, 405, 413) are NOT logged to keep logs clean

### Response Format

| Request Type | Error Format | Example |
|--------------|--------------|---------|
| **API requests** (`/api/*` or `Accept: application/json`) | JSON | `{"code": 401, "message": "Unauthenticated"}` |
| **Web requests** | HTML (Laravel default) | Beautiful error page with stack trace (debug mode) |

---

## AppHandler Architecture

`AppHandler` uses Laravel 11/12's exception configuration system:

### Structure

```
AppHandler
├── configure()           - Static: Setup logging via bootstrap/app.php
├── shouldNotLog()        - Static: Filter normal errors
├── isSecurityIssue()     - Static: Check 4xx vs 5xx (for log level)
├── getStatusCode()       - Static: Extract status code from any exception
├── buildLogContext()     - Static: Build structured log context
├── renderException()     - Static: Render JSON for API requests
└── render()              - Instance: Route to JSON (API) or HTML (Web)
```

### Design Principles

1. **Generic & Global** - Works with any exception type, not hard-coded specific exceptions
2. **Status Code Based** - Uses HTTP status codes (4xx vs 5xx) for log level determination
3. **Separation of Concerns** - Logging in `configure()`, Rendering in `render()`
4. **Laravel Native** - Uses Laravel's default HTML rendering for web requests

---

## Setup (Required)

### Step 1: Create Handler Class

**app/Exceptions/Handler.php:**

```php
<?php

namespace App\Exceptions;

use Daniardev\LaravelTsd\Exceptions\AppHandler;

class Handler extends AppHandler
{
    // AppHandler provides all exception handling logic
}
```

### Step 2: Configure Laravel 11/12

**bootstrap/app.php:**

```php
use Daniardev\LaravelTsd\Exceptions\AppHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(fn (\Illuminate\Foundation\Configuration\Exceptions $e) => AppHandler::configure($e))
    ->create();
```

That's it! No additional configuration needed for basic functionality.

### Step 3: Configure Logging (Optional but Recommended)

For structured JSON logging, see [Logging Setup Guide](./logging-setup.md).

---

## NO Try-Catch in Controllers or Services

### Rule

**Controllers and Services MUST NOT have try-catch blocks.** Exception handling is centralized in `AppHandler`.

### ❌ WRONG - Try-Catch in Controller

```php
class PackageController extends Controller
{
    public function store(Request $request)
    {
        try {
            $package = $this->service->create($request->all());
            return response()->json($package);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

**Problems:**
- ❌ Duplicated error handling logic
- ❌ Inconsistent error responses
- ❌ Bypasses global handler
- ❌ No automatic logging
- ❌ Hard to test

### ✅ CORRECT - No Try-Catch

```php
class PackageController extends Controller
{
    public function store(PackageFormRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $package = $this->service->create($request->validated());
            return AppResponse::success(PackageResource::make($package), __('message.saved'));
        });
    }
}
```

**Benefits:**
- ✅ Clean code
- ✅ Consistent error handling via AppHandler
- ✅ Automatic logging (401/403/500+)
- ✅ Automatic JSON responses for API
- ✅ Automatic HTML pages for Web
- ✅ Easier to test

---

## When to Throw AppException

### Use AppException for Business Logic Violations

```php
// ✅ CORRECT - Business rule violations
class PaymentService
{
    public function processPayment(string $packageId, float $amount): void
    {
        $package = Package::findOrFail($packageId);

        // Business rule: Minimum payment
        if ($amount < 100000) {
            throw new AppException('Minimum payment is Rp 100.000', 422);
        }

        // Business rule: Insufficient balance
        if ($user->balance < $amount) {
            throw new AppException('Insufficient balance', 422);
        }
    }
}
```

### Exception vs Validation

| Scenario | Use | Example |
|----------|-----|---------|
| **User input validation** | Form Request | Email format, required fields |
| **Business logic violation** | AppException | Insufficient balance, duplicate entry |
| **Data not found** | findOrFail() | Model not found (auto 404) |
| **Authorization** | Auth middleware | User cannot access resource |

---

## When to Use Each Pattern

### Pattern 1: Business Rule Validation

```php
class PackageService
{
    public function bookPackage(string $packageId, int $seats): void
    {
        $package = Package::findOrFail($packageId);

        // Business rule: Check availability
        if ($package->remaining_seats < $seats) {
            throw new AppException("Only {$package->remaining_seats} seats available", 422);
        }

        // Business rule: Max booking limit
        if ($seats > 10) {
            throw new AppException('Maximum 10 seats per booking', 422);
        }
    }
}
```

### Pattern 2: Data Integrity Checks

```php
class JournalService
{
    public function createJournal(array $data): Journal
    {
        // Validate debit/credit balance
        if ($data['total_debit'] !== $data['total_credit']) {
            throw new AppException('Journal entry must balance (debit != credit)', 422);
        }

        // Validate accounts exist
        $debitAccount = CodeAccount::findOrFail($data['debit_account_id']);
        $creditAccount = CodeAccount::findOrFail($data['credit_account_id']);

        // Create journal...
    }
}
```

---

## API Error Responses

All API errors return consistent JSON format:

```json
{
    "code": 401,
    "message": "Unauthenticated"
}
```

### Common Error Codes

| Code | Message | Exception |
|------|---------|-----------|
| 401 | Unauthenticated | AuthenticationException |
| 403 | Forbidden | AuthorizationException |
| 404 | Not found | NotFoundHttpException |
| 404 | {Model} not found | ModelNotFoundException |
| 405 | Method not allowed | MethodNotAllowedHttpException |
| 413 | File too large | PostTooLargeException |
| 419 | Session expired | TokenMismatchException |
| 422 | {validation errors} | ValidationException |
| 429 | Too many requests | ThrottleRequestsException |
| 500 | Server error | Exception/QueryException |

---

## Web Error Pages

Web requests automatically use Laravel's default error pages:

- **Development (`APP_DEBUG=true`)**: Detailed error page with stack trace
- **Production (`APP_DEBUG=false`)**: Simple "500 | Server Error" page

You can customize these by creating Blade templates in `resources/views/errors/`:

```blade.php
<!-- resources/views/errors/500.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Server Error</title>
</head>
<body>
    <h1>500 - Server Error</h1>
    <p>Something went wrong. Please try again later.</p>
</body>
</html>
```

---

## Testing Exception Handling

### Test API Errors

```bash
# Test 401 (Unauthorized)
curl -i http://your-app.test/api/protected-route
# Expected: {"code":401,"message":"Unauthenticated"}

# Test 403 (Forbidden)
curl -i http://your-app.test/api/admin-route
# Expected: {"code":403,"message":"Forbidden"}

# Test 404 (Not Found)
curl -i http://your-app.test/api/nonexistent-route
# Expected: {"code":404,"message":"Not found"}
```

### Test Logging

```bash
# Trigger an error
curl http://your-app.test/api/test-500

# Check log
cat storage/logs/laravel-$(date +%Y-%m-%d).log | jq

# Expected: JSON log with "level_name": "ERROR"
```

---

**Related Patterns:**
- [Service Layer Pattern](./service-layer.md)
- [Database Transaction Pattern](./database-transaction.md)
- [Logging Setup Guide](./logging-setup.md)

---

**Last Updated:** 2026-03-15