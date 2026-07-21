<?php

namespace Daniardev\LaravelTsd\Traits;

use Daniardev\LaravelTsd\Enums\Disk;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use UnitEnum;

trait AppHasImages
{
    /**
     * Boot the app has images trait for a model.
     */
    protected static function bootAppHasImages(): void
    {
        // Auto delete all images when model is deleted
        static::deleting(function (Model $model) {
            foreach ($model->getImageFields() as $field) {
                $model->deleteImageField($field);
            }
        });
    }

    /**
     * Get all image fields for this model.
     * Override in model to define multiple image fields.
     *
     * @return array<string>
     */
    public function getImageFields(): array
    {
        return ['image'];
    }

    /**
     * Get the disk configuration for this model.
     * Override in model to customize disk, visibility, and directory.
     *
     * @return array{disk: string, visibility: string, directory: string}
     */
    public function getImageDiskConfig(string $field = 'image'): array
    {
        $disk = config('filesystems.default', 'local');

        return [
            'disk' => $this->normalizeDisk($disk),
            'visibility' => $this->getVisibilityFromDisk($disk),
            'directory' => $this->getImageDirectory($field),
        ];
    }

    /**
     * Normalize disk to string value.
     * Handles Disk enum by converting to value.
     */
    protected function normalizeDisk(string|Disk $disk): string
    {
        return $disk instanceof Disk ? $disk->value : $disk;
    }

    /**
     * Normalize directory to string value.
     * Handles any enum by converting to value.
     */
    protected function normalizeDirectory(string|UnitEnum $directory): string
    {
        return $directory instanceof UnitEnum ? $directory->value : $directory;
    }

    /**
     * Get visibility from disk configuration.
     * Auto-detects visibility based on disk config.
     */
    protected function getVisibilityFromDisk(string|Disk $disk): string
    {
        // If Disk enum is passed, get visibility from enum
        if ($disk instanceof Disk) {
            return $disk->visibility();
        }

        // Check visibility from filesystems config
        $configVisibility = config("filesystems.disks.{$disk}.visibility");

        if ($configVisibility) {
            return $configVisibility;
        }

        // Fallback: determine by disk name
        return match ($disk) {
            'local', 'public', 's3', 'files' => 'public',
            'private-files' => 'private',
            default => 'public',
        };
    }

    /**
     * Get the directory path for image storage.
     * Override in model to customize.
     * Default: plural model name in lowercase (e.g., Boarding -> boardings)
     */
    public function getImageDirectory(string $field = 'image'): string
    {
        return strtolower(class_basename(static::class)).'s';
    }

    /**
     * Get field names for image attributes.
     *
     * @return array{path: string, disk: string}
     */
    protected function getImageFieldNames(string $field): array
    {
        return [
            'path' => $field.'_path',
            'disk' => $field.'_disk',
        ];
    }

    /**
     * Upload image and set all image-related fields for a specific field.
     * Supports both UploadedFile and base64 string.
     *
     * @return array{path: string, storage: string}
     */
    public function uploadImageField(string $field, UploadedFile|string $image): array
    {
        // Delete old image if exists
        $this->deleteImageField($field);

        $config = $this->getImageDiskConfig($field);
        $disk = $this->normalizeDisk($config['disk']);
        $directory = $this->normalizeDirectory($config['directory']);

        if (is_string($image)) {
            $result = $this->uploadBase64Image($image, $disk, $directory, $field);
        } else {
            $result = $this->uploadFileImage($image, $disk, $directory, $field);
        }

        $fieldNames = $this->getImageFieldNames($field);

        // Set attributes
        $this->setAttribute($fieldNames['path'], $result['path']);
        $this->setAttribute($fieldNames['disk'], $result['disk']);

        return $result;
    }

    /**
     * Magic method to handle uploadImage(), uploadProfile(), etc.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Match upload{Field}() pattern
        if (preg_match('/^upload([A-Z][a-zA-Z]+)$/', $method, $matches)) {
            $field = strtolower($matches[1]);
            if (in_array($field, $this->getImageFields())) {
                return $this->uploadImageField($field, $parameters[0]);
            }
        }

        // Match delete{Field}() pattern
        if (preg_match('/^delete([A-Z][a-zA-Z]+)$/', $method, $matches)) {
            $field = strtolower($matches[1]);
            if (in_array($field, $this->getImageFields())) {
                return $this->deleteImageField($field);
            }
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Upload file image.
     *
     * @return array{path: string, disk: string}
     */
    protected function uploadFileImage(UploadedFile $file, string $disk, string $directory, string $field): array
    {
        $path = $file->store($directory, [
            'disk' => $disk,
            'visibility' => $this->getImageDiskConfig($field)['visibility'] ?? 'public',
        ]);

        return [
            'path' => $path,
            'disk' => $disk,
        ];
    }

    /**
     * Upload base64 image.
     *
     * @return array{path: string, disk: string}
     *
     * @throws InvalidArgumentException
     */
    protected function uploadBase64Image(string $base64, string $disk, string $directory, string $field): array
    {
        if (! preg_match('/^data:image\/(\w+);base64,(.+)$/', $base64, $matches)) {
            throw new InvalidArgumentException('Invalid base64 image format');
        }

        $extension = $matches[1];
        $imageData = base64_decode($matches[2]);

        if ($imageData === false) {
            throw new InvalidArgumentException('Failed to decode base64 image');
        }

        $filename = uniqid($field.'_').'.'.$extension;
        $path = $directory.'/'.$filename;

        Storage::disk($disk)->put($path, $imageData, [
            'visibility' => $this->getImageDiskConfig($field)['visibility'] ?? 'public',
        ]);

        return [
            'path' => $path,
            'disk' => $disk,
        ];
    }

    /**
     * Delete image for a specific field from storage.
     */
    public function deleteImageField(string $field): bool
    {
        $fieldNames = $this->getImageFieldNames($field);
        $path = $this->getAttribute($fieldNames['path']);
        $disk = $this->getAttribute($fieldNames['disk'])
            ?? $this->normalizeDisk($this->getImageDiskConfig($field)['disk']);

        if (! $path) {
            return false;
        }

        if (Storage::disk($disk)->exists($path)) {
            Storage::disk($disk)->delete($path);

            return true;
        }

        return false;
    }

    /**
     * Legacy method for backward compatibility (upload default 'image' field).
     */
    public function uploadImage(UploadedFile|string $image): array
    {
        return $this->uploadImageField('image', $image);
    }

    /**
     * Legacy method for backward compatibility (delete default 'image' field).
     */
    public function deleteImage(): bool
    {
        return $this->deleteImageField('image');
    }

    /**
     * Get the image path for a specific field.
     */
    public function getImagePath(string $field = 'image'): ?string
    {
        $fieldNames = $this->getImageFieldNames($field);

        return $this->getAttribute($fieldNames['path']);
    }

    /**
     * Get the image disk for a specific field.
     */
    public function getImageDisk(string $field = 'image'): ?string
    {
        $fieldNames = $this->getImageFieldNames($field);

        return $this->getAttribute($fieldNames['disk']);
    }

    /**
     * Scope to filter by image existence for any field.
     *
     * @param  Builder  $query
     */
    public function scopeWhereHasImage($query, string $field = 'image', bool $hasImage = true): Builder
    {
        $fieldNames = $this->getImageFieldNames($field);

        return $query->whereNotNull($fieldNames['path']);
    }

    /**
     * Scope to filter by profile image existence.
     *
     * @param  Builder  $query
     */
    public function scopeWhereHasProfile($query, bool $hasImage = true): Builder
    {
        return $query->whereHasImage('profile', $hasImage);
    }
}
