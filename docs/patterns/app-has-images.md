# AppHasImages Trait - Image Upload Management

**Overview:** The `AppHasImages` trait provides automatic image upload and storage management for Laravel models. Follows the pattern used in Youmrah project where URLs are generated dynamically in Resources, not stored in database.

---

## 🎯 Purpose

Automate image handling for models that need to store images with automatic:
- File upload (multipart or base64)
- Path and storage field management
- Cloud storage integration (S3, local, etc.)
- Automatic cleanup on model deletion
- Support for multiple images per model
- Dynamic URL generation in Resource layer

**Key Pattern:** Only store `file_path` and `file_disk` in database. Generate URLs dynamically in Resources using `temporaryUrl()` for private files.

**Disk Configuration:** Use `Disk` enum for type-safe disk selection. Visibility is auto-detected from disk config.

---

## 📦 Quick Setup Guide

Follow these **4 steps** to add image upload to your model:

### Step 1: Migration (Add Fields)

Add `path` and `disk` fields to your migration. **DO NOT add `url` field!**

```php
// database/migrations/XXXX_XX_XX_create_boardings_table.php

Schema::create('boardings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('title');

    // ✅ Add these TWO fields only
    $table->string('image_path');
    $table->string('image_disk')->nullable();

    $table->timestamps(6);
    $table->auditFields();
});
```

### Step 2: Model (Add Trait + Config)

Add trait to model and optionally configure storage:

```php
// app/Models/Boarding.php

use Daniardev\LaravelTsd\Enums\Disk;
use Daniardev\LaravelTsd\Traits\AppHasImages;

class Boarding extends Model
{
    use AppAuditable, AppHasImages; // ✅ Add trait

    protected $guarded = ['id', 'created_at', 'updated_at'];

    // ✅ Optional: Customize storage (remove to use defaults)
    public function getImageDiskConfig(string $field = 'image'): array
    {
        return [
            'disk' => Disk::FILES,                    // ✅ Use enum directly (no ->value needed!)
            'directory' => Directory::BOARDINGS,      // ✅ Can use Directory enum too!
            // Note: both disk and directory are auto-converted by trait
            // - Visibility auto-detected from disk config!
            // - Disk::FILES, Disk::PUBLIC, Disk::S3 → public
            // - Disk::PRIVATE_FILES → private
        ];
    }
}
```

**Available Disk Options (from `Disk` enum):**

| Disk | Type | Visibility | Bucket/Path |
|------|------|------------|-------------|
| `Disk::FILES` | MinIO/S3 | Public | `files` |
| `Disk::PRIVATE_FILES` | MinIO/S3 | Private | `private-files` |
| `Disk::S3` | MinIO/S3 | Public | From `AWS_BUCKET` |
| `Disk::LOCAL` | Local | Private | `storage/app/private` |
| `Disk::PUBLIC` | Local | Public | `storage/app/public` |

**📘 MinIO/S3 Setup:** First-time setup? See `/docs/patterns/app-has-images-setup.md`

### Step 3: Resource (Generate URL)

Add URL generation in your Resource:

```php
// app/Http/Resources/Api/Common/Boarding/BoardingResource.php

use Illuminate\Support\Facades\Storage;

class BoardingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,

            // ✅ Add storage fields from database
            'image_path' => $this->image_path,
            'image_disk' => $this->image_disk,

            // ✅ Add dynamic URL generation
            'image_url' => $this->when(
                $this->image_path && $this->image_disk,
                fn() => $this->getImageUrl()
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    // ✅ Add URL generation method
    protected function getImageUrl(): string
    {
        // Private storage (S3, S3-private, MinIO) - use temporary URL with token
        if (method_exists(Storage::disk($this->image_disk), 'temporaryUrl')) {
            return Storage::disk($this->image_disk)->temporaryUrl(
                $this->image_path,
                now()->addMinutes(30) // URL expires in 30 minutes
            );
        }

        // Public storage (local, public) - use direct URL
        return Storage::disk($this->image_disk)->url($this->image_path);
    }
}
```

### Step 4: Controller/Service (Use Trait)

Use the trait methods to upload images:

```php
// In Service or Controller
$boarding = new Boarding();
$boarding->title = 'Welcome Screen';

// ✅ Upload image - auto-sets image_path and image_disk
$boarding->uploadImage($request->image);
$boarding->save();

// ✅ Update image - auto-replaces old image
$boarding->uploadImage($request->new_image);
$boarding->save();

// ✅ Delete image - removes from storage
$boarding->deleteImage();
```

**That's it!** Your model now has full image upload capability.

---

## 📦 Detailed Setup

For more detailed information, see the sections below.

### 1. Add Trait to Model

```php
use Daniardev\LaravelTsd\Traits\AppAuditable;
use Daniardev\LaravelTsd\Traits\AppHasImages;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Boarding extends Model
{
    use HasUuids, AppAuditable, AppHasImages;
}
```

### 2. Database Migration

**IMPORTANT:** Only add `path` and `disk` fields. Do NOT add `url` field.

```php
Schema::create('boardings', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('title');

    // Image fields (only path and disk - NO url field)
    $table->string('image_path');
    $table->string('image_disk')->nullable();

    $table->timestamps(6);
    $table->auditFields();
});
```

---

## 🚀 Usage

### Single Image (Default)

**Model Definition:**
```php
class Boarding extends Model
{
    use AppHasImages;

    // Default field: 'image'
    // Auto-creates: image_path, image_disk (NO image_url field)
}
```

**Controller Usage:**
```php
// Create with image
$boarding = new Boarding();
$boarding->title = 'Welcome Screen';
$boarding->uploadImage($request->image);  // Auto-sets path and storage
$boarding->save();

// Update image
$boarding->uploadImage($request->new_image);
$boarding->save();

// Delete image
$boarding->deleteImage();
```

### Multiple Images

**Model Definition:**
```php
class User extends Model
{
    use AppHasImages;

    public function getImageFields(): array
    {
        return ['image', 'profile', 'thumbnail'];
    }

    // Creates 6 fields total (NO url fields):
    // - image_path, image_disk
    // - profile_path, profile_disk
    // - thumbnail_path, thumbnail_disk
}
```

**Controller Usage:**
```php
$user = new User();
$user->name = 'John';

// Upload different images
$user->uploadImage($request->avatar);      // → image_path, image_disk
$user->uploadProfile($request->profile);    // → profile_path, profile_disk
$user->uploadThumbnail($request->thumb);    // → thumbnail_path, thumbnail_disk

$user->save();

// Or use generic method
$user->uploadImageField('thumbnail', $file);
```

---

## ⚙️ Configuration

### Using Disk and Directory Enums (Recommended)

Use enums for type-safe disk and directory selection:

```php
use Daniardev\LaravelTsd\Enums\Disk;
use App\Enums\Directory; // Your project's directory enum

public function getImageDiskConfig(string $field = 'image'): array
{
    return [
        'disk' => Disk::FILES,                // ✅ Direct (no ->value needed!)
        'directory' => Directory::BOARDINGS,   // ✅ Direct too!
        // Visibility auto-detected from disk config!
    ];
}
```

**Important:** The trait automatically handles both enum and string values. You can use either:
- ✅ `Disk::FILES` or `Disk::FILES->value`
- ✅ `Directory::BOARDINGS` or `Directory::BOARDINGS->value`
- ✅ `'files'` or `'boardings'` (plain strings still work)

**Available Disks:**

```php
// MinIO/S3 Public
Disk::FILES          // Bucket: files, visibility: public
Disk::S3             // Bucket: from AWS_BUCKET, visibility: public

// MinIO/S3 Private
Disk::PRIVATE_FILES  // Bucket: private-files, visibility: private

// Local Storage
Disk::LOCAL          // Path: storage/app/private, visibility: private
Disk::PUBLIC         // Path: storage/app/public, visibility: public
```

**Helper Methods:**

```php
Disk::FILES->isPublic();           // true
Disk::FILES->isPrivate();          // false
Disk::FILES->isS3Based();          // true
Disk::FILES->isLocalBased();       // false
Disk::FILES->bucket();             // 'files'
Disk::FILES->visibility();         // 'public'
```

### Creating Directory Enum (Project-Specific)

Create your own directory enum in your project:

```php
// app/Enums/Directory.php

<?php

namespace App\Enums;

use Daniardev\LaravelTsd\Traits\AppEnum;

enum Directory: string
{
    use AppEnum;

    // Image directories
    case BOARDINGS = 'boardings';
    case BANNERS = 'banners';
    case PRODUCTS = 'products';
    case USERS = 'users';

    /**
     * Get all image directories.
     *
     * @return array<string>
     */
    public static function imageDirectories(): array
    {
        return [
            self::BOARDINGS->value,
            self::BANNERS->value,
            self::PRODUCTS->value,
            self::USERS->value,
        ];
    }
}
```

### Custom Storage Config per Field

Override `getImageDiskConfig()` to customize per model or per field:

```php
use Daniardev\LaravelTsd\Enums\Disk;
use App\Enums\Directory;

public function getImageDiskConfig(string $field = 'image'): array
{
    return match($field) {
        'profile' => [
            'disk' => Disk::FILES,
            'directory' => Directory::PROFILES,
        ],
        'thumbnail' => [
            'disk' => Disk::PUBLIC,
            'directory' => Directory::THUMBNAILS,
        ],
        'document' => [
            'disk' => Disk::PRIVATE_FILES,
            'directory' => Directory::DOCUMENTS,
        ],
        default => [
            'disk' => Disk::FILES,
            'directory' => Directory::BOARDINGS,
        ]
    };
}
```

**Note:** Visibility is auto-detected from disk config. You don't need to specify it manually.

### Custom Directory

Override `getImageDirectory()` to change default directory:

```php
public function getImageDirectory(string $field = 'image'): string
{
    // Default: strtolower(class_basename(static::class)) . 's'
    // Example: 'boardings', 'users', 'products'

    return 'custom/path/images';
}
```

---

## 🌐 URL Generation in Resources

**CRITICAL:** URLs are generated in Resource layer, NOT in trait or database.

### Resource Implementation

```php
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

            // Storage fields from database
            'image_path' => $this->image_path,
            'image_disk' => $this->image_disk,

            // URL generated dynamically
            'image_url' => $this->when(
                $this->image_path && $this->image_disk,
                fn() => $this->getImageUrl()
            ),

            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
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

### Multiple Images in Resource

```php
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,

            // Multiple images with dynamic URLs
            'avatar_path' => $this->avatar_path,
            'avatar_disk' => $this->avatar_disk,
            'avatar_url' => $this->when(
                $this->avatar_path && $this->avatar_disk,
                fn() => $this->getStorageUrl('avatar')
            ),

            'profile_path' => $this->profile_path,
            'profile_disk' => $this->profile_disk,
            'profile_url' => $this->when(
                $this->profile_path && $this->profile_disk,
                fn() => $this->getStorageUrl('profile')
            ),

            'thumbnail_path' => $this->thumbnail_path,
            'thumbnail_disk' => $this->thumbnail_disk,
            'thumbnail_url' => $this->when(
                $this->thumbnail_path && $this->thumbnail_disk,
                fn() => $this->getStorageUrl('thumbnail')
            ),
        ];
    }

    protected function getStorageUrl(string $field): string
    {
        $path = $this->{$field . '_path'};
        $disk = $this->{$field . '_disk'};

        if (method_exists(Storage::disk($disk), 'temporaryUrl')) {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(30));
        }

        return Storage::disk($disk)->url($path);
    }
}
```

---

## 📡 Supported Input Formats

### 1. Multipart/Form-Data (File Upload)

```javascript
// Frontend (JavaScript/FormData)
const formData = new FormData();
formData.append('title', 'Welcome');
formData.append('image', fileObject); // File from <input type="file">

fetch('/api/boardings', {
    method: 'POST',
    body: formData
});
```

```php
// Backend (Controller)
public function store(Request $request)
{
    $boarding = new Boarding();
    $boarding->title = $request->title;
    $boarding->uploadImage($request->file('image'));
    $boarding->save();
}
```

### 2. Base64 String (JSON)

```javascript
// Frontend (JavaScript)
fetch('/api/boardings', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        title: 'Welcome',
        image: 'data:image/png;base64,iVBORw0KG...' // Base64 string
    })
});
```

```php
// Backend (Controller)
public function store(Request $request)
{
    $boarding = new Boarding();
    $boarding->title = $request->title;
    $boarding->uploadImage($request->image); // Works with base64 too!
    $boarding->save();
}
```

---

## 🧹 Automatic Cleanup

The trait automatically deletes all images when model is deleted:

```php
// Deleting model automatically removes images from storage
$boarding->delete();
// → Deletes: image from S3/local storage
// → Cleans up: image_path, image_disk fields
```

For multiple images:
```php
$user->delete();
// → Deletes: image, profile, thumbnail from storage
// → All 6 fields cleaned up automatically
```

---

## 🔧 Available Methods

| Method | Description | Parameters | Returns |
|--------|-------------|------------|---------|
| `uploadImage($file)` | Upload default 'image' field | `UploadedFile\|string` | `array{path, disk}` |
| `upload{Field}($file)` | Upload specific field (magic) | `UploadedFile\|string` | `array{path, disk}` |
| `uploadImageField($field, $file)` | Generic upload method | `string`, `UploadedFile\|string` | `array{path, disk}` |
| `deleteImage()` | Delete default 'image' | - | `bool` |
| `delete{Field}()` | Delete specific field (magic) | - | `bool` |
| `deleteImageField($field)` | Generic delete method | `string` | `bool` |
| `getImagePath($field)` | Get path for field | `string` (default: 'image') | `string\|null` |
| `getImageDisk($field)` | Get storage disk | `string` (default: 'image') | `string\|null` |
| `getImageFields()` | Get all image fields | - | `array<string>` |
| `getImageDiskConfig($field)` | Get disk config for field | `string` (default: 'image') | `array{disk, visibility, directory}` |
| `getImageDirectory($field)` | Get directory for field | `string` (default: 'image') | `string` |

---

## 📝 Form Request Validation

```php
class BoardingFormRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'image' => ['required', function ($attribute, $value, $fail) {
                // Accept both file and base64
                if (is_string($value)) {
                    if (!preg_match('/^data:image\/(jpg|jpeg|png|webp);base64,/', $value)) {
                        $fail(__('validation.image', ['attribute' => __('label.image')]));
                    }
                } elseif (!($value instanceof UploadedFile)) {
                    $fail(__('validation.image', ['attribute' => __('label.image')]));
                }
            }],
        ];
    }
}
```

---

## 🎯 Best Practices

### DO ✅

1. **Only store path and disk in database** - URLs are dynamic
2. **Generate URLs in Resource layer** - Use `temporaryUrl()` for private files
3. **Use trait for all image uploads** - Consistent pattern across project
4. **Define multiple fields when needed** - Keep related images in same model
5. **Customize storage per field** - Public vs private images
6. **Let trait handle cleanup** - Automatic deletion on model delete

### DON'T ❌

1. **Don't store URLs in database** - Generate dynamically in Resources
2. **Don't manually set image_path/disk** - Trait handles this
3. **Don't create separate upload services** - Use this trait instead
4. **Don't add url fields to migrations** - Only path and disk
5. **Don't mix upload logic in controller** - Use service layer pattern
6. **Don't generate URLs in trait/model** - That's Resource's job

---

## 🔗 Related Documentation

- **Model Rules:** `/docs/ai/quick-reference.md#section-4-model-rules`
- **Service Layer:** `/docs/patterns/service-layer.md`
- **Database Transactions:** `/docs/patterns/database-transaction.md`
- **Error Handling:** `/docs/patterns/error-handling.md`
- **API Resources:** `/docs/patterns/api-resources.md`

---

## 📚 Full Example

### Migration (NO url field!)
```php
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

### Model
```php
class Boarding extends Model
{
    use HasUuids, AppAuditable, AppHasImages;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageDiskConfig(): array
    {
        return [
            'disk' => config('filesystems.default', 's3'),
            'visibility' => 'public',
            'directory' => 'boardings',
        ];
    }
}
```

### Service
```php
class BoardingService implements BoardingInterface
{
    use AppTransactional;

    public function create(
        string $title,
        string|\Illuminate\Http\UploadedFile $image,
        ?int $order = null,
        ?string $description = null,
        ?bool $isActive = null
    ): Boarding {
        $this->requireTransaction();

        $boarding = new Boarding();
        $boarding->title = $title;
        $boarding->description = $description;
        $boarding->order = $order ?? (Boarding::max('order') ?? 0) + 1;
        $boarding->is_active = $isActive ?? true;

        // Upload image - auto-sets path and storage
        $boarding->uploadImage($image);
        $boarding->save();

        return $boarding->fresh();
    }

    public function update(
        string $id,
        ?string $title = null,
        string|\Illuminate\Http\UploadedFile|null $image = null,
        ?int $order = null,
        ?string $description = null,
        ?bool $isActive = null
    ): Boarding {
        $this->requireTransaction();

        $boarding = $this->find($id);

        if ($title !== null) $boarding->title = $title;
        if ($order !== null) $boarding->order = $order;
        if ($description !== null) $boarding->description = $description;
        if ($isActive !== null) $boarding->is_active = $isActive;

        // Upload new image if provided (auto-deletes old)
        if ($image !== null) {
            $boarding->uploadImage($image);
        }

        $boarding->save();
        return $boarding->fresh();
    }

    public function delete(string $id): Boarding
    {
        $this->requireTransaction();
        $boarding = $this->find($id);
        $boarding->delete(); // Image auto-deleted by trait
        return $boarding;
    }
}
```

### Resource (Dynamic URL Generation)
```php
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

            // URL generated dynamically
            'image_url' => $this->when(
                $this->image_path && $this->image_disk,
                fn() => $this->getImageUrl()
            ),

            'audit' => AuditResource::make($this->resource),
        ];
    }

    protected function getImageUrl(): string
    {
        // S3/S3-private/MinIO - temporary URL with token
        if (method_exists(Storage::disk($this->image_disk), 'temporaryUrl')) {
            return Storage::disk($this->image_disk)->temporaryUrl(
                $this->image_path,
                now()->addMinutes(30)
            );
        }

        // Local/public disks - direct URL
        return Storage::disk($this->image_disk)->url($this->image_path);
    }
}
```

### Controller
```php
class BoardingController extends Controller
{
    public function __construct(
        private readonly BoardingInterface $service
    ) {}

    public function store(BoardingFormRequest $request): JsonResponse
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            $item = $this->service->create(
                title: $validated['title'],
                image: $validated['image'],
                order: $validated['order'] ?? null,
                description: $validated['description'] ?? null,
                isActive: $validated['is_active'] ?? null,
            );

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

            $item = $this->service->update(
                id: $id,
                title: $validated['title'] ?? null,
                image: $validated['image'] ?? null,
                order: $validated['order'] ?? null,
                description: $validated['description'] ?? null,
                isActive: $validated['is_active'] ?? null,
            );

            return AppResponse::success(
                BoardingResource::make($item),
                __('message.successUpdated')
            );
        });
    }

    public function destroy(string $id): JsonResponse
    {
        return DB::transaction(function () use ($id) {
            $item = $this->service->delete($id);

            return AppResponse::success(
                BoardingResource::make($item),
                __('message.successDeleted')
            );
        });
    }
}
```

---

## 🔐 Security Considerations

1. **Private Files:** Use `temporaryUrl()` for files that require authentication
   - URLs expire after specified time (default: 30 minutes)
   - Includes authentication token for access control
   - Works with S3, S3-private, MinIO

2. **Public Files:** Use direct `url()` for publicly accessible files
   - No authentication required
   - Faster access (no token generation)
   - Suitable for profile photos, product images, etc.

3. **File Validation:** Always validate file types and sizes in Form Request
   - Use mime type validation
   - Limit file size to prevent DoS
   - Scan for malware if accepting user uploads

---

**Version:** 2.0.0
**Last Updated:** 2026-03-19
**Package:** Daniardev\LaravelTsd\Traits\AppHasImages
**Pattern Source:** Youmrah Project - PackageAgreementResource