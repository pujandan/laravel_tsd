<?php

namespace Daniardev\LaravelTsd\Enums;

enum Disk: string
{
    // Local storage
    case LOCAL = 'local';
    case PUBLIC = 'public';

    // MinIO/S3 storage
    case S3 = 's3';
    case FILES = 'files';
    case PRIVATE_FILES = 'private-files';

    /**
     * Get visibility for this disk.
     * Returns visibility based on disk type or config.
     *
     * @return string
     */
    public function visibility(): string
    {
        return match($this) {
            self::LOCAL, self::PUBLIC, self::S3, self::FILES => 'public',
            self::PRIVATE_FILES => 'private',
        };
    }

    /**
     * Get bucket name for S3 disks.
     *
     * @return string|null
     */
    public function bucket(): ?string
    {
        return match($this) {
            self::FILES => 'files',
            self::PRIVATE_FILES => 'private-files',
            default => null,
        };
    }

    /**
     * Check if disk is private.
     *
     * @return bool
     */
    public function isPrivate(): bool
    {
        return $this->visibility() === 'private';
    }

    /**
     * Check if disk is public.
     *
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->visibility() === 'public';
    }

    /**
     * Check if disk is S3-based (MinIO, AWS S3, etc).
     *
     * @return bool
     */
    public function isS3Based(): bool
    {
        return in_array($this, [self::S3, self::FILES, self::PRIVATE_FILES]);
    }

    /**
     * Check if disk is local-based.
     *
     * @return bool
     */
    public function isLocalBased(): bool
    {
        return in_array($this, [self::LOCAL, self::PUBLIC]);
    }
}