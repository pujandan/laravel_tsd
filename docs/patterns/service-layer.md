# Service Layer Pattern

Detailed guide for implementing the Service Layer Pattern in Laravel applications using this package.

---

## Table of Contents

1. [Overview](#1-overview)
2. [Why Service Layer Pattern](#2-why-service-layer-pattern)
3. [Interface Requirement](#3-interface-requirement)
4. [Service Implementation](#4-service-implementation)
5. [Controller Integration](#5-controller-integration)
6. [Separation of Concerns](#6-separation-of-concerns)
7. [Complete Example](#7-complete-example)
8. [Best Practices](#8-best-practices)

---

## 1. Overview

### Architecture Flow

```
HTTP Request → Controller → Service → Model → Database
                  ↓           ↓
             Transactions  Business Logic
             & JSON        & Validation
```

### Layer Responsibilities

| Layer | Responsibility | Example |
|-------|---------------|---------|
| **Controller** | HTTP handling, validation, transactions | `DB::transaction()`, return JSON responses |
| **Service** | Business logic, calculations, rules | Duplicate checks, data processing, validation |
| **Model** | Data access, relationships | `belongsTo()`, `hasMany()`, queries |
| **Request** | Validation rules | `rules()`, `attributes()` |
| **Resource** | Data transformation | `toArray()`, formatting response |

---

## 2. Why Service Layer Pattern

### Benefits

1. **Separation of Concerns**
   - Controller handles HTTP only
   - Service handles business logic
   - Model handles data access

2. **Testability**
   - Easy to mock services for controller tests
   - Business logic isolated for unit tests

3. **Maintainability**
   - Business logic in one place
   - Easy to find and modify rules

4. **Reusability**
   - Service methods can be called from multiple controllers
   - Business logic can be reused across the application

5. **Clean Architecture**
   - Follows SOLID principles
   - Clear dependency flow

---

## 3. Interface Requirement

### Why Interface is MANDATORY

**ALL Services MUST have Interface** - This is a non-negotiable requirement.

#### Benefits of Interface

1. **Testability** - Easy to mock for unit tests
2. **Maintainability** - Clear contract/implementation separation
3. **Flexibility** - Easy to swap implementations
4. **Dependency Injection** - Laravel container can auto-inject
5. **Clean Architecture** - Follows SOLID principles

### Interface Template

```php
<?php

namespace App\Services\Common\Whatsapp;

use App\Models\WhatsappDevice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Daniardev\LaravelTsd\Data\PaginationData;

interface WhatsappDeviceInterface
{
    /**
     * Get paginated list with filters.
     *
     * ALL filters must be explicit parameters, NO array $filters
     *
     * @param PaginationData $pagination
     * @param string|null $search
     * @param string|null $status
     * @return LengthAwarePaginator
     */
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator;

    /**
     * Find by ID or throw 404.
     *
     * @param string $id
     * @return WhatsappDevice
     */
    public function find(string $id): WhatsappDevice;

    /**
     * Create new record.
     *
     * @param string $deviceId Device ID from WhatsApp
     * @param string $name Device name
     * @param string|null $phone Phone number (optional)
     * @param bool $isActive Whether device is active
     * @return WhatsappDevice
     */
    public function create(
        string $deviceId,
        string $name,
        ?string $phone,
        bool $isActive
    ): WhatsappDevice;

    /**
     * Update existing record.
     *
     * @param string $id Device ID
     * @param string|null $name Device name
     * @param string|null $phone Phone number
     * @param bool|null $isActive Active status
     * @return WhatsappDevice
     */
    public function update(
        string $id,
        ?string $name,
        ?string $phone,
        ?bool $isActive
    ): WhatsappDevice;

    /**
     * Delete record.
     *
     * @param string $id
     * @return WhatsappDevice
     */
    public function delete(string $id): WhatsappDevice;
}
```

**Key Points:**
- **Use named parameters** - All parameters explicitly defined with types
- **Explicit parameter names** - Clear what each parameter is (deviceId, not just $id)
- **PHPDoc @param** - Document each parameter with description
- **Nullable types** - Use `?string`, `?bool` for optional parameters
- **Return type** - Always declare return type

### Interface Rules

| Rule | Description |
|------|-------------|
| **1** | Interface name: `{Entity}Interface` (NOT `{Entity}ServiceInterface`) |
| **2** | All public methods MUST be defined in interface |
| **3** | Method signatures MUST include type hints |
| **4** | Return types MUST be declared |
| **5** | PHPDoc comments are REQUIRED for all methods with parameter descriptions |
| **6** | **MUST use named parameters** - Explicitly define all parameters with descriptive names (NOT array/DTO) |
| **7** | Use nullable types (`?string`, `?bool`) for optional parameters |
| **8** | **CRUD method names**: `paginate`, `find`, `create`, `update`, `delete` (default names) |

### Named Parameters Pattern

**WHY Named Parameters?**

Service methods **MUST use named parameters** instead of array or DTO objects. This provides:

1. **Explicit Parameters** - All parameters are visible in method signature
2. **Self-Documenting** - No need to open another file to see what's required
3. **Type Safety** - Each parameter has explicit type
4. **IDE Support** - Full autocomplete and parameter hints
5. **No Redundancy** - Don't create DTO class just to wrap request data

**Pattern:**

```php
// ❌ WRONG - Using Array
public function create(array $data): Model
{
    $name = $data['name']; // What's in $data? Need to check Request class
    $email = $data['email'];
}

// ❌ WRONG - Using DTO
public function create(UserCreateData $data): Model
{
    $name = $data->name; // Need to open UserCreateData class to see properties
}

// ✅ CORRECT - Using Named Parameters
public function create(
    string $name,
    string $email,
    ?string $phone,
    bool $isActive
): Model {
    // All parameters are explicit and visible!
}
```

**Controller Usage:**

```php
// Call service with named parameters
$user = $this->service->create(
    name: $validated['name'],
    email: $validated['email'],
    phone: $validated['phone'] ?? null,
    isActive: $validated['is_active'] ?? true
);
```

### CRUD Method Names (Default Pattern)

**ALL services SHOULD use these default method names:**

| Method | Purpose | Parameters | Returns |
|--------|---------|------------|---------|
| **paginate** | List with pagination | `PaginationData $pagination`, explicit filter params (e.g., `?string $search`, `?string $status`) | `LengthAwarePaginator` |
| **find** | Find by ID or throw 404 | `string $id` | `Model` |
| **create** | Create new record | Named parameters (required + optional) | `Model` |
| **update** | Update existing record | `string $id` + named parameters (nullable) | `Model` |
| **delete** | Delete record | `string $id` | `Model` |

**Example Interface:**

```php
interface UserServiceInterface
{
    // READ - List & pagination
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator;

    // READ - Single record
    public function find(string $id): User;

    // WRITE - Create with named parameters
    public function create(
        string $name,
        string $email,
        ?string $phone,
        bool $isActive
    ): User;

    // WRITE - Update with nullable named parameters
    public function update(
        string $id,
        ?string $name,
        ?string $email,
        ?string $phone,
        ?bool $isActive
    ): User;

    // WRITE - Delete
    public function delete(string $id): User;
}
```

**When to Use Custom Method Names:**

Only use different method names if:
- Domain-specific operation (e.g., `approve()`, `reject()`, `publish()`)
- Special business logic (e.g., `resetPassword()`, `changeStatus()`)

---

## 4. Service Implementation

### Service Template

```php
<?php

namespace App\Services\Common\Whatsapp;

use App\Models\WhatsappDevice;
use Daniardev\LaravelTsd\Traits\AppTransactional;
use Daniardev\LaravelTsd\Exceptions\AppException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Daniardev\LaravelTsd\Data\PaginationData;
use Daniardev\LaravelTsd\Helpers\AppQuery;

class WhatsappDeviceService implements WhatsappDeviceInterface
{
    use AppTransactional;

    /**
     * Get paginated list with filters.
     *
     * @param PaginationData $pagination Pagination data (page, per_page, sort, sort_by)
     * @param string|null $search Search keyword
     * @param string|null $status Filter by status
     * @return LengthAwarePaginator Paginated result
     */
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $query = WhatsappDevice::query();

        // Apply filters using explicit parameters
        if ($search !== null) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return AppQuery::paginate($query, $pagination);
        // Optional: Specify allowed sort columns for security
        // return AppQuery::paginate($query, $pagination, ['id', 'name', 'status', 'created_at', 'updated_at']);
    }

    /**
     * Find by ID or throw 404.
     *
     * @param string $id Model ID
     * @return WhatsappDevice Found model
     */
    public function find(string $id): WhatsappDevice
    {
        return WhatsappDevice::findOrFail($id);
    }

    /**
     * Create new record.
     *
     * @param string $deviceId Device ID from WhatsApp
     * @param string $name Device name
     * @param string|null $phone Phone number (optional)
     * @param bool $isActive Whether device is active
     * @return WhatsappDevice Created model
     * @throws AppException If device_id already exists
     */
    public function create(
        string $deviceId,
        string $name,
        ?string $phone,
        bool $isActive
    ): WhatsappDevice {
        $this->requireTransaction();

        // Business rule: Validate uniqueness
        $existing = WhatsappDevice::where('device_id', $deviceId)->first();
        if ($existing) {
            throw new AppException('Device ID already exists', 422);
        }

        // Create model with named parameters
        $device = WhatsappDevice::create([
            'device_id' => $deviceId,
            'name' => $name,
            'phone' => $phone,
            'is_active' => $isActive,
            'status' => DeviceStatus::PENDING,
            'qr_code' => null,
            'pairing_code' => null,
        ]);

        return $device->fresh();
    }

    /**
     * Update existing record.
     *
     * @param string $id Device ID
     * @param string|null $name Device name
     * @param string|null $phone Phone number
     * @param bool|null $isActive Active status
     * @return WhatsappDevice Updated model
     * @throws AppException If validation fails
     */
    public function update(
        string $id,
        ?string $name,
        ?string $phone,
        ?bool $isActive
    ): WhatsappDevice {
        $this->requireTransaction();

        $device = $this->find($id);

        // Build update data from named parameters
        $data = [];

        if ($name !== null) {
            $data['name'] = $name;
        }

        if ($phone !== null) {
            $data['phone'] = $phone;
        }

        if ($isActive !== null) {
            $data['is_active'] = $isActive;
            // Business rule: Clear temp data if deactivated
            if (!$isActive) {
                $data['qr_code'] = null;
                $data['pairing_code'] = null;
            }
        }

        $device->update($data);

        return $device->fresh();
    }

    /**
     * Delete record.
     *
     * @param string $id Device ID
     * @return WhatsappDevice Deleted model
     * @throws AppException If device is connected
     */
    public function delete(string $id): WhatsappDevice
    {
        $this->requireTransaction();

        $device = $this->find($id);

        // Business rule: Can't delete connected device
        if ($device->status === DeviceStatus::CONNECTED) {
            throw new AppException('Cannot delete connected device', 422);
        }

        $device->delete();

        return $device;
    }
}
```

### Service Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST implement Interface** | `class Service implements ServiceInterface` |
| 2 | **MUST use AppTransactional trait** | `use AppTransactional;` |
| 3 | **WRITE methods: Call requireTransaction()** | At START of create/update/delete |
| 4 | **READ methods: NO requireTransaction()** | paginate/find/query methods |
| 5 | **MUST NOT use DB::transaction()** | Controller manages transactions |
| 6 | **MUST NOT have try-catch** | Handler.php manages exceptions |
| 7 | **MUST handle all business logic** | Validation, checks, rules |
| 8 | **MUST return Model, not JSON** | Controller handles JSON |
| 9 | **MUST use findOrFail()** | Not find() + null check |
| 10 | **MUST throw AppException for business violations** | With descriptive message |

### READ vs WRITE Methods

**READ Methods (NO requireTransaction):**
```php
// ✅ CORRECT
public function paginate(
    PaginationData $pagination,
    ?string $search = null,
    ?string $status = null
): LengthAwarePaginator {
    // NO requireTransaction()
    $query = Model::query();

    if ($search !== null) {
        $query->where('name', 'like', "%{$search}%");
    }

    if ($status !== null) {
        $query->where('status', $status);
    }

    return AppQuery::paginate($query, $pagination);
}

public function find(string $id): Model
{
    // NO requireTransaction()
    return Model::findOrFail($id);
}
```

**WRITE Methods (WITH requireTransaction):**
```php
// ✅ CORRECT
public function create(array $data): Model
{
    $this->requireTransaction(); // Required at START
    
    // Business logic...
    return Model::create($data);
}

public function update(string $id, array $data): Model
{
    $this->requireTransaction(); // Required at START
    
    $model = $this->find($id);
    $model->update($data);
    return $model->fresh();
}

public function delete(string $id): Model
{
    $this->requireTransaction(); // Required at START
    
    $model = $this->find($id);
    $model->delete();
    return $model;
}
```

---

## 5. Controller Integration

### Controller Template

```php
<?php

namespace App\Http\Controllers\Api\Common;

use Daniardev\LaravelTsd\Helpers\AppResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Common\Whatsapp\WhatsappDeviceFormRequest;
use App\Http\Requests\Api\Common\Whatsapp\WhatsappDeviceRequest;
use App\Http\Resources\Api\Common\Whatsapp\WhatsappDeviceCollection;
use App\Http\Resources\Api\Common\Whatsapp\WhatsappDeviceResource;
use App\Services\Common\Whatsapp\WhatsappDeviceInterface;
use App\Traits\AppPagination;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class WhatsappDeviceController extends Controller
{
    use AppPagination;

    public function __construct(
        private WhatsappDeviceInterface $service
    ) {}

    public function index(WhatsappDeviceRequest $request): JsonResponse
    {
        $filters = $request->input('filter', []);

        $items = $this->service->paginate(
            pagination: $this->pagination($request),
            search: $filters['search'] ?? null,
            status: $filters['status'] ?? null
        );

        return AppResponse::success(
            new WhatsappDeviceCollection($items),
            __('message.successLoaded')
        );
    }

    /**
     * Store new record.
     *
     * @param WhatsappDeviceFormRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(WhatsappDeviceFormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Call service with named parameters
            $item = $this->service->create(
                deviceId: $validated['device_id'],
                name: $validated['name'],
                phone: $validated['phone'] ?? null,
                isActive: $validated['is_active'] ?? true
            );

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successSaved')
            );
        });
    }

    /**
     * Update existing record.
     *
     * @param WhatsappDeviceFormRequest $request
     * @param string $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(WhatsappDeviceFormRequest $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $validated = $request->validated();

            // Call service with named parameters
            $item = $this->service->update(
                id: $id,
                name: $validated['name'] ?? null,
                phone: $validated['phone'] ?? null,
                isActive: $validated['is_active'] ?? null
            );

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successUpdated')
            );
        });
    }

    /**
     * Delete record.
     *
     * @param string $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function destroy(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $item = $this->service->delete($id);

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successDeleted')
            );
        });
    }
}
```

### Controller Rules

| # | Rule | Example |
|---|------|---------|
| 1 | **MUST inject Service via constructor** | Use Interface type: `ServiceInterface $service` |
| 2 | **MUST wrap write operations with DB::transaction()** | store(), update(), destroy() |
| 3 | **MUST NOT wrap read operations** | index(), show() |
| 4 | **MUST add @throws Throwable** | For methods with DB::transaction() |
| 5 | **MUST return via AppResponse::success()** | Not raw response()->json() |
| 6 | **MUST use __() for locale** | Not Lang::get() |
| 7 | **MUST NOT have try-catch** | Handler.php manages exceptions |
| 8 | **MUST NOT put business logic** | All in service |

---

## 6. Separation of Concerns

### What Goes Where

#### Controller (HTTP Layer)
**✅ DO:**
- Validate requests (Form Request)
- Wrap write operations with `DB::transaction()`
- Return JSON responses via `AppResponse::success()`
- HTTP-specific concerns

**❌ DON'T:**
- Business logic
- Direct database queries
- Data validation rules
- Business calculations

#### Service (Business Logic Layer)
**✅ DO:**
- Business rule validation
- Data processing and transformation
- Business calculations
- Data integrity checks
- Call external services
- Throw `AppException` for violations

**❌ DON'T:**
- `DB::transaction()` (controller's job)
- Return JSON responses
- HTTP-specific logic
- Try-catch blocks

#### Model (Data Layer)
**✅ DO:**
- Database queries
- Relationships
- Scopes
- Mutators/accessors

**❌ DON'T:**
- Business logic
- Return JSON
- HTTP concerns

---

## 7. Complete Example

### Scenario: WhatsApp Device Management

#### Business Requirements:
1. Device ID must be unique
2. Can't delete connected device
3. Reset QR code when deactivated
4. Can't change device_id after creation

#### Interface:
```php
<?php

namespace App\Services\Common\Whatsapp;

use App\Models\WhatsappDevice;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Daniardev\LaravelTsd\Data\PaginationData;

interface WhatsappDeviceInterface
{
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator;
    public function find(string $id): WhatsappDevice;

    public function create(
        string $deviceId,
        string $name,
        ?string $phone,
        bool $isActive
    ): WhatsappDevice;

    public function update(
        string $id,
        ?string $name,
        ?string $phone,
        ?bool $isActive
    ): WhatsappDevice;

    public function delete(string $id): WhatsappDevice;
}
```

#### Service (Business Logic):
```php
<?php

namespace App\Services\Common\Whatsapp;

use App\Models\WhatsappDevice;
use Daniardev\LaravelTsd\Traits\AppTransactional;
use Daniardev\LaravelTsd\Exceptions\AppException;

class WhatsappDeviceService implements WhatsappDeviceInterface
{
    use AppTransactional;

    public function create(
        string $deviceId,
        string $name,
        ?string $phone,
        bool $isActive
    ): WhatsappDevice {
        $this->requireTransaction();

        // Business rule 1: Device ID must be unique
        $existing = WhatsappDevice::where('device_id', $deviceId)->first();
        if ($existing) {
            throw new AppException('Device ID already exists', 422);
        }

        return WhatsappDevice::create([
            'device_id' => $deviceId,
            'name' => $name,
            'phone' => $phone,
            'is_active' => $isActive,
            'status' => DeviceStatus::PENDING,
            'qr_code' => null,
            'pairing_code' => null,
        ]);
    }

    public function update(
        string $id,
        ?string $name,
        ?string $phone,
        ?bool $isActive
    ): WhatsappDevice {
        $this->requireTransaction();

        $device = $this->find($id);

        // Build update data from named parameters
        $data = [];

        if ($name !== null) {
            $data['name'] = $name;
        }

        if ($phone !== null) {
            $data['phone'] = $phone;
        }

        // Business rule 3: Reset QR when deactivated
        if ($isActive !== null) {
            $data['is_active'] = $isActive;
            if (!$isActive) {
                $data['qr_code'] = null;
                $data['pairing_code'] = null;
            }
        }

        // Business rule 4: Can't change device_id
        // (deviceId is not in the update data)

        $device->update($data);
        return $device->fresh();
    }

    public function delete(string $id): WhatsappDevice
    {
        $this->requireTransaction();

        $device = $this->find($id);

        // Business rule 2: Can't delete connected device
        if ($device->status === DeviceStatus::CONNECTED) {
            throw new AppException('Cannot delete connected device', 422);
        }

        $device->delete();
        return $device;
    }
}
```

#### Controller (HTTP Layer):
```php
<?php

namespace App\Http\Controllers\Api\Common;

use App\Services\Common\Whatsapp\WhatsappDeviceInterface;
use Daniardev\LaravelTsd\Helpers\AppResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Common\Whatsapp\WhatsappDeviceFormRequest;
use App\Http\Resources\Api\Common\Whatsapp\WhatsappDeviceResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WhatsappDeviceController extends Controller
{
    public function __construct(
        private WhatsappDeviceInterface $service
    ) {}

    public function store(WhatsappDeviceFormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Call service with named parameters
            $item = $this->service->create(
                deviceId: $validated['device_id'],
                name: $validated['name'],
                phone: $validated['phone'] ?? null,
                isActive: $validated['is_active'] ?? true
            );

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successSaved')
            );
        });
    }

    public function update(WhatsappDeviceFormRequest $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $validated = $request->validated();

            // Call service with named parameters
            $item = $this->service->update(
                id: $id,
                name: $validated['name'] ?? null,
                phone: $validated['phone'] ?? null,
                isActive: $validated['is_active'] ?? null
            );

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successUpdated')
            );
        });
    }

    public function destroy(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $item = $this->service->delete($id);

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successDeleted')
            );
        });
    }
}
```

---

## 8. Best Practices

### ✅ DO

1. **Keep services focused** - One service per domain entity
2. **Use interfaces** - Always create interface for service
3. **Use named parameters** - Explicitly define all parameters with types (NOT array/DTO)
4. **Use default CRUD names** - `paginate`, `find`, `create`, `update`, `delete`
5. **Inject dependencies** - Use constructor injection
6. **Use type hints** - All parameters and return types
7. **Return models** - Let controller handle JSON
8. **Throw exceptions** - Use AppException for business violations
9. **Keep methods short** - Max 30 lines, extract if longer
10. **Use findOrFail()** - Not find() + null check

### ❌ DON'T

1. **Don't use array parameters** - Use named parameters instead
2. **Don't create DTO for simple CRUD** - Redundant if just wrapping request data
3. **Don't use DB::transaction() in service** - Controller's job
4. **Don't use try-catch** - Handler.php manages exceptions
5. **Don't return JSON** - Controller's job
6. **Don't put business logic in controller** - Service's job
7. **Don't call Model directly in controller** - Use service
8. **Don't create service without interface** - Interface is mandatory
9. **Don't skip requireTransaction()** - Enforce transaction usage
10. **Don't make services too large** - Split if needed

---

## Quick Reference

| Need to... | Solution |
|------------|----------|
| **Create new service** | 1. Create Interface, 2. Create Service implementing Interface |
| **Define method parameters** | Use named parameters (NOT array/DTO) |
| **CRUD method names** | Use `paginate`, `find`, `create`, `update`, `delete` |
| **Optional parameters** | Use nullable types: `?string`, `?bool`, `?int` |
| **Add business logic** | Put in service method, not controller |
| **Validate business rules** | In service, throw AppException if violated |
| **Call service from controller** | Inject via constructor, call with named parameters |
| **Return data from service** | Return Model, not JSON |
| **Handle errors in service** | Throw AppException, let Handler.php handle |
| **Ensure transaction** | Controller wraps with DB::transaction(), service calls requireTransaction() |

### Named Parameters Quick Guide

```php
// Interface - Define with named parameters
public function create(
    string $name,      // Required
    string $email,     // Required
    ?string $phone,    // Optional (nullable)
    bool $isActive     // Required with default in controller
): Model;

// Service - Use named parameters directly
public function create(string $name, string $email, ?string $phone, bool $isActive): Model
{
    // Use $name, $email, $phone, $isActive directly
    return Model::create([...]);
}

// Controller - Call with named parameters
$this->service->create(
    name: $validated['name'],
    email: $validated['email'],
    phone: $validated['phone'] ?? null,
    isActive: $validated['is_active'] ?? true
);
```

---

**Related Patterns:**
- [Database Transaction Pattern](./database-transaction.md)
- [Error Handling Pattern](./error-handling.md)
- [Model Retrieval Pattern](./model-retrieval.md)

---

**Last Updated:** 2026-03-21
