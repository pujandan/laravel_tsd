# Database Transaction Pattern

Detailed guide for database transaction usage and the AppTransactional trait.

---

## Table of Contents

1. [Overview](#1-overview)
2. [When to Use Transactions](#2-when-to-use-transactions)
3. [AppTransactional Trait](#3-apptransactional-trait)
4. [Controller Pattern](#4-controller-pattern)
5. [Service Pattern](#5-service-pattern)
6. [Common Mistakes](#6-common-mistakes)
7. [Error Scenarios](#7-error-scenarios)
8. [Performance Considerations](#8-performance-considerations)

---

## 1. Overview

### Why Transactions Matter

**Database transactions are REQUIRED for all write operations** to maintain data consistency and atomicity.

```php
// ❌ WITHOUT transaction - Data inconsistency risk
public function transferMoney(string $fromId, string $toId, float $amount): void
{
    $from = Account::find($fromId);
    $to = Account::find($toId);

    $from->balance -= $amount;
    $from->save(); // ⚠️ If this fails, money is lost!

    $to->balance += $amount;
    $to->save();
}

// ✅ WITH transaction - Atomic operation
public function transferMoney(string $fromId, string $toId, float $amount): void
{
    DB::transaction(function () use ($fromId, $toId, $amount) {
        $from = Account::findOrFail($fromId);
        $to = Account::findOrFail($toId);

        $from->balance -= $amount;
        $from->save();

        $to->balance += $amount;
        $to->save();

        // Both succeed OR both fail - guaranteed
    });
}
```

### Transaction Rules Summary

| Operation | Transaction Required | Location |
|-----------|---------------------|----------|
| **CREATE** | ✅ YES | Controller: `DB::transaction()` |
| **UPDATE** | ✅ YES | Controller: `DB::transaction()` |
| **DELETE** | ✅ YES | Controller: `DB::transaction()` |
| **READ** | ❌ NO | No transaction |
| **Business operations** | ✅ YES | Controller: `DB::transaction()` |

---

## 2. When to Use Transactions

### Write Operations (REQUIRED)

```php
// ✅ CORRECT - All write operations use transaction
public function store(FormRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $item = $this->service->create($request->validated());
        return AppResponse::success(Resource::make($item), __('message.saved'));
    });
}

public function update(FormRequest $request, string $id): JsonResponse
{
    return DB::transaction(function () use ($request, $id) {
        $item = $this->service->update($id, $request->validated());
        return AppResponse::success(Resource::make($item), __('message.updated'));
    });
}

public function destroy(string $id): JsonResponse
{
    return DB::transaction(function () use ($id) {
        $item = $this->service->delete($id);
        return AppResponse::success(Resource::make($item), __('message.deleted'));
    });
}
```

### Read Operations (NOT REQUIRED)

```php
// ✅ CORRECT - Read operations don't use transaction
public function index(Request $request): JsonResponse
{
    $filters = $request->input('filter', []);

    $items = $this->service->paginate(
        pagination: $this->pagination($request),
        search: $filters['search'] ?? null,
        status: $filters['status'] ?? null
    );

    return AppResponse::success(
        new EntityCollection($items),
        __('message.successLoaded')
    );
}

public function show(string $id): JsonResponse
{
    $item = $this->service->find($id);

    return AppResponse::success(
        EntityResource::make($item),
        __('message.successLoaded')
    );
}
```

### Business Operations (REQUIRED)

```php
// ✅ CORRECT - Multi-step operations use transaction
public function transferMoney(string $fromId, string $toId, float $amount): JsonResponse
{
    return DB::transaction(function () use ($fromId, $toId, $amount) {
        // Step 1: Deduct from sender
        $from = $this->accountService->debit($fromId, $amount);

        // Step 2: Add to receiver
        $to = $this->accountService->credit($toId, $amount);

        // Step 3: Create transaction record
        $transaction = $this->transactionService->create([
            'from_id' => $fromId,
            'to_id' => $toId,
            'amount' => $amount,
        ]);

        return AppResponse::success(
            TransactionResource::make($transaction),
            __('message.transferCompleted')
        );
    });
}
```

---

## 3. AppTransactional Trait

### Purpose

**Enforce transaction usage at runtime** to prevent data inconsistency bugs.

**Location:** `app/Traits/AppTransactional.php`

```php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use LogicException;

trait AppTransactional
{
    /**
     * Ensure method is called within DB::transaction
     *
     * @throws LogicException If not in transaction
     * @return void
     */
    protected function requireTransaction(): void
    {
        $transactionLevel = DB::connection()->transactionLevel();

        if ($transactionLevel === 0) {
            throw new LogicException(
                __('message.mustUseTransaction'),
                500
            );
        }
    }
}
```

### How It Works

1. **Checks transaction level** - Uses `DB::transactionLevel()` (reads property, NO DB query)
2. **Throws if level is 0** - Means not in `DB::transaction()` block
3. **Zero performance overhead** - Only reads a variable, doesn't query database

### Why Runtime Check

- ✅ **Fails fast** - Catches missing transactions during development/testing
- ✅ **Self-documenting** - Clear which methods need transactions
- ✅ **No performance cost** - `transactionLevel()` is a property read, not a query
- ✅ **Better than middleware** - Avoids deadlocks, nested transactions, performance issues

---

## 4. Controller Pattern

### Rule: Controllers MUST Wrap Write Operations

```php
<?php

namespace App\Http\Controllers\Api\Common;

use App\Services\Common\Whatsapp\WhatsappDeviceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class WhatsappDeviceController extends Controller
{
    public function __construct(
        private WhatsappDeviceInterface $service
    ) {}

    // ❌ READ operation - NO transaction
    public function index(Request $request): JsonResponse
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

    // ❌ READ operation - NO transaction
    public function show(string $id): JsonResponse
    {
        $item = $this->service->find($id);

        return AppResponse::success(
            WhatsappDeviceResource::make($item),
            __('message.successLoaded')
        );
    }

    // ✅ WRITE operation - WITH transaction
    /**
     * Store new record.
     *
     * @param FormRequest $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(FormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $item = $this->service->create($request->validated());

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successSaved')
            );
        });
    }

    // ✅ WRITE operation - WITH transaction
    /**
     * Update existing record.
     *
     * @param FormRequest $request
     * @param string $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(FormRequest $request, string $id): JsonResponse
    {
        return DB::transaction(function () use ($request, $id) {
            $item = $this->service->update($id, $request->validated());

            return AppResponse::success(
                WhatsappDeviceResource::make($item),
                __('message.successUpdated')
            );
        });
    }

    // ✅ WRITE operation - WITH transaction
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

    // ✅ WRITE operation (business logic) - WITH transaction
    /**
     * Custom action.
     *
     * @param string $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function connect(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $result = $this->service->connectDevice($id);

            return AppResponse::success(
                JsonResource::make($result),
                __('message.qrCodeGenerated')
            );
        });
    }
}
```

### Controller Rules

| Rule | Description |
|------|-------------|
| 1 | **ALL write operations wrapped with `DB::transaction()`** | store(), update(), destroy(), custom actions |
| 2 | **NO transaction for read operations** | index(), show() |
| 3 | **Add `@throws Throwable` in PHPDoc** | For methods with `DB::transaction()` |
| 4 | **NO try-catch blocks** | Let Handler.php manage exceptions |
| 5 | **Return AppResponse::success() inside transaction** | Response within transaction block |

---

## 5. Service Pattern

### Rule: Services MUST Enforce Transaction Usage

```php
<?php

namespace App\Services\Common\Whatsapp;

use App\Models\WhatsappDevice;
use Daniardev\LaravelTsd\Traits\AppTransactional;
use Daniardev\LaravelTsd\Exceptions\AppException;

class WhatsappDeviceService implements WhatsappDeviceInterface
{
    use AppTransactional; // ✅ Add trait

    // ❌ READ operation - NO requireTransaction()
    public function paginate(
        PaginationData $pagination,
        ?string $search = null,
        ?string $status = null
    ): LengthAwarePaginator {
        $query = WhatsappDevice::query();

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

    // ❌ READ operation - NO requireTransaction()
    public function find(string $id): WhatsappDevice
    {
        return WhatsappDevice::findOrFail($id);
    }

    // ✅ WRITE operation - WITH requireTransaction()
    public function create(array $data): WhatsappDevice
    {
        $this->requireTransaction(); // ⬅️ Enforce transaction

        // Validate uniqueness
        $existing = WhatsappDevice::where('device_id', $data['device_id'])->first();
        if ($existing) {
            throw new AppException(__('message.deviceIdAlreadyExists'), 422);
        }

        // Set defaults
        $data['status'] = DeviceStatus::PENDING;
        $data['qr_code'] = null;
        $data['pairing_code'] = null;

        $device = WhatsappDevice::create($data);

        return $device->fresh();
    }

    // ✅ WRITE operation - WITH requireTransaction()
    public function update(string $id, array $data): WhatsappDevice
    {
        $this->requireTransaction(); // ⬅️ Enforce transaction

        $device = $this->find($id);

        // Prevent immutable field updates
        unset($data['device_id']);

        // Clear temp data if status changes to PENDING
        if (isset($data['status']) && $data['status'] === DeviceStatus::PENDING) {
            $data['qr_code'] = null;
            $data['pairing_code'] = null;
        }

        $device->update($data);

        return $device->fresh();
    }

    // ✅ WRITE operation - WITH requireTransaction()
    public function delete(string $id): WhatsappDevice
    {
        $this->requireTransaction(); // ⬅️ Enforce transaction

        $device = $this->find($id);

        // Business rule: Can't delete connected device
        if ($device->status === DeviceStatus::CONNECTED) {
            throw new AppException(__('message.cannotDeleteConnectedDevice'), 422);
        }

        $device->delete();

        return $device;
    }

    // ✅ WRITE operation (business logic) - WITH requireTransaction()
    public function connectDevice(string $id): array
    {
        $this->requireTransaction(); // ⬅️ Enforce transaction

        $device = $this->find($id);

        // Business rule: Already connected?
        if ($device->status === DeviceStatus::CONNECTED) {
            throw new AppException(__('message.deviceAlreadyConnected'), 422);
        }

        // Call external API (still within transaction)
        $response = $this->whatsappService->loginQR($device->device_id);

        $qrCode = $response['qr'] ?? null;
        $pairingCode = $response['code'] ?? null;

        // Update device with connection data
        $device->update([
            'qr_code' => $qrCode,
            'pairing_code' => $pairingCode,
            'status' => DeviceStatus::PENDING,
        ]);

        return [
            'device_id' => $device->device_id,
            'qr_code' => $qrCode,
            'pairing_code' => $pairingCode,
            'message' => __('message.qrCodeGenerated'),
        ];
    }
}
```

### Service Rules

| Method Type | requireTransaction() | DB::transaction() |
|-------------|----------------------|-------------------|
| **READ** (paginate, find, query) | ❌ NO | ❌ NO |
| **WRITE** (create, update, delete) | ✅ YES (at START) | ❌ NO |
| **WRITE** (custom business methods) | ✅ YES (at START) | ❌ NO |

### Key Points

1. **READ methods** - No `requireTransaction()`, no `DB::transaction()`
2. **WRITE methods** - Call `$this->requireTransaction()` at START
3. **NO DB::transaction() in service** - Controller manages transactions
4. **Use findOrFail()** - Not find() + null check
5. **Throw AppException** - For business logic violations

---

## 6. Common Mistakes

### ❌ Mistake 1: Missing Transaction in Controller

```php
// ❌ WRONG - No transaction
public function store(FormRequest $request): JsonResponse
{
    // Service will throw LogicException:
    // "This method must be called within DB::transaction"
    $item = $this->service->create($request->validated());

    return AppResponse::success(
        EntityResource::make($item),
        __('message.successSaved')
    );
}

// ✅ CORRECT - Wrap with transaction
public function store(FormRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $item = $this->service->create($request->validated());

        return AppResponse::success(
            EntityResource::make($item),
            __('message.successSaved')
        );
    });
}
```

### ❌ Mistake 2: Transaction in Service

```php
// ❌ WRONG - DB::transaction() in service
public function create(array $data): Model
{
    return DB::transaction(function () use ($data) {
        return Model::create($data);
    });
}

// ✅ CORRECT - Only requireTransaction()
public function create(array $data): Model
{
    $this->requireTransaction();
    return Model::create($data);
}
```

### ❌ Mistake 3: Missing requireTransaction()

```php
// ❌ WRONG - Forgot requireTransaction()
public function create(array $data): Model
{
    // If controller forgets DB::transaction(),
    // data inconsistency is possible!
    return Model::create($data);
}

// ✅ CORRECT - Enforce transaction usage
public function create(array $data): Model
{
    $this->requireTransaction(); // Enforces transaction
    return Model::create($data);
}
```

### ❌ Mistake 4: Transaction for Read Operations

```php
// ❌ WRONG - Unnecessary transaction
public function index(Request $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $items = $this->service->paginate(...);
        return AppResponse::success(...);
    });
}

// ✅ CORRECT - No transaction for reads
public function index(Request $request): JsonResponse
{
    $items = $this->service->paginate(...);
    return AppResponse::success(...);
}
```

### ❌ Mistake 5: Try-Catch in Transaction

```php
// ❌ WRONG - Try-catch in controller
public function store(FormRequest $request): JsonResponse
{
    try {
        return DB::transaction(function () use ($request) {
            // ...
        });
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}

// ✅ CORRECT - No try-catch
public function store(FormRequest $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        // Let Handler.php handle exceptions
    });
}
```

---

## 7. Error Scenarios

### Scenario 1: Developer Forgets Transaction

**Controller code:**
```php
// ❌ Developer forgot DB::transaction()
public function store(Request $request): JsonResponse
{
    $item = $this->service->create($request->all()); // ⚠️ Will throw!
    return response()->json($item);
}
```

**Service code:**
```php
public function create(array $data): Model
{
    $this->requireTransaction(); // ⬅️ Throws LogicException
    return Model::create($data);
}
```

**Result:**
```json
HTTP 500 Internal Server Error
{
  "success": false,
  "message": "This method must be called within DB::transaction to maintain data consistency."
}
```

**Fix:**
```php
// ✅ Wrap with transaction
public function store(Request $request): JsonResponse
{
    return DB::transaction(function () use ($request) {
        $item = $this->service->create($request->all());
        return response()->json($item);
    });
}
```

### Scenario 2: Partial Failure Without Transaction

**Without transaction (data loss):**
```php
// ❌ NO transaction
public function processOrder(string $orderId): void
{
    $order = Order::findOrFail($orderId);

    // Step 1: Deduct inventory
    $product = Product::findOrFail($order->product_id);
    $product->stock -= $order->quantity;
    $product->save(); // ✅ Success

    // Step 2: Charge payment
    $payment = $this->chargePayment($order); // ❌ FAILS!

    // Step 3: Update order status
    $order->status = 'paid';
    $order->save(); // Never reached
}

// Result: Stock deducted but payment failed! Data inconsistency!
```

**With transaction (atomic):**
```php
// ✅ WITH transaction
public function processOrder(string $orderId): void
{
    DB::transaction(function () use ($orderId) {
        $order = Order::findOrFail($orderId);

        // Step 1: Deduct inventory
        $product = Product::findOrFail($order->product_id);
        $product->stock -= $order->quantity;
        $product->save(); // Success

        // Step 2: Charge payment
        $payment = $this->chargePayment($order); // Fails!

        // Step 3: Update order status
        $order->status = 'paid';
        $order->save();
    });
    // Result: Everything rolled back, stock NOT deducted. Consistent!
}
```

---

## 8. Performance Considerations

### Myth vs Fact

**Myth:** "Transactions slow down queries."

**Fact:** Transactions have minimal overhead and prevent expensive data corruption.

### Performance Metrics

| Operation | Without Transaction | With Transaction | Difference |
|-----------|-------------------|------------------|------------|
| **Single INSERT** | 2.5ms | 2.6ms | +0.1ms (4%) |
| **10 INSERTs** | 25ms | 26ms | +1ms (4%) |
| **Data corruption cleanup** | Hours | N/A | Prevention > Cure |

### Transaction Overhead

- ✅ **Minimal**: ~4% for typical operations
- ✅ **Worth it**: Prevents data inconsistency
- ✅ **Required**: For any write operation
- ✅ **Standard**: Industry best practice

### When NOT to Use Transactions

- ❌ Read-only operations (SELECT queries)
- ❌ Reports/analytics (no data modification)
- ❌ Bulk imports with explicit error handling (use DB::beginTransaction() manually)

---

## Quick Reference

| Task | Pattern |
|------|---------|
| **Write operation (controller)** | `DB::transaction(function () { ... })` |
| **Write operation (service)** | `$this->requireTransaction()` at START |
| **Read operation** | NO transaction |
| **Prevent data inconsistency** | Use transactions for all writes |
| **Enforce transaction usage** | Add AppTransactional trait to service |

---

## Checklist

**For Service Layer:**
- [ ] AppTransactional trait added to service class
- [ ] requireTransaction() called at start of write methods
- [ ] NO requireTransaction() in read methods
- [ ] NO DB::transaction() in service
- [ ] NO try-catch blocks in service

**For Controller Layer:**
- [ ] DB::transaction() wrapper for all write operations
- [ ] NO DB::transaction() for read operations
- [ ] @throws Throwable in PHPDoc for methods with DB::transaction()
- [ ] NO try-catch blocks in controller

---

**Related Patterns:**
- [Service Layer Pattern](./service-layer.md)
- [Error Handling Pattern](./error-handling.md)

---

**Last Updated:** 2026-01-29
