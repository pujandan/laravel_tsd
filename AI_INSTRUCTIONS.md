# AI Instructions - Laravel TSD Package

> **Untuk AI Agent:** Baca file ini ketika user meminta membuat fitur sesuai pattern laravel-tsd

---

## Trigger Phrases

Ketika user mengatakan:
- "buatkan menu X sesuai pattern laravel_tsd"
- "buatkan API X seusai pattern package laravel_tsd"
- "create X following laravel_tsd pattern"
- "tambahkan fitur X dengan standard laravel_tsd"

**Ikuti langkah di bawah ini:**

---

## Learning Sequence (WAJIB)

Sebelum membuat kode, AI agent HARUS membaca dokumentasi berikut secara berurutan:

### Step 1: Baca package overview (30 detik)
```
/docs/ai/readme.md → Architecture & file structure
```

### Step 2: Baca semua coding rules (2 menit)
```
/docs/ai/quick-reference.md → 17 sections lengkap
```
**Wajib baca:**
- Section 1: Naming Conventions
- Section 2-8: Controller, Service, Model, Request, Resource, Route, Migration rules
- Section 9: Response Format (AppResponse::success pattern)
- Section 10: Transaction Pattern (DB::transaction + requireTransaction)
- Section 16: Helper Reference ( semua helper yang tersedia)
- Section 17: Trait Reference

### Step 3: Baca API implementation guide (5 menit)
```
/docs/ai/api-guide.md → Template + Pattern lengkap
```
**Fokus pada:**
- Template dasar untuk 7 files
- Named parameters pattern
- Transaction handling
- Response format
- Helper classes reference

### Step 4: Baca templates (opsional, 2 menit)
```
/docs/ai/templates.md → Examples domain-specific
```

---

## Setelah Membaca, Konfirmasi:

```
✅ Saya sudah mempelajari laravel-tsd package:
- Architecture: Service Layer Pattern (Controller → Service → Model)
- Helpers: AppResponse, AppQuery, AppRequest, AppResource, AppHelper, dll
- Traits: AppTransactional, AppPagination, AppRequestTrait, AppAuditable
- Pattern: 7 files CRUD (api-standard.md)

Siap membuat: [nama menu/fitur]
```

---

## Implementation Checklist

Saat membuat kode, gunakan:

### Controller
- [ ] Inject service via constructor (`private readonly ServiceInterface $service`)
- [ ] Wrap write operations dengan `DB::transaction()`
- [ ] Return via `AppResponse::success(JsonResource, message)`
- [ ] Use `AppPagination` trait untuk index
- [ ] Named parameters saat call service

### Service
- [ ] Buat Interface dulu
- [ ] Service implements Interface
- [ ] Use `AppTransactional` trait
- [ ] Call `$this->requireTransaction()` di awal write methods
- [ ] Semua parameters explicit (bukan array $data)
- [ ] Return Model, bukan JSON

### Request
- [ ] `{Menu}Request.php` untuk index/pagination
- [ ] `{Menu}FormRequest.php` untuk create/update
- [ ] Use `AppRequestTrait`
- [ ] Use `AppRequest::pagination()` untuk index request

### Resource
- [ ] `{Menu}Resource.php` untuk single item
- [ ] `{Menu}Collection.php` untuk list
- [ ] Use `AuditResource` dari package untuk audit info
- [ ] Use `AppHelper::toCamelCase()` untuk response

### Model
- [ ] Use `AppAuditable` trait untuk audit trail
- [ ] Use `HasUuids` trait untuk primary key
- [ ] Use `guarded` instead of `fillable`

### Migration
- [ ] Use UUID untuk primary key (`$table->uuid('id')->primary()`)
- [ ] Use `auditFields()` macro untuk audit columns
- [ ] Foreign keys dengan `->onDelete('set null')`

---

## Helper Classes dari Package

| Class | Namespace | Gunakan untuk |
|-------|-----------|---------------|
| `AppResponse` | `Daniardev\LaravelTsd\Helpers` | Response JSON |
| `AppQuery` | `Daniardev\LaravelTsd\Helpers` | Pagination query |
| `AppRequest` | `Daniardev\LaravelTsd\Helpers` | Pagination validation |
| `AppHelper` | `Daniardev\LaravelTsd\Helpers` | toCamelCase, enumCasesToString |
| `AppResource` | `Daniardev\LaravelTsd\Helpers` | Pagination metadata |
| `AppPagination` | `Daniardev\LaravelTsd\Traits` | Extract pagination |
| `AppTransactional` | `Daniardev\LaravelTsd\Traits` | Enforce transaction |
| `AppRequestTrait` | `Daniardev\LaravelTsd\Traits` | Validation error format |
| `PaginationData` | `Daniardev\LaravelTsd\Data` | Pagination DTO |
| `AppException` | `Daniardev\LaravelTsd\Exceptions` | Business logic error |
| `AuditResource` | `Daniardev\LaravelTsd\Resources\Api` | Audit information |

---

## Contoh Prompt ke User

Setelah membaca dokumentasi, tanya user:

```
✅ Pattern laravel-tsd sudah dipelajari.

Untuk membuat: [Menu] API

Saya butuh informasi:
1. Table name: (contoh: products)
2. Module: (contoh: Ecommerce)
3. Fields: (contoh: name, price, description, is_active)
4. Ada file upload? (image/pdf/none)
5. Ada relasi ke table lain? (optional)

Silakan lengkapi.
```

---

## Quick Reference

### Transaction Pattern
```php
// Controller
return DB::transaction(function () use ($request) {
    $item = $this->service->create(...);
    return AppResponse::success(Resource::make($item), __('message.successSaved'));
});

// Service
public function create(...): Model
{
    $this->requireTransaction(); // Enforce transaction
    return Model::create([...]);
}
```

### Response Format
```php
// ✅ BENAR
AppResponse::success(JsonResource::make($data), __('message.success'));

// ❌ SALAH (parameter terbalik)
AppResponse::success(__('message.success'), $data);
```

### Named Parameters
```php
// ✅ BENAR - Explicit parameters
$this->service->create(
    name: $validated['name'],
    price: $validated['price'],
    isActive: $validated['is_active'] ?? true
);

// ❌ SALAH - Array
$this->service->create([
    'name' => $validated['name'],
    'price' => $validated['price'],
]);
```

---

## File Paths

Dokumentasi lengkap ada di:
- `/docs/ai/readme.md` - Overview
- `/docs/ai/quick-reference.md` - 17 sections rules
- `/docs/ai/api-standard.md` - CRUD pattern 7 files
- `/docs/ai/templates.md` - Code templates
- `/docs/ai/checklist.md` - Pre-commit checklist
- `/docs/patterns/*.md` - Deep dive patterns
