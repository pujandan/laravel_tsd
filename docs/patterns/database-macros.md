# Database Macros

Custom Blueprint macros untuk audit fields di migrations.

## auditFields() Macro

Macro untuk menambahkan kolom audit ke tabel baru.

### Usage (New Tables)

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('name');

    $table->auditFields();  // Add audit fields

    $table->timestamps(6);
    $table->softDeletes();  // Menambahkan deleted_by
});
```

### Yang Ditambahkan

**Tanpa softDeletes():**
- `created_by` (UUID, nullable, indexed, FK ke users)
- `updated_by` (UUID, nullable, indexed, FK ke users)

**Dengan softDeletes():**
- `created_by` (UUID, nullable, indexed, FK ke users)
- `updated_by` (UUID, nullable, indexed, FK ke users)
- `deleted_by` (UUID, nullable, indexed, FK ke users)

### Foreign Keys

Hanya ditambahkan jika tabel `users` exists. ON DELETE SET NULL.

## auditFieldsSafe() Macro

Macro untuk menambahkan kolom audit ke tabel **existing** tanpa error.

### Usage (Existing Tables)

```php
// Migration untuk menambah audit ke existing table
Schema::table('products', function (Blueprint $table) {
    $table->auditFieldsSafe();  // Safe untuk existing columns
});
```

### Safe Behavior

- Cek existing columns dulu
- Hanya add columns yang belum ada
- Hanya add foreign keys untuk columns yang baru ditambahkan

## Trait Pendukung

Gunakan trait `AppAuditable` di model untuk auto-populate audit fields:

```php
use Daniardev\LaravelTsd\Traits\AppAuditable;

class User extends Model
{
    use AppAuditable;
    // created_by, updated_by, deleted_by auto-filled
}
```