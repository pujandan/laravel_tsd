# AppHasImages Setup Guide - MinIO/S3 Configuration

**Overview:** Guide for setting up MinIO/S3 storage disks for `AppHasImages` trait in new projects.

---

## 🚀 Quick Setup

### Step 1: Add MinIO/S3 Disks to Filesystem Config

Add `files` and `private-files` disks to `config/filesystems.php`:

```php
// config/filesystems.php

'disks' => [

    // Existing disks...

    'files' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET_PUBLIC', 'files'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'visibility' => 'public',
        'throw' => false,
        'report' => false,
    ],

    'private-files' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET_PRIVATE', 'private-files'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'visibility' => 'private',
        'throw' => false,
        'report' => false,
    ],

],
```

### Step 2: Add Environment Variables

Add to `.env` file:

```env
# MinIO/S3 Configuration
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin
AWS_DEFAULT_REGION=ap-southeast-1

# For MinIO
AWS_ENDPOINT=http://localhost:19000
AWS_USE_PATH_STYLE_ENDPOINT=true

# Buckets
AWS_BUCKET_PUBLIC=files
AWS_BUCKET_PRIVATE=private-files
```

### Step 3: Verify Disk Enum Options

The `Disk` enum now includes these options:

| Disk | Type | Visibility | Bucket/Path |
|------|------|------------|-------------|
| `Disk::FILES` | MinIO/S3 | Public | `files` |
| `Disk::PRIVATE_FILES` | MinIO/S3 | Private | `private-files` |
| `Disk::S3` | MinIO/S3 | Public | From `AWS_BUCKET` |
| `Disk::LOCAL` | Local | Private | `storage/app/private` |
| `Disk::PUBLIC` | Local | Public | `storage/app/public` |

### Step 4: Use in Model

```php
use Daniardev\LaravelTsd\Enums\Disk;
use Daniardev\LaravelTsd\Traits\AppHasImages;

class YourModel extends Model
{
    use AppHasImages;

    public function getImageDiskConfig(): array
    {
        return [
            'disk' => Disk::FILES,  // No ->value needed!
            'directory' => 'your-model',
            // Visibility auto-detected from disk config
        ];
    }
}
```

---

## 🐳 Docker/Podman Setup (Optional)

If using Docker/Podman with MinIO, add to `docker-compose.yaml` or `podman-compose.yaml`:

```yaml
services:
  minio:
    image: quay.io/minio/minio:latest
    container_name: minio
    command: server /data --console-address ":9001"
    environment:
      MINIO_ROOT_USER: ${AWS_ACCESS_KEY_ID}
      MINIO_ROOT_PASSWORD: ${AWS_SECRET_ACCESS_KEY}
    ports:
      - "${MINIO_API_PORT:-9000}:9000"
      - "${MINIO_CONSOLE_PORT:-9001}:9001"
    volumes:
      - minio_data:/data

  minio-init:
    image: docker.io/minio/mc:latest
    depends_on:
      - minio
    entrypoint: >
      sh -c "
      until /usr/bin/mc alias set myminio http://minio:9000 $$AWS_ACCESS_KEY_ID $$AWS_SECRET_ACCESS_KEY; do
        sleep 2;
      done;

      /usr/bin/mc mb --ignore-existing myminio/files;
      /usr/bin/mc mb --ignore-existing myminio/private-files;

      /usr/bin/mc anonymous set download myminio/files;
      /usr/bin/mc anonymous set none myminio/private-files;

      echo 'Buckets created successfully!';
      "
    environment:
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY}
```

Add to `.env`:

```env
MINIO_API_PORT=19000
MINIO_CONSOLE_PORT=19001
```

---

## ✅ Verification

Test the setup:

```php
// In tinker or controller
use Daniardev\LaravelTsd\Enums\Disk;
use Illuminate\Support\Facades\Storage;

// Test public disk
Storage::disk(Disk::FILES->value)->put('test.txt', 'Hello World');
$url = Storage::disk(Disk::FILES->value)->url('test.txt');
echo $url; // Should return accessible URL

// Test private disk
Storage::disk(Disk::PRIVATE_FILES->value)->put('private.txt', 'Secret');
$tempUrl = Storage::disk(Disk::PRIVATE_FILES->value)->temporaryUrl(
    'private.txt',
    now()->addMinutes(30)
);
echo $tempUrl; // Should return signed URL with token
```

---

## 🔧 Troubleshooting

### Issue: "Bucket not found"
**Solution:** Ensure MinIO init container has run successfully. Check logs: `podman logs minio-init`

### Issue: "Access denied"
**Solution:** Verify `AWS_ACCESS_KEY_ID` and `AWS_SECRET_ACCESS_KEY` match between `.env` and MinIO container

### Issue: "URL returns 403"
**Solution:**
- For public files: Check bucket policy is set to `download`
- For private files: Ensure `temporaryUrl()` is used, not direct `url()`

### Issue: "Disk enum not found"
**Solution:** Run `composer dump-autoload` to register new enum

---

## 📚 Related Documentation

- **AppHasImages Pattern:** `/docs/patterns/app-has-images.md`
- **Quick Reference:** `/docs/ai/quick-reference.md#section-175-apphasimages`
- **Enum Pattern:** `/docs/patterns/enum-pattern.md`

---

**Version:** 1.0.0
**Last Updated:** 2026-03-19
**Package:** Daniardev\LaravelTsd