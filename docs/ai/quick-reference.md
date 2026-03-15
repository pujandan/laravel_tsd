# Quick Reference - All Coding Rules

This file contains ALL coding rules and patterns for Laravel development using this architecture. Use this as your primary reference when coding.

> **💡 Design & UI Rules:** For frontend/UI guidelines (colors, typography, components), refer to **docs/design-system.md**
>
> **⚡ Livewire 4 Rules:** For Livewire component patterns and best practices, refer to **docs/patterns/livewire.md**

---

## Table of Contents

0. [Package Setup](#0-package-setup)
1. [Naming Conventions](#1-naming-conventions)
2. [Controller Rules](#2-controller-rules)
3. [Service Rules](#3-service-rules)
4. [Model Rules](#4-model-rules)
5. [Request Rules](#5-request-rules)
6. [Resource Rules](#6-resource-rules)
7. [Route Rules](#7-route-rules)
8. [Migration Rules](#8-migration-rules)
9. [Response Format Rules](#9-response-format-rules)
10. [Transaction Pattern Rules](#10-transaction-pattern-rules)
11. [Error Handling Rules](#11-error-handling-rules)
12. [Model Retrieval Pattern](#12-model-retrieval-pattern)
13. [Enum Pattern Rules](#13-enum-pattern-rules)
14. [Common Mistakes](#14-common-mistakes)
15. [Safe Execution Pattern](#15-safe-execution-pattern)
16. [Helper Reference](#16-helper-reference)
17. [Trait Reference](#17-trait-reference)

---

## 0. Package Setup

### 0.1 Installation

```bash
composer require daniardev/laravel-tsd
```

### 0.2 Publish Documentation (Optional but Recommended)

```bash
php artisan vendor:publish --tag=laravel-tsd-docs
```

This creates `docs/laravel-tsd/` directory with all documentation.

### 0.3 Setup Exception Handler (REQUIRED)

The package provides `AppHandler` for standardized exception handling. **You must configure your project to use it.**

**Step 1: Create app/Exceptions/Handler.php**

```php
<?php

namespace App\Exceptions;

use Daniardev\LaravelTsd\Exceptions\AppHandler;

class Handler extends AppHandler
{
    // AppHandler provides all exception handling logic
}
```

**Step 2: Configure Laravel 11/12 (bootstrap/app.php)**

```php
use Daniardev\LaravelTsd\Exceptions\AppHandler;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(/* ... */)
    ->withMiddleware(/* ... */)
    ->withExceptions(fn (\Illuminate\Foundation\Configuration\Exceptions $e) => AppHandler::configure($e))
    ->create();
```

**What AppHandler provides:**

| Feature | Description |
|---------|-------------|
| **API JSON responses** | Consistent `{"code": 401, "message": "..."}` format |
| **Web HTML pages** | Laravel's default error pages (debug: stack trace, prod: simple) |
| **Security logging** | 401/403 logged as WARNING |
| **System error logging** | 500+ logged as ERROR |
| **Generic approach** | Works with any exception type (not hard-coded) |
| **Normal errors filtered** | 404, 422, 429, 419, 405, 413 NOT logged |

### 0.4 Configure Logging (REQUIRED)

The package uses a `json-daily` logging channel for structured JSON logs. You MUST configure this channel for `AppHandler` to work properly.

**No custom files needed!** Just update `config/logging.php`:

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

The package provides `AppLogFormatJson` class with:
- ✅ `datetime` at the top (for better readability)
- ✅ Pretty print for non-production (for easier debugging)
- ✅ Compact JSON for production (smaller file size)

#### Update Environment

Update `.env` file:

```env
LOG_CHANNEL=json-daily
LOG_LEVEL=debug
```

#### What Gets Logged

| Exception Type | HTTP Code | Log Level | Logged? | Reason |
|----------------|-----------|-----------|---------|--------|
| `AuthenticationException` | 401 | WARNING | ✅ Yes | Security monitoring |
| `AuthorizationException` | 403 | WARNING | ✅ Yes | Security monitoring |
| `AppException (4xx)` | 400-499 | WARNING | ✅ Yes | Business logic tracking |
| `NotFoundHttpException` | 404 | - | ❌ No | Normal user error |
| `ModelNotFoundException` | 404 | - | ❌ No | Normal user error |
| `ValidationException` | 422 | - | ❌ No | Expected validation |
| `ThrottleRequestsException` | 429 | - | ❌ No | Rate limiting working |
| `MethodNotAllowedHttpException` | 405 | - | ❌ No | User error |
| `TokenMismatchException` | 419 | - | ❌ No | Normal session expiry |
| `PostTooLargeException` | 413 | - | ❌ No | Validation error |
| `QueryException` | 500 | ERROR | ✅ Yes | Database error |
| `RuntimeException` | 500 | ERROR | ✅ Yes | System error |

#### Verify Logging Works

```bash
# Test logging manually
php artisan tinker
>>> app('log')->channel('json-daily')->info('Test log', ['test' => 'data']);
=> true

# Check the log file
cat storage/logs/laravel-$(date +%Y-%m-%d).log

# You should see JSON formatted log like:
{"message":"Test log","context":{"test":"data"},...}
```

#### Troubleshooting

**Problem: Logs not appearing in file**

1. Check `config/logging.php` - ensure `json-daily` channel exists
2. Check `.env` - ensure `LOG_CHANNEL=json-daily`
3. Check file permissions - ensure `storage/logs` is writable
4. Clear config cache: `php artisan config:clear`

**Problem: Logs not in JSON format**

1. Ensure `FormatJsonLog` class exists in `app/Logging/`
2. Check `tap` configuration in `config/logging.php`
3. Clear config cache: `php artisan config:clear`

### 0.5 Verify Setup

After setup, verify your handler works:

**Test API Error Responses:**

```bash
# Test 404 (should return JSON)
curl http://your-app.test/api/invalid-route

# Expected response:
{"code": 404, "message": "Not found"}

# Test 401/403 (should return JSON)
curl http://your-app.test/api/protected-route

# Expected response:
{"code": 401, "message": "Unauthenticated"}
```

**Test Web Error Pages:**

```bash
# Test web route (should return HTML)
curl http://your-app.test/test-401

# Expected: HTML error page with stack trace (if APP_DEBUG=true)
```

**Test Logging:**

```bash
# Trigger an error
curl http://your-app.test/api/test-500

# Check log file
cat storage/logs/laravel-$(date +%Y-%m-%d).log | jq

# Expected: JSON log with "level_name": "ERROR"
```

---

## 1. Naming Conventions

### 1.1 Database Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **Table Name** | `snake_case` + plural | `category_accounts`, `users`, `products` | Always plural |
| **Column Name** | `snake_case` | `device_id`, `created_at`, `is_active` | Lowercase with underscores |
| **Primary Key** | `id` (UUID) | `$table->uuid('id')->primary()` | Use UUID, not auto-increment |
| **Foreign Key** | `{relation}_id` | `user_id`, `category_id`, `parent_id` | Reference relation + _id |

### 1.1.1 API Response Convention (STANDARD LARAVEL)

**CRITICAL:** This package follows **standard Laravel convention** where API responses use the **same snake_case format** as database columns. No case transformation is performed.

| Layer | Key Format | Example |
|-------|------------|---------|
| **Database** | `snake_case` | `user_name`, `created_at`, `phone_number` |
| **Model** | `snake_case` | `$model->user_name`, `$model->created_at` |
| **Resource/JSON Response** | `snake_case` | `{"user_name": "John", "created_at": "2024-01-01"}` |
| **Request Validation** | `snake_case` | `'user_name' => ['required', 'string']` |

**Request/Response Flow:**
```php
// ❌ OLD PATTERN (removed): camelCase transformation
// Frontend: {userName: "John"} → Backend: user_name → Response: {userName: "John"}

// ✅ NEW PATTERN (standard Laravel): consistent snake_case
// Frontend: {user_name: "John"} → Backend: user_name → Response: {user_name: "John"}
```

**Benefits:**
- Consistent with Laravel convention
- Simpler, no transformation overhead
- Easier debugging (keys match database columns)
- No ambiguity in API contracts

### 1.2 Model Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **Class Name** | `PascalCase` + singular | `CategoryAccount`, `User`, `Product` | Singular, not plural |
| **File Name** | Same as class | `CategoryAccount.php`, `User.php` | Match class name |
| **Properties** | `snake_case` | `$this->device_id`, `$this->created_at` | Match database columns |
| **Methods/Relations** | `camelCase` | `codeAccount()`, `user()`, `findById()` | NOT snake_case! |

### 1.3 Controller Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **Class Name** | `PascalCase` + "Controller" | `UserController`, `ProductController` | Always end with Controller |
| **File Location** | `Api/{Module}/{Entity}/` | `Api/Common/User/UserController.php` | Module/Entity structure |
| **Methods** | `camelCase` | `index()`, `store()`, `update()`, `destroy()` | RESTful methods |

### 1.4 Service Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **Service Class** | `PascalCase` + "Service" | `UserService`, `ProductService` | Always end with Service |
| **Interface Name** | `PascalCase` + "Interface" | `UserInterface`, `ProductInterface` | Without "Service" suffix |
| **File Location** | `app/Services/{Domain}/` | `Services/Common/User/UserService.php` | Domain-based structure |
| **Methods** | `camelCase` | `paginate()`, `find()`, `create()`, `update()`, `delete()` | CRUD operations |

### 1.5 Route Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **URL Path** | `kebab-case` + plural | `/users`, `/products`, `/category-accounts` | Always plural, kebab-case |
| **Route Name** | `dot.notation` | `common.users.index`, `products.store` | Module.entity.action |
| **Route Prefix** | `lowercase` | `Route::prefix('common')`, `Route::prefix('api')` | Module name |

### 1.6 Request Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **Index Request** | `{Entity}Request` | `UserRequest`, `ProductRequest` | For listing/filtering |
| **Form Request** | `{Entity}FormRequest` | `UserFormRequest`, `ProductFormRequest` | For store/update |
| **File Location** | `app/Http/Requests/Api/{Module}/{Entity}/` | `Requests/Api/Common/User/UserRequest.php` | Match entity structure |

### 1.7 Resource Naming

| Type | Convention | Example | Rule |
|------|------------|---------|------|
| **Resource (Single)** | `{Entity}Resource` | `UserResource`, `ProductResource` | For single entity |
| **Collection (List)** | `{Entity}Collection` | `UserCollection`, `ProductCollection` | For list of entities |
| **File Location** | `app/Http/Resources/Api/{Module}/{Entity}/` | `Resources/Api/Common/User/UserResource.php` | Match entity structure |

---

## 2. Controller Rules

### 2.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST inject Service via constructor** | `private ServiceInterface $service` |
| 2 | **MUST use DB::transaction for write operations** | `DB::transaction(function () { ... })` |
| 3 | **MUST NOT use DB::transaction for read operations** | No transaction for `index()`, `show()` |
| 4 | **MUST return via AppResponse::success()** | `AppResponse::success(JsonResource, message)` |
| 5 | **MUST use __() for locale (NOT Lang::get())** | `__('message.successLoaded')` |
| 6 | **MUST add @throws Throwable for methods with DB::transaction** | `@throws Throwable` in PHPDoc |
| 7 | **MUST NOT have try-catch blocks** | Let Handler.php manage exceptions |
| 8 | **MUST NOT put business logic in controller** | Business logic goes to service |
| 9 | **MUST NOT call Model directly (use service)** | `$this->service->find($id)` not `Model::find($id)` |
| 10 | **MUST wrap array responses with JsonResource::make()** | `JsonResource::make($arrayData)` |

### 2.2 Response Format

```php
// ✅ CORRECT - JsonResource first, message second
return AppResponse::success(
    JsonResource::make($data),      // Parameter 1: JsonResource
    __('message.successLoaded')     // Parameter 2: message
);

// ❌ WRONG - Parameters reversed
return AppResponse::success(
    __('message.successLoaded'),    // Wrong order!
    $data
);

// For array responses:
return AppResponse::success(
    JsonResource::make($arrayData), // Wrap array
    __('message.actionCompleted')
);
```

### 2.3 Transaction Pattern

```php
// ✅ CORRECT - Write operation with transaction
public function store(FormRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $item = $this->service->create($request->validated());
        return AppResponse::success(Resource::make($item), __('message.saved'));
    });
}

// ✅ CORRECT - Read operation without transaction
public function show(string $id): JsonResponse
{
    $item = $this->service->find($id);
    return AppResponse::success(Resource::make($item), __('message.loaded'));
}

// ❌ WRONG - Write operation without transaction
public function store(FormRequest $request): JsonResponse
{
    $item = $this->service->create($request->validated());
    return AppResponse::success(Resource::make($item), __('message.saved'));
}
```

### 2.4 Method Length

| Method Type | Maximum Lines | Notes |
|-------------|---------------|-------|
| **Controller methods** | 20 lines | Keep thin, delegate to service |
| **Custom actions** | 20 lines | Extract to service if longer |

---

## 3. Service Rules

### 3.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST have Interface** (REQUIRED for all services) | `interface EntityInterface` |
| 2 | **MUST implement Interface** | `class EntityService implements EntityInterface` |
| 3 | **MUST use AppTransactional trait** | `use AppTransactional;` |
| 4 | **MUST call $this->requireTransaction() in ALL write methods** | First line of write methods |
| 5 | **MUST NOT call requireTransaction() in read methods** | Only for write operations |
| 6 | **MUST NOT use DB::transaction()** | Transactions managed by controller |
| 7 | **MUST NOT have try-catch blocks** | Let Handler.php manage exceptions |
| 8 | **MUST handle all business logic** | Validation, checks, rules |
| 9 | **MUST return Model, not JSON** | Controller handles JSON response |
| 10 | **MUST use type hints for all methods** | Parameters and return types |
| 11 | **MUST register Interface binding in AppServiceProvider** | `$this->app->bind(Interface::class, Service::class);` |

### 3.2 Service Interface Pattern

**CRITICAL: ALL parameters must be explicit, NO array $data or array $filters!**

```php
// ✅ CORRECT - ALL parameters are explicit
interface UserInterface
{
    // Paginate: ALL filters as explicit parameters
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null,
        ?string $department = null
    ): LengthAwarePaginator;

    public function find(string $id): User;

    // Create: ALL fields as explicit parameters (NO array $data)
    public function create(
        string $name,
        string $email,
        ?string $phone = null,
        ?string $department = null
    ): User;

    // Update: ALL fields as explicit parameters (NO array $data)
    public function update(
        string $id,
        ?string $name = null,
        ?string $email = null,
        ?string $phone = null,
        ?string $department = null
    ): User;

    public function delete(string $id): User;
}

// Service implements interface
class UserService implements UserInterface
{
    use AppTransactional;

    // Implementation...
}
```

### 3.3 Write vs Read Methods

```php
// ✅ WRITE method - With requireTransaction(), ALL parameters explicit
public function create(
    string $name,
    string $email,
    ?string $phone = null,
    ?string $department = null
): User {
    $this->requireTransaction();  // Required at start

    // Business logic: Validate uniqueness
    $existing = User::where('email', $email)->first();
    if ($existing) {
        throw new AppException('Email already exists', 422);
    }

    // Build data with ALL explicit parameters
    $createData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'department' => $department,
    ];

    // Remove null values
    $createData = array_filter($createData, fn($v) => $v !== null);

    return User::create($createData);
}

// ✅ READ method - No requireTransaction(), ALL filters explicit
public function paginate(
    PaginationData $pagination,
    ?string $search = null,
    ?string $status = null,
    ?string $department = null
): LengthAwarePaginator {
    $query = User::query();

    if ($search !== null) {
        $query->where('name', 'like', "%{$search}%");
    }

    if ($status !== null) {
        $query->where('status', $status);
    }

    if ($department !== null) {
        $query->where('department', $department);
    }

    return AppQuery::paginate($query, $pagination);
    // Optional: Specify allowed sort columns for security
    // return AppQuery::paginate($query, $pagination, ['id', 'name', 'created_at', 'updated_at']);
}
```

**Note:** The `$allowedColumns` parameter in `AppQuery::paginate()` is optional but recommended for security. It restricts which columns can be used for sorting, preventing SQL injection attacks through user input.

### 3.4 Business Logic in Service

```php
// ✅ CORRECT - ALL fields as explicit parameters
public function create(
    string $name,
    string $email,
    ?string $phone = null,
    ?string $department = null
): User {
    $this->requireTransaction();

    // Business rule: Check uniqueness using explicit parameter
    $existing = User::where('email', $email)->first();
    if ($existing) {
        throw new AppException('Email already exists', 422);
    }

    // Build data with ALL explicit parameters
    $createData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'department' => $department,
        'status' => UserStatus::ACTIVE,
    ];

    // Remove null values for optional fields
    $createData = array_filter($createData, fn($v) => $v !== null);

    return User::create($createData);
}

// ❌ WRONG - Using array $data parameter
public function create(string $name, string $email, array $data = []): User
{
    // Missing explicit parameters for phone, department, etc.
    $createData = array_merge(['name' => $name, 'email' => $email], $data);
    return User::create($createData);
}

// ❌ WRONG - Business logic in controller
public function store(FormRequest $request): JsonResponse
{
    // Business logic should be in service!
    $existing = User::where('email', $request->email)->first();
    if ($existing) {
        // ...
    }
}
```

### 3.5 Method Length

| Method Type | Maximum Lines | Notes |
|-------------|---------------|-------|
| **Service methods** | 30 lines | Extract to private methods if longer |

### 3.6 Service Registration Pattern

**CRITICAL: After creating a new service, you MUST register it in AppServiceProvider immediately!**

```php
// ❌ WRONG - Service not registered
class UserService implements UserInterface
{
    // Implementation...
}

// Result: Error 500 - "Target [Interface] is not instantiable"

// ✅ CORRECT - Register service in AppServiceProvider
// File: app/Providers/AppServiceProvider.php

use App\Services\Common\User\UserInterface;
use App\Services\Common\User\UserService;

public function register(): void
{
    // Register User Service
    $this->app->bind(UserInterface::class, UserService::class);
}
```

**When to Register:**
- Immediately after creating Interface and Service classes
- Before testing the controller
- Add both `use` statements and binding in same commit

**Common Errors if Not Registered:**
- Error 500: "Target [Interface] is not instantiable"
- Dependency injection fails
- Controller cannot be instantiated

---

## 4. Model Rules

### 4.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST use HasUuids trait** | `use HasUuids;` for UUID primary keys |
| 2 | **MUST use AppAuditable trait** | `use AppAuditable;` for audit trails |
| 3 | **MUST use guarded instead of fillable** | `protected $guarded = ['id', 'created_at', 'updated_at'];` |
| 4 | **MUST use camelCase for relation methods** | `public function user()` not `user_relation()` |
| 5 | **MUST use SoftDeletes if deleted_by needed** | `use SoftDeletes;` |
| 6 | **MUST NOT put business logic in model** | Business logic goes to service |

### 4.2 Model Template

```php
<?php

namespace App\Models;

use Daniardev\LaravelTsd\Traits\AppAuditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use HasUuids, AppAuditable, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    // ✅ camelCase relation method
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    // ❌ WRONG - snake_case relation
    // public function user_profile() { ... }
}
```

### 4.3 Relationship Naming

| Database Column | Model Method | Type |
|-----------------|--------------|------|
| `user_id` | `user()` | `belongsTo` |
| `category_id` | `category()` | `belongsTo` |
| `parent_id` | `parent()` | `belongsTo` |
| - | `posts()` | `hasMany` |
| - | `comments()` | `hasMany` |

---

## 5. Request Rules

### 5.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST create two request classes per entity** | `{Entity}Request` (index), `{Entity}FormRequest` (form) |
| 2 | **MUST use AppRequest::pagination() for index** | `AppRequest::pagination([...])` |
| 3 | **MUST define custom attribute names** | `attributes()` method with `__()` |
| 4 | **MUST use AppRequestTrait** | `use AppRequestTrait;` |

### 5.2 Index Request Template

```php
<?php

namespace App\Http\Requests\Api\Common\User;

use Daniardev\LaravelTsd\Helpers\AppRequest;
use Daniardev\LaravelTsd\Traits\AppRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    use AppRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function attributes()
    {
        return [
            'filter.search' => __('label.search'),
            'filter.status' => __('label.status'),
        ];
    }

    public function rules(): array
    {
        return AppRequest::pagination([
            'filter.search' => ['nullable', 'string'],
            'filter.status' => ['nullable', 'string'],
        ]);
    }
}
```

### 5.3 Form Request Template

```php
<?php

namespace App\Http\Requests\Api\Common\User;

use Daniardev\LaravelTsd\Traits\AppRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class UserFormRequest extends FormRequest
{
    use AppRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function attributes()
    {
        return [
            'name' => __('label.name'),
            'email' => __('label.email'),
            'phone_number' => __('label.phoneNumber'),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone_number' => ['nullable', 'string'],
        ];
    }
}
```

---

## 6. Resource Rules

### 6.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST create two resource classes per entity** | `{Entity}Resource` (single), `{Entity}Collection` (list) |
| 2 | **MUST return array with snake_case keys** | Match database column names |
| 3 | **MUST include audit information** | Created/updated/deleted with user info |
| 4 | **MUST use $this->when() for conditional loading** | For relations |
| 5 | **MUST use dedicated Resource for nested relations** | Don't inline relation data |

### 6.2 Resource Template

```php
<?php

namespace App\Http\Resources\Api\Common\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,

            // ✅ Use dedicated Resource for relations
            'profile' => $this->when($this->relationLoaded('profile'), function () {
                return ProfileResource::make($this->profile);
            }),

            // ✅ Include audit information
            'audit' => [
                'created' => [
                    'at' => $this->created_at?->format('Y-m-d H:i:s'),
                    'by' => $this->creator_name,
                    'by_id' => $this->created_by,
                ],
                'updated' => [
                    'at' => $this->updated_at?->format('Y-m-d H:i:s'),
                    'by' => $this->updater_name,
                    'by_id' => $this->updated_by,
                ],
            ],
        ];
    }
}
```

### 6.3 Collection Template

```php
<?php

namespace App\Http\Resources\Api\Common\User;

use Daniardev\LaravelTsd\Helpers\AppResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            // ✅ CRITICAL: Wrap collection with dedicated Resource
            // This ensures each item goes through Resource transform (audit info, camelCase, etc.)
            'data' => UserResource::collection($this->collection),

            // ✅ Use AppResource helper for consistent pagination format
            'pagination' => AppResource::pagination($this),
        ];
    }
}
```

**IMPORTANT NOTES:**

1. **MUST wrap with Resource**: Always use `{Entity}Resource::collection($this->collection)`
   - Without this: No audit info, no custom formatting
   - Each item must go through Resource's `toArray()` method

2. **MUST use AppResource::pagination()**: Helper for consistent pagination format
   - Returns: page, size, from, to, count, total, pageLast, pageMore
   - Consistent format across all collections

3. **Type hint toArray()**: Always use `Request $request` not just `$request`

4. **Response keys use snake_case**: Match database column names directly
   - No case transformation needed
   - Consistent with Laravel convention

---

## 7. Route Rules

### 7.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST use kebab-case + plural for URL** | `/users`, `/products` |
| 2 | **MUST group routes by module** | `Route::prefix('common')->group(...)` |
| 3 | **MUST use route names with dot notation** | `common.users.index` |
| 4 | **MUST use resource controllers for CRUD** | Standard RESTful methods |

### 7.2 Route Template

```php
// routes/api.php

Route::prefix('common')->group(function () {
    // Users Routes
    Route::get('users', [UserController::class, 'index'])
        ->name('common.users.index');

    Route::post('users', [UserController::class, 'store'])
        ->name('common.users.store');

    Route::get('users/{id}', [UserController::class, 'show'])
        ->name('common.users.show');

    Route::put('users/{id}', [UserController::class, 'update'])
        ->name('common.users.update');

    Route::delete('users/{id}', [UserController::class, 'destroy'])
        ->name('common.users.destroy');
});
```

### 7.3 Route Naming Convention

```php
// Format: {module}.{entity}.{action}
'common.users.index'
'common.users.store'
'common.users.show'
'common.users.update'
'common.users.destroy'
```

---

## 8. Migration Rules

### 8.1 Mandatory Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST use UUID for primary keys** | `$table->uuid('id')->primary()` |
| 2 | **MUST use auditFields() macro for new tables** | `$table->auditFields()` |
| 3 | **MUST use auditFieldsSafe() for existing tables** | `$table->auditFieldsSafe()` |
| 4 | **MUST add foreign key constraints** | `->constrained()->onUpdate('cascade')->onDelete('cascade')` |
| 5 | **MUST add indexes for frequently queried columns** | `$table->index(['column'])` |

### 8.2 Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign keys
            $table->foreignUuid('role_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('set null');

            // Columns
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->auditFields();

            $table->timestamps(6);
            $table->softDeletes(); // Add this if you need deleted_by
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
```

### 8.3 Audit Fields Macro

```php
// For NEW tables
$table->auditFields();

// What gets added:
// - created_by (UUID, nullable, FK to users)
// - updated_by (UUID, nullable, FK to users)
// - deleted_by (UUID, nullable, FK to users) ONLY if softDeletes() is present
// - All columns indexed
// - Foreign keys with ON DELETE SET NULL
```

---

## 9. Response Format Rules

### 9.1 Response Structure

```php
// Success Response
{
    "success": true,
    "message": "Data loaded successfully",
    "data": {
        "id": "uuid",
        "fieldName": "value",
        "audit": { ... }
    }
}

// Error Response
{
    "success": false,
    "message": "Error message",
    "errors": { ... }
}
```

### 9.2 AppResponse::success() Signature

```php
public static function success(?JsonResource $data, ?string $message = null): JsonResponse

// Parameter 1: JsonResource (or null)
// Parameter 2: Message string (or null)
```

### 9.3 Correct Response Patterns

```php
// ✅ Single entity
return AppResponse::success(
    EntityResource::make($entity),
    __('message.successLoaded')
);

// ✅ Collection
return AppResponse::success(
    new EntityCollection($entities),
    __('message.successLoaded')
);

// ✅ Array data (wrap with JsonResource::make())
return AppResponse::success(
    JsonResource::make($arrayData),
    __('message.actionCompleted')
);

// ❌ WRONG - Unwrapped array
return AppResponse::success(
    $arrayData,  // Type error!
    __('message.actionCompleted')
);
```

---

## 10. Transaction Pattern Rules

### 10.1 Controller Transaction Rules

| Operation | Transaction Required | Pattern |
|-----------|---------------------|---------|
| **CREATE (store)** | ✅ YES | `DB::transaction(function () { ... })` |
| **UPDATE (update)** | ✅ YES | `DB::transaction(function () { ... })` |
| **DELETE (destroy)** | ✅ YES | `DB::transaction(function () { ... })` |
| **READ (index, show)** | ❌ NO | No transaction wrapper |
| **Custom write** | ✅ YES | `DB::transaction(function () { ... })` |

### 10.2 Service Transaction Rules

| Method Type | requireTransaction() | Pattern |
|-------------|----------------------|---------|
| **CREATE** | ✅ YES | `$this->requireTransaction()` at start |
| **UPDATE** | ✅ YES | `$this->requireTransaction()` at start |
| **DELETE** | ✅ YES | `$this->requireTransaction()` at start |
| **READ** | ❌ NO | No requireTransaction() call |

### 10.3 Complete Transaction Pattern

```php
// Controller
public function store(FormRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $item = $this->service->create($request->validated());
        return AppResponse::success(Resource::make($item), __('message.saved'));
    });
}

// Service
public function create(array $data): Model
{
    $this->requireTransaction(); // Enforces transaction

    // Business logic...
    return Model::create($data);
}
```

---

## 11. Error Handling Rules

### 11.1 NO Try-Catch Rule

```php
// ❌ WRONG - Try-catch in controller
public function store(Request $request)
{
    try {
        DB::transaction(function () use ($request) {
            // ...
        });
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

// ❌ WRONG - Try-catch in service
public function create(array $data): Model
{
    try {
        $this->requireTransaction();
        return Model::create($data);
    } catch (\Exception $e) {
        throw new AppException('Failed to create', 500);
    }
}

// ✅ CORRECT - No try-catch (Handler.php manages exceptions)
public function store(FormRequest $request)
{
    return DB::transaction(function () use ($request) {
        // Exceptions handled globally
    });
}
```

### 11.2 Throw AppException for Business Logic

```php
// ✅ CORRECT - Business rule violation
if ($entity->quantity < $requested) {
    throw new AppException('Insufficient quantity available', 422);
}

// ✅ CORRECT - Data integrity issue
if ($user->balance < $amount) {
    throw new AppException('Insufficient balance', 422);
}

// ✅ CORRECT - Duplicate entry
$existing = Model::where('email', $email)->first();
if ($existing) {
    throw new AppException('Email already exists', 422);
}
```

### 11.3 Use findOrFail() for Required Models

```php
// ✅ CORRECT - Single line, auto 404
$entity = Entity::findOrFail($id);

// ❌ WRONG - Verbose null check
$entity = Entity::find($id);
if ($entity === null) {
    throw new AppException('Not found', 404);
}
```

---

## 12. Model Retrieval Pattern

### 12.1 When to Use Each Pattern

| Scenario | Method | Example |
|----------|--------|---------|
| **Model must exist** | `findOrFail()` | `$model = Model::findOrFail($id);` |
| **Null is valid state** | `find()` | `$parent = Model::find($parentId); // null OK` |
| **Query with where** | `firstOrFail()` | `$model = Model::where('code', $x)->firstOrFail();` |
| **Query with where (nullable)** | `first()` | `$model = Model::where('status', 'active')->first();` |

### 12.2 Benefits of findOrFail()

| Aspect | findOrFail() | find() + null check |
|--------|-------------|---------------------|
| **Lines of code** | 1 line | 3-4 lines |
| **Error handling** | Automatic via Handler.php | Manual throw required |
| **Consistency** | Standard Laravel pattern | Inconsistent messages |
| **Readability** | Cleaner, more expressive | Verbose, noisy |

---

## 13. Enum Pattern Rules

### 13.1 Enum Value Size Requirement

**For database storage optimization, enum values MUST be short (max 2-3 characters):**

| Type | ✅ Good | ❌ Bad |
|------|--------|-------|
| **Status** | `ac`, `in`, `pe` | `active`, `inactive`, `pending` |
| **Type** | `df`, `pr`, `tr` | `DEFAULT`, `PREMIUM`, `TRIAL` |
| **Category** | `in`, `out`, `tr` | `income`, `outcome`, `transfer` |

### 13.2 Enum Template

```php
<?php

namespace App\Enums;

enum UserStatus: string
{
    case ACTIVE = 'ac';
    case INACTIVE = 'in';
    case PENDING = 'pe';

    public static function label(?string $value): ?string
    {
        return match($value) {
            self::ACTIVE->value => __('label.active'),
            self::INACTIVE->value => __('label.inactive'),
            self::PENDING->value => __('label.pending'),
            default => null,
        };
    }

    public function getLabel(): ?string
    {
        return self::label($this->value);
    }

    public static function toArray(): array
    {
        return [
            self::ACTIVE->value => self::label(self::ACTIVE->value),
            self::INACTIVE->value => self::label(self::INACTIVE->value),
            self::PENDING->value => self::label(self::PENDING->value),
        ];
    }
}
```

### 13.3 Enum Usage

```php
// In Migration
$table->enum('status', array_keys(UserStatus::toArray()))->default('pe');

// In Model
protected $casts = [
    'status' => UserStatus::class,
];

// In Service
if ($user->status === UserStatus::ACTIVE) {
    // ...
}
```

---

## 14. Common Mistakes

### 14.1 Response Parameter Order

```php
// ❌ WRONG
return AppResponse::success(
    __('message.success'),
    $result  // Array in second parameter
);

// ✅ CORRECT
return AppResponse::success(
    JsonResource::make($result),
    __('message.success')
);
```

### 14.2 Locale Function

```php
// ❌ WRONG
Lang::get('message.success')

// ✅ CORRECT
__('message.success')
```

### 14.3 Try-Catch in Controller

```php
// ❌ WRONG
try {
    DB::transaction(function () {
        // ...
    });
} catch (\Exception $e) {
    // Handle
}

// ✅ CORRECT
DB::transaction(function () {
    // Let Handler.php handle exceptions
});
```

### 14.4 Snake_case Relations

```php
// ❌ WRONG
public function user_profile() {
    return $this->belongsTo(Profile::class);
}

// ✅ CORRECT
public function userProfile() {
    return $this->belongsTo(Profile::class);
}
```

### 14.5 Missing @throws Throwable

```php
// ❌ WRONG
public function store(Request $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        // ...
    });
}

// ✅ CORRECT
/**
 * Store new record.
 *
 * @param Request $request
 * @return JsonResponse
 * @throws Throwable
 */
public function store(Request $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        // ...
    });
}
```

### 14.6 Service Without Interface

```php
// ❌ WRONG
class UserService
{
    // No interface!
}

// ✅ CORRECT
interface UserInterface
{
    // ...
}

class UserService implements UserInterface
{
    // ...
}
```

### 14.7 DB::transaction in Service

```php
// ❌ WRONG
public function create(array $data): Model
{
    return DB::transaction(function () use ($data) {
        return Model::create($data);
    });
}

// ✅ CORRECT
public function create(array $data): Model
{
    $this->requireTransaction();
    return Model::create($data);
}
```

---

## 15. Safe Execution Pattern

### 15.1 What is AppSafe?

**AppSafe** is a helper for executing operations that should **fail silently** without breaking the main application flow.

**Location:** `app/Helpers/AppSafe.php`

**Deep Dive:** See [docs/patterns/safe-execution.md](../patterns/safe-execution.md) for complete documentation.

---

### 15.2 When to Use AppSafe

| ✅ Use AppSafe For | ❌ DON'T Use AppSafe For |
|-------------------|------------------------|
| Sending emails (welcome, receipts) | Database operations (use transactions) |
| SMS/WhatsApp messages | Critical business logic (payments, inventory) |
| Push notifications | Data integrity operations |
| Webhook calls to 3rd parties | Operations that MUST succeed |
| Cache updates | - |
| Analytics tracking | - |
| Logging to external services | - |

---

### 15.3 Core Methods

| Method | Use Case | Return Value |
|--------|----------|--------------|
| `AppSafe::run()` | Simple silent execution | mixed/null |
| `AppSafe::runWithLevel()` | Custom log level | mixed/null |
| `AppSafe::runMaybe()` | Conditional throw | mixed/null |
| `AppSafe::runWithRetry()` | External API calls (auto retry) | mixed/null |
| `AppSafe::runBatch()` | Multiple operations | array |
| `AppSafe::runWithTimeout()` | Long-running operations | mixed/null |

---

### 15.4 Usage Examples

#### Basic Silent Execution
```php
use Daniardev\LaravelTsd\Helpers\AppSafe;

public function register(Request $request)
{
    $user = User::create($request->validated());

    // Silent failure - won't break user registration
    AppSafe::run('Welcome email', fn() =>
        Mail::to($user->email)->send(new WelcomeEmail($user))
    );

    return AppResponse::success($user, 'Registration successful');
}
```

#### With Retry (External APIs)
```php
// Retry 3 times if API fails
$result = AppSafe::runWithRetry('External API call', function() {
    return Http::timeout(10)->get('https://api.example.com/data');
}, maxAttempts: 3);
```

#### Batch Operations
```php
$results = AppSafe::runBatch([
    ['tag' => 'Email user', 'callback' => fn() =>
        $this->emailService->send(...)
    ],
    ['tag' => 'Email admin', 'callback' => fn() =>
        $this->emailService->send(...)
    ],
]);

// Check results if needed
if (!$results['Email admin']['success']) {
    // Log alert
}
```

#### Custom Log Level
```php
// Log as ERROR instead of WARNING
AppSafe::runWithLevel('Admin email', 'error', fn() =>
    $this->emailService->send(...)
);
```

---

### 15.5 Business Domain Examples

#### E-commerce: Order Confirmation
```php
public function store(Request $request)
{
    $order = DB::transaction(function() use ($request) {
        return Order::create($request->validated());
    });

    // Email customer (silent)
    AppSafe::run('Email customer', fn() =>
        $this->emailService->send(
            to: $order->customer->email,
            subject: 'Order Confirmation - ' . config('app.name'),
            mailable: new OrderConfirmationEmail($order)
        )
    );

    // Email warehouse team (silent)
    AppSafe::run('Email warehouse', fn() =>
        $this->emailService->send(
            to: 'warehouse@example.com',
            subject: "New Order: {$order->number}",
            mailable: new NewOrderEmail($order)
        )
    );

    return AppResponse::success($order, 'Order placed successfully');
}
```

#### HR: Job Application
```php
public function store(Request $request)
{
    $application = DB::transaction(function() use ($request) {
        return JobApplication::create($request->validated());
    });

    // Email applicant (silent)
    AppSafe::run('Email applicant', fn() =>
        $this->emailService->send(
            to: $application->email,
            subject: 'Application Received - ' . config('app.name'),
            mailable: new ApplicationReceivedEmail($application)
        )
    );

    // Email HR team (silent)
    AppSafe::run('Email HR', fn() =>
        $this->emailService->send(
            to: 'hr@example.com',
            subject: "New Application: {$application->name}",
            mailable: new NewApplicationEmail($application)
        )
    );

    return AppResponse::success($application, 'Application submitted');
}
```

---

### 15.6 Logging Structure

All failures are automatically logged to `json-daily` channel:

```json
{
  "message": "SafeRun failed: Send welcome email",
  "context": {
    "tag": "Send welcome email",
    "exception_type": "Swift_TransportException",
    "message": "Connection timed out",
    "file": "/app/Services/MailService.php",
    "line": 45,
    "request": {
      "method": "POST",
      "url": "https://api.example.com/register",
      "request_id": "abc123"
    },
    "user": {
      "id": 1,
      "email": "use***@***"
    }
  }
}
```

---

### 15.7 Best Practices

✅ **DO:**
- Use descriptive tags: "Email customer" not "Send email"
- Use `runBatch()` for multiple related operations
- Use `runWithRetry()` for external API calls
- Monitor logs for failure patterns
- Set up alerts for critical failures

❌ **DON'T:**
- Use for database operations
- Use for critical business logic
- Use for payments or inventory operations
- Overuse (only for non-critical side effects)

---

### 15.8 Quick Reference

| Need to... | Use | Example |
|------------|-----|---------|
| **Send email silently** | `AppSafe::run()` | `AppSafe::run('Email user', fn() => Mail::to(...)->send(...))` |
| **Retry external API** | `AppSafe::runWithRetry()` | `AppSafe::runWithRetry('API call', $callback, maxAttempts: 3)` |
| **Multiple side effects** | `AppSafe::runBatch()` | `AppSafe::runBatch([['tag' => '...', 'callback' => fn() => ...]])` |
| **Custom log level** | `AppSafe::runWithLevel()` | `AppSafe::runWithLevel('tag', 'error', $callback)` |

---

## 16. Helper Reference

This section provides quick reference for all helper classes available in the package.

### 16.1 AppQuery

Query helper for pagination and sorting with security features.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `paginate()` | `Builder $query, PaginationData $pagination, ?array $allowedColumns = null` | `LengthAwarePaginator` | Paginate with optional column whitelist |
| `pagination()` | `Builder $query, Request $request, ?array $allowedColumns = null` | `LengthAwarePaginator` | Paginate from request with optional column whitelist |
| `sort()` | `Builder $query, Request $request, ?array $allowedColumns = null` | `Builder` | Apply sorting with optional column whitelist |
| `paginateCollection()` | `Collection $collection, PaginationData $pagination` | `LengthAwarePaginator` | Paginate a collection |

**Usage:**
```php
// Basic usage
return AppQuery::paginate($query, $pagination);

// With security (recommended)
return AppQuery::paginate($query, $pagination, ['id', 'name', 'created_at']);
```

### 16.2 AppRequest

Request validation helper.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `pagination()` | `array $additionalRules = []` | `array` | Get pagination validation rules |

**Usage:**
```php
public function rules(): array
{
    return AppRequest::pagination([
        'filter.search' => ['nullable', 'string'],
        'filter.status' => ['nullable', 'string'],
    ]);
}
```

### 16.3 AppResource

Resource transformation helper.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `pagination()` | `ResourceCollection $data` | `array` | Format pagination metadata |
| `whenLoaded()` | `mixed $resource, JsonResource $data, string $relationship` | `JsonResource` | Conditional relation loading |

**Usage:**
```php
// In collection
public function toArray(Request $request): array
{
    return [
        'data' => EntityResource::collection($this->collection),
        'pagination' => AppResource::pagination($this),
    ];
}
```

### 16.4 AppResponse

Response formatting helper.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `success()` | `?JsonResource $data, ?string $message = null` | `JsonResponse` | Success response |
| `error()` | `?string $message, int $code = 404, ?JsonResource $error = null` | `JsonResponse` | Error response |
| `print()` | `?string $message, array $data = []` | `JsonResponse` | Simple print response |
| `selectionEnums()` | `string $enumClass, string $key, ?string $type = null` | `Collection` | Format enums as selection |
| `selection()` | `Collection $items, string $key, string $value, ?string $type = null` | `Collection` | Format collection as selection |

### 16.5 AppValidation

Custom validation helper.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `unique()` | `Builder $query, string $key, string $value, ?string $id, callable $fail, ?string $attribute = null` | `void` | Case-insensitive unique validation |
| `fail()` | `Validator $validator` | `HttpResponseException` | Throw validation exception |

### 16.6 AppHelper

Utility helper with common functions.

| Method | Description |
|--------|-------------|
| `toSnakeCase(array $data)` | Convert array keys to snake_case |
| `toCamelCase(array $data)` | Convert array keys to camelCase |
| `isCamel(?string $value)` | Check if string is camelCase |
| `toBoolean($value)` | Convert value to boolean |
| `formatCurrency(?float $amount, ...)` | Format currency |
| `formatDate($date, $format)` | Format date |
| `arrayMerge(...$arrays)` | Merge arrays |
| `ifNull($data, $replace)` | Null coalesce |
| `getClass(object $object)` | Get class name |
| `base64Image(string $path)` | Convert image to base64 |
| `generateQrCode(string $data)` | Generate QR code |

### 16.7 AppLog

Logging helper for consistent log formatting.

| Method | Description |
|--------|-------------|
| `getUserContext(?Request $request)` | Get user context for logging |
| `maskEmail(?string $email)` | Mask email for logs |
| `maskPhoneNumber(?string $phoneNumber)` | Mask phone for logs |
| `sanitizeUrl(string $url)` | Sanitize URL for logs |
| `sanitizeTrace(?string $trace)` | Sanitize stack trace |
| `getRequestContext(?Request $request, ...)` | Get request context |

### 16.8 AppPermission

Permission checking helper.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `isAllow()` | `?string $feature` | `bool` | Check if user has permission for feature |

**Usage:**
```php
if (AppPermission::isAllow('users.create')) {
    // User can create users
}
```

### 16.9 AppSecure

Encryption/decryption helper.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `encrypt()` | `string $value` | `string` | Encrypt value using APP_KEY |
| `decrypt()` | `string $encoded` | `string` | Decrypt encrypted value |

### 16.10 AppSafe

Safe execution helper for non-critical operations.

| Method | Description |
|--------|-------------|
| `run($tag, $callback)` | Execute callback silently |
| `runWithLevel($tag, $level, $callback)` | Execute with custom log level |
| `runMaybe($tag, $callback, $silent)` | Execute with optional throw |
| `runWithRetry($tag, $callback, $maxAttempts)` | Execute with retry logic |
| `runBatch($operations)` | Execute multiple operations |

*See Section 15 for detailed AppSafe documentation.*

### 16.11 AppMigration

Migration helper for common patterns.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `useAddress()` | `Blueprint $table` | `void` | Add address columns to table |

**Usage:**
```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    AppMigration::useAddress($table); // Adds address column
    $table->auditFields();
    $table->timestamps();
});
```

---

## 17. Trait Reference

This section provides quick reference for all traits available in the package.

### 17.1 AppTransactional

Transaction enforcement for service classes.

| Method | Visibility | Description |
|--------|------------|-------------|
| `requireTransaction()` | `protected` | Throw exception if not in transaction |

**Usage:**
```php
class UserService implements UserServiceInterface
{
    use AppTransactional;

    public function create(string $name): User
    {
        $this->requireTransaction(); // Must be in DB::transaction
        return User::create(['name' => $name]);
    }
}
```

### 17.2 AppAuditable

Audit trail support for models.

**Adds to model:**
- Audit fields: `created_by`, `updated_by`, `deleted_by`
- Relations: `creator()`, `updater()`, `deleter()`
- Scopes: `createdBy()`, `updatedBy()`, `deletedBy()`, `notDeleted()`
- Accessors: `getCreatorNameAttribute()`, `getUpdaterNameAttribute()`, `getDeleterNameAttribute()`

**Usage:**
```php
class User extends Model
{
    use HasUuids, AppAuditable, SoftDeletes;
}
```

### 17.3 AppPagination

Pagination data extraction from request.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `pagination()` | `Request $request` | `PaginationData` | Extract pagination data from request |

**Usage:**
```php
class UserController extends Controller
{
    use AppPagination;

    public function index(UserRequest $request): JsonResponse
    {
        $items = $this->service->paginate(
            pagination: $this->pagination($request),
            search: $request->input('filter.search')
        );
        return AppResponse::success(new UserCollection($items));
    }
}
```

### 17.4 AppRequestTrait

Request validation trait.

| Method | Parameters | Returns | Description |
|--------|------------|---------|-------------|
| `failedValidation()` | `Validator $validator` | `void` | Handle failed validation with custom format |

**Usage:**
```php
class UserFormRequest extends FormRequest
{
    use AppRequestTrait; // Adds custom validation error handling
}
```

### 17.5 AppMigrationOrderScanner

Migration execution order control.

**Purpose:** Ensures migrations run in correct order when multiple migration files exist.

---

## Quick Reference Tables

### What to Use When

| Need to... | Use | Reference |
|------------|-----|-----------|
| **Name a table** | `snake_case` + plural | Section 1.1 |
| **Name a model** | `PascalCase` + singular | Section 1.2 |
| **Name a relation method** | `camelCase` | Section 1.2 |
| **Return JSON response** | `AppResponse::success(JsonResource, message)` | Section 9 |
| **Wrap write operation** | `DB::transaction()` | Section 10 |
| **Enforce transaction in service** | `$this->requireTransaction()` | Section 10 |
| **Handle business logic error** | `throw new AppException($message, 422)` | Section 11 |
| **Get required model** | `Model::findOrFail($id)` | Section 12 |
| **Get optional model** | `Model::find($id)` | Section 12 |
| **Display locale message** | `__('message.key')` | Section 2 |
| **Create enum** | Short values (max 2-3 chars) | Section 13 |

---

**Last Updated:** 2026-02-24
**Version:** 2.0 (Generic/Universal)