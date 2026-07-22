# Middleware Pattern

Middleware untuk normalisasi request data di Laravel TSD.

## AppParseBoolAndNull

Middleware untuk normalisasi boolean dan null values dari frontend ke format yang Laravel harapkan.

### Problem

Frontend (JS/Flutter) mengirim boolean sebagai `true`/`false`, tapi Laravel expect `"1"`/`0"` untuk validasi boolean.

### Solution

Middleware otomatis convert:
- `true` → `"1"`
- `false` → `"0"`
- `"true"` → `"1"`
- `"false"` → `"0"`
- `"null"` → `null`
- `""` (empty string) → `null`

### Setup

```php
// app/Http/Kernel.php

protected $middleware = [
    // ...
    \Daniardev\LaravelTsd\Middleware\AppParseBoolAndNull::class,
];
```

Atau gunakan alias di route:

```php
// routes/web.php atau routes/api.php
Route::middleware('parse.bool')->group(function () {
    // Routes...
});
```

### Usage

Frontend bisa kirim:
```javascript
// JSON body
{
  "is_active": true,
  "is_verified": false,
  "status": null
}

// Query params
GET /api/users?filter[is_active]=true
```

Backend terima sebagai:
```php
// $request->input('is_active') = "1"
// $request->input('is_verified') = "0"
// $request->input('status') = null
```

Validation tetap work:
```php
'is_active' => ['boolean']  // Pass dengan "1" atau "0"
'status' => ['nullable']    // Pass dengan null
```

### Works For

- ✅ JSON request body
- ✅ Form data
- ✅ Query parameters (nested seperti `filter[is_active]=true`)