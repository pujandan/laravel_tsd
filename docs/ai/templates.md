# Implementation Templates

This file contains all implementation templates for each layer. Copy and customize these templates for your needs.

---

## Table of Contents

1. [Controller Template](#1-controller-template)
2. [Service Interface Template](#2-service-interface-template)
3. [Service Implementation Template](#3-service-implementation-template)
4. [Model Template](#4-model-template)
5. [Index Request Template](#5-index-request-template)
6. [Form Request Template](#6-form-request-template)
7. [Resource Template](#7-resource-template)
8. [Collection Template](#8-collection-template)
9. [Migration Template](#9-migration-template)

---

## 1. Controller Template

**File Location:** `app/Http/Controllers/Api/{Module}/{Entity}/{Entity}Controller.php`

```php
<?php

namespace App\Http\Controllers\Api\{Module}\{Entity};

use Daniardev\LaravelTsd\Helpers\AppResponse;
use Daniardev\LaravelTsd\Traits\AppPagination;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\{Module}\{Entity}\{Entity}FormRequest;
use App\Http\Requests\Api\{Module}\{Entity}\{Entity}Request;
use App\Http\Resources\Api\{Module}\{Entity}\{Entity}Collection;
use App\Http\Resources\Api\{Module}\{Entity}\{Entity}Resource;
use App\Services\{Module}\{Entity}\{Entity}Interface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Throwable;

class {Entity}Controller extends Controller
{
    use AppPagination;

    public function __construct(
        private readonly {Entity}Interface $service
    ) {}

    public function index({Entity}Request $request): JsonResponse
    {
        $filters = $request->input('filter', []);

        $items = $this->service->paginate(
            pagination: $this->pagination($request),
            search: $filters['search'] ?? null,
            status: $filters['status'] ?? null
            // Add all filter parameters as explicit, not ...$additionalFilters
        );

        return AppResponse::success(
            new {Entity}Collection($items),
            __('message.successLoaded')
        );
    }

    /**
     * Store new record.
     *
     * @param {Entity}FormRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store({Entity}FormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Call service with ALL explicit parameters
            $item = $this->service->create(
                name: $validated['name'],
                email: $validated['email'],
                status: $validated['status'] ?? null,
                otherField: $validated['other_field'] ?? null,
                // Add ALL fields as explicit parameters, NO array merge
            );

            return AppResponse::success(
                {Entity}Resource::make($item),
                __('message.successSaved')
            );
        });
    }

    /**
     * Show single record.
     *
     * @param string $id
     * @return JsonResponse
     */
    public function show(string $id): JsonResponse
    {
        $item = $this->service->find($id);

        return AppResponse::success(
            {Entity}Resource::make($item),
            __('message.successLoaded')
        );
    }

    /**
     * Update existing record.
     *
     * @param {Entity}FormRequest $request
     * @param string $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function update({Entity}FormRequest $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $validated = $request->validated();

            // Call service with ALL explicit parameters
            $item = $this->service->update(
                id: $id,
                name: $validated['name'] ?? null,
                email: $validated['email'] ?? null,
                status: $validated['status'] ?? null,
                otherField: $validated['other_field'] ?? null,
                // Add ALL fields as explicit parameters, NO array merge
            );

            return AppResponse::success(
                {Entity}Resource::make($item),
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
                {Entity}Resource::make($item),
                __('message.successDeleted')
            );
        });
    }

    /**
     * Custom action returning array data.
     *
     * @param string $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function customAction(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $result = $this->service->customAction($id);

            // Wrap array with JsonResource::make()
            return AppResponse::success(
                JsonResource::make($result),
                __('message.actionCompleted')
            );
        });
    }
}
```

### Template Usage Guide

**Placeholders to replace:**
- `{Module}` - Your module name (e.g., `Common`, `Finance`, `Sales`)
- `{Entity}` - Your entity name (e.g., `User`, `Product`, `Order`)
- `{entity}` - Entity in kebab-case (e.g., `users`, `products`, `orders`)

**Example 1: E-commerce - Product**
```php
// Module: Sales, Entity: Product

namespace App\Http\Controllers\Api\Sales\Product;

use App\Services\Sales\Product\ProductInterface;

class ProductController extends Controller
{
    private ProductInterface $service;
    // ...
}
```

**Example 2: HR - Employee**
```php
// Module: Hr, Entity: Employee

namespace App\Http\Controllers\Api\Hr\Employee;

use App\Services\Hr\Employee\EmployeeInterface;

class EmployeeController extends Controller
{
    private EmployeeInterface $service;
    // ...
}
```

**Example 3: Common - User**
```php
// Module: Common, Entity: User

namespace App\Http\Controllers\Api\Common\User;

use App\Services\Common\User\UserInterface;

class UserController extends Controller
{
    private UserInterface $service;
    // ...
}
```

---

## 2. Service Interface Template

**File Location:** `app/Services/{Module}/{Entity}/{Entity}Interface.php`

```php
<?php

namespace App\Services\{Module}\{Entity};

use App\Models\{Entity};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Daniardev\LaravelTsd\Data\PaginationData;

interface {Entity}Interface
{
    /**
     * Get paginated list with filters.
     *
     * ALL filters must be explicit parameters, NO array $filters
     *
     * @param PaginationData $pagination
     * @param string|null $search Search keyword
     * @param string|null $status Filter by status
     * @param string|null $otherField Add all filter fields explicitly
     * @return LengthAwarePaginator
     */
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null,
        ?string $otherField = null
    ): LengthAwarePaginator;

    /**
     * Find by ID or throw 404.
     *
     * @param string $id
     * @return {Entity}
     */
    public function find(string $id): {Entity};

    /**
     * Create new record.
     *
     * ALL required fields must be explicit parameters, NO array $data for main fields
     *
     * @param string $name Required field
     * @param string $email Required field
     * @param string|null $status Optional field (add ALL fields as parameters)
     * @param string|null $otherField Optional field
     * @return {Entity}
     */
    public function create(
        string $name,
        string $email,
        ?string $status = null,
        ?string $otherField = null
    ): {Entity};

    /**
     * Update existing record.
     *
     * ALL updatable fields must be explicit parameters, NO array $data for main fields
     *
     * @param string $id
     * @param string|null $name Optional field (add ALL fields as parameters)
     * @param string|null $email Optional field
     * @param string|null $status Optional field
     * @param string|null $otherField Optional field
     * @return {Entity}
     */
    public function update(
        string $id,
        ?string $name = null,
        ?string $email = null,
        ?string $status = null,
        ?string $otherField = null
    ): {Entity};

    /**
     * Delete record.
     *
     * @param string $id
     * @return {Entity}
     */
    public function delete(string $id): {Entity};
}
```

---

## 3. Service Implementation Template

**File Location:** `app/Services/{Module}/{Entity}/{Entity}Service.php`

```php
<?php

namespace App\Services\{Module}\{Entity};

use App\Models\{Entity};
use Daniardev\LaravelTsd\Traits\AppTransactional;
use Daniardev\LaravelTsd\Exceptions\AppException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Daniardev\LaravelTsd\Data\PaginationData;
use Daniardev\LaravelTsd\Helpers\AppQuery;

class {Entity}Service implements {Entity}Interface
{
    use AppTransactional;

    /**
     * Get paginated list with filters.
     *
     * @param PaginationData $pagination Pagination data (page, per_page, sort, sort_by)
     * @param string|null $search Search keyword
     * @param string|null $status Filter by status
     * @param string|null $otherField Filter by other field
     * @return LengthAwarePaginator Paginated result
     */
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null,
        ?string $otherField = null
    ): LengthAwarePaginator {
        $query = {Entity}::query();

        // Apply filters using explicit parameters
        if ($search !== null) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($otherField !== null) {
            $query->where('other_field', $otherField);
        }

        return AppQuery::paginate($query, $pagination);
        // Optional: Specify allowed sort columns for security
        // return AppQuery::paginate($query, $pagination, ['id', 'name', 'field_name', 'created_at', 'updated_at']);
    }

    /**
     * Find by ID or throw 404.
     *
     * @param string $id Entity ID
     * @return {Entity} Found model
     */
    public function find(string $id): {Entity}
    {
        return {Entity}::findOrFail($id);
    }

    /**
     * Create new record.
     *
     * @param string $name Entity name
     * @param string $email Entity email
     * @param string|null $status Entity status (optional)
     * @param string|null $otherField Other field value (optional)
     * @return {Entity} Created model
     * @throws AppException If email already exists
     */
    public function create(
        string $name,
        string $email,
        ?string $status = null,
        ?string $otherField = null
    ): {Entity} {
        $this->requireTransaction();

        // Business logic: Validate uniqueness using explicit params
        $existing = {Entity}::where('email', $email)->first();
        if ($existing) {
            throw new AppException('Email already exists', 422);
        }

        // Build create data with ALL explicit parameters
        $createData = [
            'name' => $name,
            'email' => $email,
            'status' => $status ?? '{DefaultValue}',
            'other_field' => $otherField,
        ];

        // Remove null values for optional fields
        $createData = array_filter($createData, fn($value) => $value !== null);

        // Create model
        $entity = {Entity}::create($createData);

        return $entity->fresh();
    }

    /**
     * Update existing record.
     *
     * @param string $id Entity ID
     * @param string|null $name Entity name (optional)
     * @param string|null $email Entity email (optional)
     * @param string|null $status Entity status (optional)
     * @param string|null $otherField Other field value (optional)
     * @return {Entity} Updated model
     * @throws AppException If email already exists or no data to update
     */
    public function update(
        string $id,
        ?string $name = null,
        ?string $email = null,
        ?string $status = null,
        ?string $otherField = null
    ): {Entity} {
        $this->requireTransaction();

        $entity = $this->find($id);

        // Build update data from ALL explicit parameters
        $updateData = [];

        if ($name !== null) {
            $updateData['name'] = $name;
        }

        if ($email !== null) {
            // Business logic: Validate uniqueness if email is being updated
            $existing = {Entity}::where('email', $email)
                ->where('id', '!=', $id)
                ->first();
            if ($existing) {
                throw new AppException('Email already exists', 422);
            }
            $updateData['email'] = $email;
        }

        if ($status !== null) {
            $updateData['status'] = $status;
        }

        if ($otherField !== null) {
            $updateData['other_field'] = $otherField;
        }

        // Business logic: Prevent immutable field updates
        unset($updateData['immutable_field']);

        if (empty($updateData)) {
            throw new AppException('No data to update', 422);
        }

        $entity->update($updateData);

        return $entity->fresh();
    }

    /**
     * Delete record.
     *
     * @param string $id Entity ID
     * @return {Entity} Deleted model
     * @throws AppException If record is active and cannot be deleted
     */
    public function delete(string $id): {Entity}
    {
        $this->requireTransaction();

        $entity = $this->find($id);

        // Business logic: Validation before delete
        if ($entity->status === 'active') {
            throw new AppException('Cannot delete active record', 422);
        }

        $entity->delete();

        return $entity;
    }
}
```

### Service Implementation Notes

**READ methods (NO requireTransaction):**
- `paginate()` - For listing with filters
- `find()` - For single record retrieval
- Any custom query methods

**WRITE methods (WITH requireTransaction):**
- `create()` - Must call `$this->requireTransaction()` at start
- `update()` - Must call `$this->requireTransaction()` at start
- `delete()` - Must call `$this->requireTransaction()` at start
- Any custom methods that modify data

---

## 4. Model Template

**File Location:** `app/Models/{Entity}.php`

```php
<?php

namespace App\Models;

use Daniardev\LaravelTsd\Traits\AppAuditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class {Entity} extends Model
{
    use HasUuids, AppAuditable, SoftDeletes;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relation to user (creator).
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relation to related model.
     *
     * @return BelongsTo
     */
    public function relatedModel(): BelongsTo
    {
        return $this->belongsTo(RelatedModel::class);
    }
}
```

### Model Notes

**Required Traits:**
- `HasUuids` - For UUID primary keys
- `AppAuditable` - For audit trails (created_by, updated_by, deleted_by)
- `SoftDeletes` - Only if you need `deleted_by` field

**Relation Naming:**
- Use `camelCase` for relation methods
- Example: `user()`, `relatedModel()`, `category()`
- NOT: `user_relation()`, `related_model()`

---

## 5. Index Request Template

**File Location:** `app/Http/Requests/Api/{Module}/{Entity}/{Entity}Request.php`

```php
<?php

namespace App\Http\Requests\Api\{Module}\{Entity};

use Daniardev\LaravelTsd\Helpers\AppRequest;
use Daniardev\LaravelTsd\Traits\AppRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class {Entity}Request extends FormRequest
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
            'filter.field_name' => __('label.fieldName'),
        ];
    }

    public function rules(): array
    {
        return AppRequest::pagination([
            'filter.search' => ['nullable', 'string'],
            'filter.status' => ['nullable', 'string'],
            'filter.field_name' => ['nullable', 'string'],
        ]);
    }
}
```

---

## 6. Form Request Template

**File Location:** `app/Http/Requests/Api/{Module}/{Entity}/{Entity}FormRequest.php`

```php
<?php

namespace App\Http\Requests\Api\{Module}\{Entity};

use Daniardev\LaravelTsd\Traits\AppRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class {Entity}FormRequest extends FormRequest
{
    use AppRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function attributes()
    {
        return [
            'field_name' => __('label.fieldName'),
            'email' => __('label.email'),
            'phone_number' => __('label.phoneNumber'),
        ];
    }

    public function rules(): array
    {
        return [
            'field_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:{table_name},email'],
            'phone_number' => ['required', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
            'nested_data' => ['nullable', 'array'],
            'nested_data.*.field' => ['required', 'string'],
        ];
    }
}
```

---

## 7. Resource Template

**File Location:** `app/Http/Resources/Api/{Module}/{Entity}/{Entity}Resource.php`

```php
<?php

namespace App\Http\Resources\Api\{Module}\{Entity};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Entity}Resource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_name' => $this->field_name,
            'status' => $this->status,

            // Nested relations - use dedicated Resource
            'user' => $this->when($this->relationLoaded('user'), function () {
                return UserResource::make($this->user);
            }),

            'related_model' => $this->when($this->relationLoaded('relatedModel'), function () {
                return RelatedModelResource::make($this->related_model);
            }),

            // Audit information
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

### Resource Notes

**Important:**
- Return array directly without case transformation
- Use `$this->when()` for conditional loading
- Use dedicated Resource classes for relations (don't inline)
- Include audit information (created/updated)

---

## 8. Collection Template

**File Location:** `app/Http/Resources/Api/{Module}/{Entity}/{Entity}Collection.php`

```php
<?php

namespace App\Http\Resources\Api\{Module}\{Entity};

use Daniardev\LaravelTsd\Helpers\AppResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class {Entity}Collection extends ResourceCollection
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
            'data' => {Entity}Resource::collection($this->collection),
            'pagination' => AppResource::pagination($this),
        ];
    }
}
```

---

## 9. Migration Template

**File Location:** `database/migrations/YYYY_MM_DD_HHMMSS_create_{table_name}_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('{table_name}', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign keys
            $table->foreignUuid('user_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignUuid('related_model_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('set null');

            // Columns
            $table->string('field_name');
            $table->string('email')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_active')->default(true);

            // Audit fields
            $table->auditFields();

            $table->timestamps(6);
            $table->softDeletes(); // Add this if you need deleted_by
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('{table_name}');
    }
};
```

### Migration Notes

**auditFields() macro adds:**
- `created_by` (UUID, nullable, FK to users)
- `updated_by` (UUID, nullable, FK to users)
- `deleted_by` (UUID, nullable, FK to users) - ONLY if softDeletes() is present
- Indexes on all audit columns
- Foreign keys with ON DELETE SET NULL

**Table Naming:**
- Use `snake_case` + plural
- Example: `users`, `products`, `category_accounts`

---

## Template Customization Guide

### Step-by-Step: Create Complete CRUD

**Step 1: Replace placeholders**
```
{Module} → Common, Finance, Sales, Hr, etc.
{Entity} → User, Product, Order, Employee, etc.
{entity} → users, products, orders, employees, etc.
{table_name} → users, products, orders, employees, etc.
```

**Step 2: Customize business logic in service**
- Add validation rules in `create()`
- Add business rules in `update()` and `delete()`
- Add custom filters in `paginate()`

**Step 3: Customize validation in requests**
- Add field-specific rules in `FormRequest`
- Add filter-specific rules in `IndexRequest`
- Add custom attribute names

**Step 4: Customize resource output**
- Add fields to display in `Resource`
- Add relations to load
- Customize audit information if needed

**Step 5: Customize migration**
- Add columns needed
- Add indexes for frequently queried columns
- Add foreign key constraints

---

## Business Domain Examples

### Example 1: E-commerce - Product

**Files:**
- `Sales/ProductController.php`
- `Sales/Product/ProductInterface.php`
- `Sales/Product/ProductService.php`
- `Product.php` (Model)
- `products` table

**Business Logic Examples:**
```php
// In ProductService::create(string $sku, string $name, float $price, ?string $status = null)
$this->requireTransaction();

// Validate SKU uniqueness using explicit parameter
$existing = Product::where('sku', $sku)->first();
if ($existing) {
    throw new AppException('SKU already exists', 422);
}

// Build create data with ALL explicit parameters
$createData = [
    'sku' => $sku,
    'name' => $name,
    'price' => $price,
    'status' => $status ?? ProductStatus::DRAFT,
];

return Product::create($createData);
```

### Example 2: HR - Employee

**Files:**
- `Hr/EmployeeController.php`
- `Hr/Employee/EmployeeInterface.php`
- `Hr/Employee/EmployeeService.php`
- `Employee.php` (Model)
- `employees` table

**Business Logic Examples:**
```php
// In EmployeeService::create(string $employeeId, string $name, string $email, ?string $department = null)
$this->requireTransaction();

// Validate employee ID uniqueness using explicit parameter
$existing = Employee::where('employee_id', $employeeId)->first();
if ($existing) {
    throw new AppException('Employee ID already exists', 422);
}

// Build create data with ALL explicit parameters
$createData = [
    'employee_id' => $employeeId,
    'name' => $name,
    'email' => $email,
    'department' => $department,
    'employment_status' => EmploymentStatus::ACTIVE,
];

return Employee::create($createData);
```

### Example 3: Common - User

**Files:**
- `Common/UserController.php`
- `Common/User/UserInterface.php`
- `Common/User/UserService.php`
- `User.php` (Model)
- `users` table

**Business Logic Examples:**
```php
// In UserService::create(string $name, string $email, string $password, ?string $phone = null)
$this->requireTransaction();

// Validate email uniqueness using explicit parameter
$existing = User::where('email', $email)->first();
if ($existing) {
    throw new AppException('Email already exists', 422);
}

// Hash password from explicit parameter
$hashedPassword = bcrypt($password);

// Build create data with ALL explicit parameters
$createData = [
    'name' => $name,
    'email' => $email,
    'password' => $hashedPassword,
    'phone' => $phone,
    'status' => UserStatus::ACTIVE,
];

return User::create($createData);
```

---

## Quick Reference: Which Template to Use?

| Task | Template |
|------|----------|
| **Create controller** | [Controller Template](#1-controller-template) |
| **Create service interface** | [Service Interface Template](#2-service-interface-template) |
| **Create service** | [Service Implementation Template](#3-service-implementation-template) |
| **Create model** | [Model Template](#4-model-template) |
| **Create index request** | [Index Request Template](#5-index-request-template) |
| **Create form request** | [Form Request Template](#6-form-request-template) |
| **Create resource** | [Resource Template](#7-resource-template) |
| **Create collection** | [Collection Template](#8-collection-template) |
| **Create migration** | [Migration Template](#9-migration-template) |
| **Model with images** | [Model with AppHasImages](#bonus-model-with-images) |

---

## Bonus: Model with AppHasImages Trait

**When to use:** Model needs to handle image uploads (single or multiple images).

**⚠️ CRITICAL PATTERN:** Follows Youmrah project pattern - URLs generated in Resources, NOT stored in database.

### Migration Template (with Image Fields - NO url field!)

```php
// database/migrations/YYYY_MM_DD_HHMMSS_create_boardings_table.php

Schema::create('boardings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('title');
    $table->text('description')->nullable();
    $table->integer('order')->unsigned();
    $table->boolean('is_active')->default(true);

    // Only path and disk - NO url field!
    $table->string('image_path');
    $table->string('image_disk')->nullable();

    $table->timestamps(6);
    $table->auditFields();
});
```

### Model Template (with AppHasImages)

```php
// app/Models/Boarding.php

<?php

namespace App\Models;

use Daniardev\LaravelTsd\Traits\AppAuditable;
use Daniardev\LaravelTsd\Traits\AppHasImages;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Boarding extends Model
{
    use HasUuids, AppAuditable, AppHasImages;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Optional: Customize storage config
    public function getImageDiskConfig(): array
    {
        return [
            'disk' => \Daniardev\LaravelTsd\Enums\Disk::FILES,
            'directory' => \App\Enums\Directory::BOARDINGS, // Or 'boardings' string
            // Note: both can use enum directly OR with ->value
            // Visibility is auto-detected from disk config
        ];
    }
}
```

### Form Request Template (with Image Validation)

```php
// app/Http/Requests/Api/Common/Boarding/BoardingFormRequest.php

<?php

namespace App\Http\Requests\Api\Common\Boarding;

use Daniardev\LaravelTsd\Traits\AppRequestTrait;
use Illuminate\Foundation\Http\FormRequest;

class BoardingFormRequest extends FormRequest
{
    use AppRequestTrait;

    public function authorize(): bool
    {
        return true;
    }

    public function attributes(): array
    {
        return [
            'title' => __('label.title'),
            'description' => __('label.description'),
            'image' => __('label.image'),
            'order' => __('label.order'),
            'is_active' => __('label.isActive'),
        ];
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['required', function ($attribute, $value, $fail) {
                // Support both file upload and base64
                if (is_string($value)) {
                    // Base64 validation
                    if (!preg_match('/^data:image\/(jpg|jpeg|png|webp);base64,/', $value)) {
                        $fail(__('validation.image', ['attribute' => __('label.image')]));
                    }
                } elseif (!($value instanceof \Illuminate\Http\UploadedFile)) {
                    $fail(__('validation.image', ['attribute' => __('label.image')]));
                }
            }],
            'order' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
```

### Controller Template (with Image Upload)

```php
// app/Http/Controllers/Api/Common/Boarding/BoardingController.php

<?php

namespace App\Http\Controllers\Api\Common\Boarding;

use Daniardev\LaravelTsd\Helpers\AppResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Common\Boarding\BoardingFormRequest;
use App\Http\Resources\Api\Common\Boarding\BoardingResource;
use App\Models\Boarding;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class BoardingController extends Controller
{
    public function store(BoardingFormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $item = new Boarding();
            $item->title = $validated['title'];
            $item->description = $validated['description'] ?? null;
            $item->order = $validated['order'];
            $item->is_active = $validated['is_active'] ?? true;

            // Upload image - auto-sets image_path, image_disk
            $item->uploadImage($validated['image']);
            $item->save();

            return AppResponse::success(
                BoardingResource::make($item),
                __('message.successSaved')
            );
        });
    }

    public function update(BoardingFormRequest $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $validated = $request->validated();

            $item = Boarding::findOrFail($id);

            $item->title = $validated['title'] ?? $item->title;
            $item->description = $validated['description'] ?? $item->description;
            $item->order = $validated['order'] ?? $item->order;
            $item->is_active = $validated['is_active'] ?? $item->is_active;

            // Upload new image if provided (auto-deletes old image)
            if (isset($validated['image'])) {
                $item->uploadImage($validated['image']);
            }

            $item->save();

            return AppResponse::success(
                BoardingResource::make($item),
                __('message.successUpdated')
            );
        });
    }

    public function destroy(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            // Image auto-deleted by trait's deleting event
            $item = Boarding::findOrFail($id);
            $item->delete();

            return AppResponse::success(
                BoardingResource::make($item),
                __('message.successDeleted')
            );
        });
    }
}
```

### Resource Template (with Dynamic URL Generation)

```php
// app/Http/Resources/Api/Common/Boarding/BoardingResource.php

<?php

namespace App\Http\Resources\Api\Common\Boarding;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BoardingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'order' => $this->order,
            'is_active' => $this->is_active,

            // Storage fields from database
            'image_path' => $this->image_path,
            'image_disk' => $this->image_disk,

            // URL generated dynamically (NOT stored in database!)
            'image_url' => $this->when(
                $this->image_path && $this->image_disk,
                fn() => $this->getImageUrl()
            ),

            // Audit information
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

    /**
     * Get image URL based on storage visibility.
     * Private storage uses temporary URL with authentication token.
     * Public storage uses direct URL.
     */
    protected function getImageUrl(): string
    {
        // For S3/S3-private/MinIO - use temporary URL with token
        if (method_exists(Storage::disk($this->image_disk), 'temporaryUrl')) {
            return Storage::disk($this->image_disk)->temporaryUrl(
                $this->image_path,
                now()->addMinutes(30) // URL expires in 30 minutes
            );
        }

        // For local/public disks - return direct URL
        return Storage::disk($this->image_disk)->url($this->image_path);
    }
}
```

**📘 Full AppHasImages Documentation:** `/docs/patterns/app-has-images.md`

---

**Remember:** After using templates, always validate your code with `checklist.md`!

---

**Last Updated:** 2026-03-21
**Version:** 4.2 (Full PHPDoc - NO {@inheritdoc})