# API Implementation Guide

> Panduan lengkap implementasi API CRUD dengan laravel-tsd package

## Daftar Isi

1. [Quick Start](#quick-start)
2. [Struktur File](#struktur-file)
3. [Template Dasar](#template-dasar)
4. [Pattern & Konvensi](#pattern--konvensi)
5. [Contoh Implementasi Lengkap](#contoh-implementasi-lengkap)
6. [Template Khusus](#template-khusus)
7. [Helper Classes](#helper-classes)
8. [Checklist](#checklist)

---

## Quick Start

Untuk membuat CRUD API baru dengan cepat:

```
php artisan vendor:publish --tag=laravel-tsd-docs
```

Lalu baca file ini dan ikuti pattern di bawah.

---

## Struktur File

Setiap menu API terdiri dari 7 file:

```
app/
├── Http/
│   ├── Controllers/Api/{Module}/{Menu}/
│   │   └── {Menu}Controller.php          ← CRUD methods
│   ├── Requests/Api/{Module}/{Menu}/
│   │   ├── {Menu}Request.php             ← Index dengan pagination
│   │   └── {Menu}FormRequest.php         ← Create/Update
│   └── Resources/Api/{Module}/{Menu}/
│       ├── {Menu}Resource.php             ← Single item
│       └── {Menu}Collection.php           ← Collection with pagination
├── Services/{Module}/{Menu}/
│   ├── {Menu}Interface.php               ← Contract
│   └── {Menu}Service.php                 ← Business logic
└── Models/
    └── {Menu}.php                         ← Model dengan AppAuditable
```

**Naming Convention:**

| File | Contoh | Module: Settings, Menu: Banner |
|------|--------|----------------------------------|
| Controller | `BannerController` | `App\Http\Controllers\Api\Settings\Banner` |
| Interface | `BannerInterface` | `App\Services\Settings\Banner` |
| Service | `BannerService` | `App\Services\Settings\Banner` |
| Request (index) | `BannerRequest` | `App\Http\Requests\Api\Settings\Banner` |
| Request (form) | `BannerFormRequest` | `App\Http\Requests\Api\Settings\Banner` |
| Resource | `BannerResource` | `App\Http\Resources\Api\Settings\Banner` |
| Collection | `BannerCollection` | `App\Http\Resources\Api\Settings\Banner` |

---

## Template Dasar

**Gunakan template di bawah ini, ganti placeholder sesuai kebutuhan:**

### 1. Interface Template

```php
<?php

namespace App\Services\{Module}\{Entity};

use Daniardev\LaravelTsd\Data\PaginationData;
use App\Models\{Entity};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface {Entity}Interface
{
    /**
     * Get paginated {entities}
     */
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator;

    /**
     * Find {entity} by ID
     */
    public function find(string $id): {Entity};

    /**
     * Create new {entity}
     */
    public function create(
        string $name,
        string $email,
        ?string $status = null
    ): {Entity};

    /**
     * Update existing {entity}
     */
    public function update(
        string $id,
        ?string $name = null,
        ?string $email = null,
        ?string $status = null
    ): {Entity};

    /**
     * Delete {entity}
     */
    public function delete(string $id): {Entity};
}
```

---

### 2. Service Template

```php
<?php

namespace App\Services\{Module}\{Entity};

use Daniardev\LaravelTsd\Data\PaginationData;
use Daniardev\LaravelTsd\Helpers\AppQuery;
use Daniardev\LaravelTsd\Traits\AppTransactional;
use Daniardev\LaravelTsd\Exceptions\AppException;
use App\Models\{Entity};
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class {Entity}Service implements {Entity}Interface
{
    use AppTransactional;

    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $query = {Entity}::query();

        if ($search !== null) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status !== null) {
            $query->where('status', $status);
        }

        return AppQuery::paginate($query, $pagination);
    }

    public function find(string $id): {Entity}
    {
        return {Entity}::findOrFail($id);
    }

    public function create(
        string $name,
        string $email,
        ?string $status = null
    ): {Entity} {
        $this->requireTransaction();

        // Business logic validation
        $existing = {Entity}::where('email', $email)->first();
        if ($existing) {
            throw new AppException('Email already exists', 422);
        }

        $createData = [
            'name' => $name,
            'email' => $email,
            'status' => $status,
        ];

        return {Entity}::create($createData);
    }

    public function update(
        string $id,
        ?string $name = null,
        ?string $email = null,
        ?string $status = null
    ): {Entity} {
        $this->requireTransaction();

        $entity = $this->find($id);
        $updateData = [];

        if ($name !== null) $updateData['name'] = $name;
        if ($email !== null) $updateData['email'] = $email;
        if ($status !== null) $updateData['status'] = $status;

        if (empty($updateData)) {
            throw new AppException('No data to update', 422);
        }

        $entity->update($updateData);
        return $entity;
    }

    public function delete(string $id): {Entity}
    {
        $this->requireTransaction();

        $entity = $this->find($id);
        $entity->delete();

        return $entity;
    }
}
```

---

### 3. Controller Template

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
        );

        return AppResponse::success(
            new {Entity}Collection($items),
            __('message.successLoaded')
        );
    }

    public function store({Entity}FormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $item = $this->service->create(
                name: $validated['name'],
                email: $validated['email'],
                status: $validated['status'] ?? null
            );

            return AppResponse::success(
                {Entity}Resource::make($item),
                __('message.successSaved')
            );
        });
    }

    public function show(string $id): JsonResponse
    {
        $item = $this->service->find($id);

        return AppResponse::success(
            {Entity}Resource::make($item),
            __('message.successLoaded')
        );
    }

    public function update({Entity}FormRequest $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $validated = $request->validated();

            $item = $this->service->update(
                id: $id,
                name: $validated['name'] ?? null,
                email: $validated['email'] ?? null,
                status: $validated['status'] ?? null
            );

            return AppResponse::success(
                {Entity}Resource::make($item),
                __('message.successUpdated')
            );
        });
    }

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
}
```

---

### 4. Request Template (Index)

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

    public function attributes(): array
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

---

### 5. Request Template (Form)

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

    public function attributes(): array
    {
        return [
            'name' => __('label.name'),
            'email' => __('label.email'),
            'status' => __('label.status'),
        ];
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'status' => ['nullable', 'string'],
        ];
    }
}
```

---

### 6. Resource Template

```php
<?php

namespace App\Http\Resources\Api\{Module}\{Entity};

use Daniardev\LaravelTsd\Helpers\AppHelper;
use Daniardev\LaravelTsd\Resources\Api\AuditResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {Entity}Resource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),

            // Audit information
            'audit' => AuditResource::make($this->resource)->toArray($request),
        ];

        return AppHelper::toCamelCase($data);
    }
}
```

---

### 7. Collection Template

```php
<?php

namespace App\Http\Resources\Api\{Module}\{Entity};

use Daniardev\LaravelTsd\Helpers\AppResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class {Entity}Collection extends ResourceCollection
{
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

### 8. Model Template

```php
<?php

namespace App\Models;

use Daniardev\LaravelTsd\Traits\AppAuditable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class {Entity} extends Model
{
    use HasUuids, AppAuditable, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
```

---

### 9. Migration Template

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{entities}', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('status')->default('active');
            $table->boolean('is_active')->default(true);

            $table->auditFields();

            $table->timestamps(6);
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{entities}');
    }
};
```

---

## Pattern & Konvensi

### 1. Transaction Handling

```php
// ✅ Controller - Wrap write operations
return DB::transaction(function () use ($request) {
    $item = $this->service->create(...);
    return AppResponse::success(...);
});

// ✅ Service - Enforce transaction
public function create(...): Model
{
    $this->requireTransaction(); // Throw jika tidak dalam transaction
    // Business logic...
}
```

### 2. Named Parameters

```php
// ✅ BENAR - Explicit parameters
$this->service->create(
    name: $validated['name'],
    email: $validated['email'],
    status: $validated['status'] ?? null
);

// ❌ SALAH - Array
$this->service->create([
    'name' => $validated['name'],
    'email' => $validated['email'],
]);
```

### 3. Response Format

```php
// ✅ BENAR - JsonResource first
AppResponse::success(JsonResource::make($data), __('message.success'));

// ❌ SALAH - Parameters reversed
AppResponse::success(__('message.success'), $data);
```

### 4. Dependency Injection

```php
// ✅ Constructor property promotion (PHP 8.1+)
public function __construct(
    private readonly {Entity}Interface $service
) {}
```

---

## Contoh Implementasi Lengkap

Lihat contoh nyata dengan Banner di dokumentasi terpisah: [`api-standard-example.md`](api-standard-example.md)

---

## Template Khusus

### AppHasImages (Image Upload)

Untuk entity dengan image upload, gunakan pattern di: [`../patterns/app-has-images.md`](../patterns/app-has-images.md)

**Key differences:**
- Model: `use AppHasImages` trait
- Migration: Hanya `{field}_path`, `{field}_disk` - NO `{field}_url`
- Controller: `$entity->upload{Field}($image)`
- Resource: Generate `{field}_url` dynamically

---

## Helper Classes dari laravel-tsd

| Class | Gunakan untuk |
|-------|--------------|
| `AppResponse` | Response JSON (success, error) |
| `AppQuery` | Pagination dengan security |
| `AppRequest` | Pagination validation rules |
| `AppHelper` | toCamelCase(), enumCasesToString() |
| `AppResource` | Pagination metadata di Collection |
| `AppPagination` | Extract pagination dari request |
| `AppTransactional` | requireTransaction() di Service |
| `AppRequestTrait` | Validation error format |
| `PaginationData` | Pagination DTO |
| `AppException` | Business logic error |
| `AuditResource` | Reusable audit information |

---

## Route Definition

```php
// routes/api.php
use App\Http\Controllers\Api\{Module}\{Entity}\{Entity}Controller;

Route::prefix('{module}')->group(function () {
    Route::prefix('{entities}')->group(function () {
        Route::get('/', [{Entity}Controller::class, 'index']);
        Route::post('/', [{Entity}Controller::class, 'store']);
        Route::get('/{id}', [{Entity}Controller::class, 'show']);
        Route::put('/{id}', [{Entity}Controller::class, 'update']);
        Route::delete('/{id}', [{Entity}Controller::class, 'destroy']);
    });
});
```

---

## Checklist

Sebelum commit:

- [ ] Write operations dalam `DB::transaction()`
- [ ] Service menggunakan named parameters (bukan array)
- [ ] Service menggunakan `AppTransactional` + `requireTransaction()`
- [ ] Response via `AppResponse::success(JsonResource, message)`
- [ ] Request menggunakan `AppRequestTrait`
- [ ] Controller menggunakan `AppPagination` trait
- [ ] Resource menggunakan `AuditResource` dari package
- [ ] Model menggunakan `AppAuditable` trait
- [ ] Migration menggunakan `auditFields()` macro
- [ ] Test endpoint dengan Postman/Insomnia
