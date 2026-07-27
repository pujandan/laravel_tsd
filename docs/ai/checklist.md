# Code Validation Checklist

Use this checklist to validate your code before committing. All items must be checked before considering the code complete.

---

## Table of Contents

1. [Service Layer Checklist](#1-service-layer-checklist)
2. [Controller Checklist](#2-controller-checklist)
3. [Model Checklist](#3-model-checklist)
4. [Request Validation Checklist](#4-request-validation-checklist)
5. [Resource Checklist](#5-resource-checklist)
6. [Migration Checklist](#6-migration-checklist)
7. [General Checklist](#7-general-checklist)
8. [Common Mistakes Checklist](#8-common-mistakes-checklist)
9. [Helper Quick Reference](#9-helper-quick-reference)
10. [Trait Quick Reference](#10-trait-quick-reference)

---

## 1. Service Layer Checklist

Use this checklist when creating or modifying service classes.

### Interface & Implementation

- [ ] **Service has Interface** (MANDATORY)
  - Interface file exists: `{Entity}Interface.php`
  - Interface is in same namespace as service
  - Interface name follows pattern: `{Entity}Interface`

- [ ] **Service implements Interface** (MANDATORY)
  - Service class declaration: `class {Entity}Service implements {Entity}Interface`
  - All interface methods are implemented
  - Method signatures match interface exactly

- [ ] **AppTransactional trait is added**
  - `use AppTransactional;` statement present
  - Trait is imported: `use Daniardev\LaravelTsd\Traits\AppTransactional;`

- [ ] **Service is registered in AppServiceProvider** (CRITICAL!)
  - Binding added in `app/Providers/AppServiceProvider.php`
  - `use` statement for Interface exists
  - `use` statement for Service exists
  - `$this->app->bind(Interface::class, Service::class);` present
  - Registered in SAME commit as service creation

### Method Implementation

- [ ] **WRITE methods call requireTransaction()**
  - `create()` method has `$this->requireTransaction();` at START
  - `update()` method has `$this->requireTransaction();` at START
  - `delete()` method has `$this->requireTransaction();` at START
  - All custom write methods call `$this->requireTransaction();`

- [ ] **READ methods do NOT call requireTransaction()**
  - `paginate()` method has NO `requireTransaction()`
  - `find()` method has NO `requireTransaction()`
  - All custom read methods have NO `requireTransaction()`

- [ ] **NO DB::transaction() in service**
  - No `DB::transaction()` anywhere in service
  - Transactions are managed by controller only

- [ ] **NO try-catch blocks**
  - No try-catch blocks in any service method
  - Let Handler.php manage exceptions

### Business Logic

- [ ] **Business logic is in service (NOT controller)**
  - All validation rules are in service
  - All business rules are in service
  - All data processing is in service

- [ ] **AppException for business logic violations**
  - Use `throw new AppException($message, 422)` for validation failures
  - Use descriptive error messages
  - Use appropriate HTTP status codes

- [ ] **findOrFail() for required models**
  - Use `Model::findOrFail($id)` instead of `find()` + null check
  - Use `firstOrFail()` for queries with where clauses

### Type Hints & Return Types

- [ ] **All methods have type hints**
  - All parameters have type hints
  - All return types are declared
  - Use proper types: `array`, `string`, `Model`, `LengthAwarePaginator`, etc.

### PHPDoc Comments

- [ ] **Service methods have complete PHPDoc**
  - **NEVER use `{@inheritdoc}`** in service implementation
  - Each method has full PHPDoc with `@param` and `@return`
  - All parameters are documented with descriptions
  - Return type is documented
  - `@throws` included for methods that throw exceptions
  - Example:
    ```php
    /**
     * Create new record.
     *
     * @param string $name Entity name
     * @param string $email Entity email
     * @param string|null $status Entity status (optional)
     * @return {Entity} Created model
     * @throws AppException If email already exists
     */
    public function create(string $name, string $email, ?string $status = null): {Entity}
    ```

### Method Quality

- [ ] **Methods follow length guidelines**
  - Service methods ≤ 30 lines
  - Extract to private methods if longer

---

## 2. Controller Checklist

Use this checklist when creating or modifying controller classes.

### Dependency Injection

- [ ] **Service injected via constructor**
  - Constructor exists with service parameter
  - Service is typed with Interface: `private {Entity}Interface $service`
  - Service is assigned to private property

- [ ] **AppPagination trait is used**
  - `use AppPagination;` statement present
  - Trait is imported: `use Daniardev\LaravelTsd\Traits\AppPagination;`

### Transaction Usage

- [ ] **ALL write operations wrapped with DB::transaction()**
  - `store()` method uses `DB::transaction(function () { ... })`
  - `update()` method uses `DB::transaction(function () { ... })`
  - `destroy()` method uses `DB::transaction(function () { ... })`
  - All custom write actions use `DB::transaction(function () { ... })`

- [ ] **NO transaction for read operations**
  - `index()` method has NO `DB::transaction()`
  - `show()` method has NO `DB::transaction()`

- [ ] **@throws Throwable in PHPDoc**
  - All methods with `DB::transaction()` have `@throws Throwable` in PHPDoc
  - PHPDoc block is present and properly formatted

### Response Format

- [ ] **Responses use AppResponse::success()**
  - All returns use `AppResponse::success(JsonResource, message)`
  - NO raw `response()->json()` calls

- [ ] **Response parameter order is correct**
  - Parameter 1: `JsonResource` (or `JsonResource::make($array)`)
  - Parameter 2: `__('message.key')`
  - NOT reversed!

- [ ] **Array responses wrapped with JsonResource::make()**
  - If service returns array, wrap with `JsonResource::make($array)`

### Locale & Messages

- [ ] **Locale uses __() helper**
  - All messages use `__('message.key')`
  - NO `Lang::get('message.key')`
  - Messages are defined in lang files

- [ ] **No try-catch blocks**
  - NO try-catch blocks in controller
  - Let Handler.php manage exceptions

### Code Quality

- [ ] **Methods follow length guidelines**
  - Controller methods ≤ 20 lines
  - Extract to service if longer

- [ ] **NO business logic in controller**
  - All business logic is in service
  - Controller only handles HTTP concerns

- [ ] **NO direct Model calls**
  - All data access goes through service
  - NO `Model::find()`, `Model::create()`, etc. in controller

---

## 3. Model Checklist

Use this checklist when creating or modifying model classes.

### Traits

- [ ] **HasUuids trait is present**
  - `use HasUuids;` statement present
  - Trait is imported: `use Illuminate\Database\Eloquent\Concerns\HasUuids;`

- [ ] **AppAuditable trait is present**
  - `use AppAuditable;` statement present
  - Trait is imported: `use Daniardev\LaravelTsd\Traits\AppAuditable;`

- [ ] **AppHasImages trait (if model has images)**
  - `use AppHasImages;` statement present
  - Trait is imported: `use Daniardev\LaravelTsd\Traits\AppHasImages;`
  - `getImageFields()` returns array of image fields if multiple images
  - `getImageDiskConfig()` defined if custom storage needed
  - **CRITICAL:** Migration includes ONLY `{field}_path` and `{field}_disk` (NO `{field}_url`!)
  - URL is generated in Resource layer using `temporaryUrl()` for private files

- [ ] **SoftDeletes trait (if needed)**
  - `use SoftDeletes;` present if `deleted_by` is needed
  - Trait is imported: `use Illuminate\Database\Eloquent\SoftDeletes;`

### Properties

- [ ] **guarded is used (NOT fillable)**
  - `protected $guarded = ['id', 'created_at', 'updated_at'];`
  - NO `protected $fillable = [...]`

- [ ] **$casts array is defined**
  - Boolean fields cast to `boolean`
  - Date fields cast to `datetime`
  - Enum fields cast to enum class if applicable

### Relations

- [ ] **Relation methods are camelCase**
  - `public function relatedModel()` not `related_model()`
  - `public function codeAccount()` not `code_account()`
  - All relation methods follow camelCase

- [ ] **Relations have proper type hints**
  - `: BelongsTo`, `: HasMany`, `: BelongsToMany`, etc.
  - Relation types are imported

### Model Quality

- [ ] **NO business logic in model**
  - Business logic is in service
  - Model only handles data access and relations

- [ ] **Table name is correct**
  - Table name follows `snake_case` + plural convention
  - Custom table name if different from convention

---

## 4. Request Validation Checklist

Use this checklist when creating or modifying request classes.

### Request Classes

- [ ] **Two request classes exist per entity**
  - `{Entity}Request` for index/listing
  - `{Entity}FormRequest` for store/update

### Index Request

- [ ] **AppRequest::pagination() is used**
  - Rules use `AppRequest::pagination([...])`
  - Pagination rules are included

- [ ] **AppRequestTrait is used**
  - `use AppRequestTrait;` present

- [ ] **Custom attributes defined**
  - `attributes()` method defined
  - All filter fields have custom attributes
  - Uses `__()` for labels

### Form Request

- [ ] **AppRequestTrait is used**
  - `use AppRequestTrait;` present

- [ ] **Custom attributes defined**
  - `attributes()` method defined
  - All fields have custom attributes
  - Uses `__()` for labels

- [ ] **Validation rules are proper**
  - Required fields have `required` rule
  - Unique fields have `unique:table,column` rule
  - Enum fields use `in:` rule with allowed values
  - Nested fields use array validation

---

## 5. Resource Checklist

Use this checklist when creating or modifying resource classes.

### Resource Classes

- [ ] **Two resource classes exist per entity**
  - `{Entity}Resource` for single entity
  - `{Entity}Collection` for list of entities

### Resource Implementation

- [ ] **Response keys match database column names (snake_case)**
  - Resource returns data with snake_case keys
  - No case transformation in toArray()

- [ ] **Audit information is included**
  - `audit.created.at`, `audit.created.by`, `audit.created.byId`
  - `audit.updated.at`, `audit.updated.by`, `audit.updated.byId`
  - `audit.deleted` fields if soft delete is used

- [ ] **Relations use dedicated Resources**
  - Relations use `RelatedResource::make($this->relation)`
  - NO inlined relation data
  - Use `$this->when($this->relationLoaded('relation'), ...)` for conditional loading

- [ ] **Image/ file URLs are generated dynamically (if AppHasImages used)**
  - URLs generated using `$this->when($this->image_path && $this->image_disk, fn() => $this->getImageUrl())`
  - Private files use `Storage::disk()->temporaryUrl()` with token (30 min expiry)
  - Public files use `Storage::disk()->url()` for direct access
  - URL generation method checks `method_exists(Storage::disk($disk), 'temporaryUrl')`
  - NO URLs stored in database - only `{field}_path` and `{field}_disk`

### Collection Implementation

- [ ] **Collection is wrapped with dedicated Resource**
  - `'data' => EntityResource::collection($this->collection)`
  - CRITICAL: Each item must go through Resource transform
  - Without this: No audit info, no custom formatting

- [ ] **AppResource::pagination() is used**
  - `'pagination' => AppResource::pagination($this)`
  - Consistent pagination format across all collections
  - Returns: page, size, from, to, count, total, page_last, page_more

- [ ] **Type hint toArray() method**
  - `public function toArray(Request $request): array`
  - NOT `public function toArray($request): array`

---

## 6. Migration Checklist

Use this checklist when creating or modifying migrations.

### Primary Key

- [ ] **UUID is used for primary key**
  - `$table->uuid('id')->primary()`
  - NO `$table->id()` (auto-increment)

### Audit Fields

- [ ] **auditFields() macro is used (for new tables)**
  - `$table->auditFields()` present
  - Creates `created_by`, `updated_by`, `deleted_by` (if softDeletes)

- [ ] **auditFieldsSafe() macro is used (for existing tables)**
  - `$table->auditFieldsSafe()` present
  - Used when adding audit to existing table

### Foreign Keys

- [ ] **Foreign key constraints are added**
  - `->constrained()` is used
  - `->onUpdate('cascade')` is set
  - `->onDelete('cascade')` or `'set null'` is set

- [ ] **Indexes are added for frequently queried columns**
  - `$table->index(['column_name'])` for searched columns
  - Composite indexes for multi-column queries

### Table & Column Names

- [ ] **Table name follows convention**
  - `snake_case` + plural (e.g., `whatsapp_devices`, `category_accounts`)

- [ ] **Column names follow convention**
  - `snake_case` (e.g., `device_id`, `created_at`, `is_active`)

- [ ] **Foreign key columns follow convention**
  - `{relation}_id` (e.g., `user_id`, `category_account_id`)

---

## 7. General Checklist

Use this checklist for overall code quality.

### Naming Conventions

- [ ] **Database tables use snake_case + plural**
  - Examples: `whatsapp_devices`, `category_accounts`, `users`

- [ ] **Models use PascalCase + singular**
  - Examples: `WhatsappDevice`, `CategoryAccount`, `User`

- [ ] **Controllers use PascalCase + "Controller"**
  - Examples: `WhatsappDeviceController`, `CategoryAccountController`

- [ ] **Services use PascalCase + "Service"**
  - Examples: `WhatsappDeviceService`, `CategoryAccountService`

- [ ] **Interfaces use PascalCase + "Interface"**
  - Examples: `WhatsappDeviceInterface`, `CategoryAccountInterface`

- [ ] **Routes use kebab-case + plural**
  - Examples: `/whatsapp-devices`, `/category-accounts`

- [ ] **Model relation methods use camelCase**
  - Examples: `codeAccount()`, `relatedModel()`, `user()`

### File Structure

- [ ] **Files follow namespace structure**
  - Controllers: `app/Http/Controllers/Api/{Module}/{Entity}/`
  - Services: `app/Services/{Module}/{Entity}/`
  - Requests: `app/Http/Requests/Api/{Module}/{Entity}/`
  - Resources: `app/Http/Resources/Api/{Module}/{Entity}/`

- [ ] **File names match class names**
  - `WhatsappDeviceController.php`
  - `WhatsappDeviceService.php`
  - `WhatsappDeviceInterface.php`

---

## 8. Common Mistakes Checklist

Use this checklist to catch common mistakes.

### Response Format

- [ ] **Response parameter order is correct**
  - ✅ `AppResponse::success(JsonResource::make($data), __('message.success'))`
  - ❌ `AppResponse::success(__('message.success'), $data)`

### Locale Messages

- [ ] **Using __() not Lang::get()**
  - ✅ `__('message.successLoaded')`
  - ❌ `Lang::get('message.successLoaded')`

### Try-Catch Blocks

- [ ] **NO try-catch in controller**
  - ❌ NO try-catch blocks in controller methods
  - ✅ Let Handler.php manage exceptions

- [ ] **NO try-catch in service**
  - ❌ NO try-catch blocks in service methods
  - ✅ Let Handler.php manage exceptions

### Relation Methods

- [ ] **Relation methods are camelCase**
  - ✅ `public function codeAccount()`
  - ❌ `public function code_account()`

### PHPDoc

- [ ] **@throws Throwable for methods with DB::transaction()**
  - ✅ PHPDoc includes `@throws Throwable`
  - ❌ Missing PHPDoc or missing @throws

- [ ] **NEVER use {@inheritdoc} in service implementation**
  - ✅ Full PHPDoc with `@param`, `@return`, `@throws` for each method
  - ❌ Using `{@inheritdoc}` - this prevents AI from generating proper documentation
  - Example:
    ```php
    // ❌ WRONG - {@inheritdoc}
    /**
     * {@inheritdoc}
     */
    public function create(string $name, string $email): Model

    // ✅ CORRECT - Full PHPDoc
    /**
     * Create new record.
     *
     * @param string $name Entity name
     * @param string $email Entity email
     * @return Model Created model
     * @throws AppException If email already exists
     */
    public function create(string $name, string $email): Model
    ```

### Service Interface

- [ ] **Service has Interface**
  - ✅ `{Entity}Interface` exists
  - ✅ Service implements interface
  - ❌ Service without interface

### Transaction in Service

- [ ] **NO DB::transaction() in service**
  - ✅ No `DB::transaction()` in service
  - ✅ Only `$this->requireTransaction()` in write methods
  - ❌ `DB::transaction()` present in service

### Collection Wrapping

- [ ] **Collection MUST wrap with Resource**
  - ✅ `'data' => EntityResource::collection($this->collection)`
  - ❌ `'data' => $this->collection` (raw collection)
  - Without wrapping: No audit info, no camelCase, no custom formatting

- [ ] **MUST use AppResource::pagination()**
  - ✅ `'pagination' => AppResource::pagination($this)`
  - ❌ Manual pagination array construction

---

## Quick Validation Summary

### Before Committing Code

- [ ] All Service Layer checks passed
- [ ] All Controller checks passed
- [ ] All Model checks passed
- [ ] All Request checks passed
- [ ] All Resource checks passed
- [ ] All Migration checks passed
- [ ] All General checks passed
- [ ] All Common Mistakes checks passed

### Critical Items (Must Pass)

These are critical failures that MUST be fixed:

1. ❌ Service without Interface
2. ❌ Service NOT registered in AppServiceProvider
3. ❌ Missing requireTransaction() in write methods
4. ❌ DB::transaction() in service
5. ❌ Missing @throws Throwable
6. ❌ Response parameter order reversed
7. ❌ Using Lang::get() instead of __()
8. ❌ Try-catch blocks in controller or service
9. ❌ Snake_case relation methods
10. ❌ Business logic in controller
11. ❌ Direct Model calls in controller

---

**Remember:** All checklist items must be checked before committing code!

---

## 9. Helper Quick Reference

Quick reference for available helper methods. See `quick-reference.md` Section 16 for detailed documentation.

### AppQuery
- `paginate($query, $pagination, ?array $allowedColumns)` - Paginate with optional column whitelist
- `pagination($query, $request, ?array $allowedColumns)` - Paginate from request
- `sort($query, $request, ?array $allowedColumns)` - Apply sorting

### AppResponse
- `success($data, $message)` - Success response
- `error($message, $code, $error)` - Error response
- `print($message, $data)` - Simple print response

### AppHelper (Utilities)
- `enumCasesToString()` - Enum to string for validation
- `formatCurrency()` / `formatDate()` - Formatting
- `toBoolean()` / `ifNull()` - Type conversion
- `arrayMerge()` - Array merging

### AppSafe
- `run()` - Silent execution
- `runWithRetry()` - Retry logic
- `runBatch()` - Batch operations

### AppValidation
- `unique()` - Case-insensitive unique validation
- `fail()` - Throw validation exception

### AppLog
- `getUserContext()` - Get user context
- `maskEmail()` / `maskPhoneNumber()` - Mask sensitive data

### AppPermission
- `isAllow($feature)` - Check permission

### AppSecure
- `encrypt($value)` - Encrypt using AES-256-CBC with random IV
- `decrypt($encoded)` - Decrypt base64 encoded (IV + ciphertext)
- `getKeyForFlutter()` - Get encryption key for Flutter config

---

## 10. Trait Quick Reference

Quick reference for available traits. See `quick-reference.md` Section 17 for detailed documentation.

### AppTransactional
- `requireTransaction()` - Must be in DB::transaction (use in write methods)

### AppAuditable
- Adds: `created_by`, `updated_by`, `deleted_by` fields
- Relations: `creator()`, `updater()`, `deleter()`
- Scopes: `createdBy()`, `updatedBy()`, `deletedBy()`, `notDeleted()`

### AppPagination
- `pagination($request)` - Extract pagination data from request

### AppRequestTrait
- Custom validation error formatting

### AppMigrationOrderScanner
- Migration execution order control

---

**Last Updated:** 2026-03-21
