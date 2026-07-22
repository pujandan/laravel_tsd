<?php

namespace Daniardev\LaravelTsd\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Audit Resource
 *
 * Reusable resource untuk audit information (created, updated, deleted).
 * Digunakan bersama AppAuditable trait untuk konsistensi format response audit.
 *
 * @package Daniardev\LaravelTsd\Resources\Api
 */
class AuditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $audit = [
            'created' => [
                'at' => $this->created_at?->format('Y-m-d H:i:s'),
                'by' => $this->creator_name,
                'by_id' => $this->created_by,
            ],
            'updated' => [
                'at' => $this->updated_at?->format('Y-m-d H:i:s'),
                'by' => $this->updater_name,
                'by_id' => $this->updated_by,
            ],
        ];

        // Include deleted information only if model uses soft deletes
        if (method_exists($this->resource, 'getDeletedAtColumn')) {
            $audit['deleted'] = [
                'at' => $this->deleted_at?->format('Y-m-d H:i:s'),
                'by' => $this->deleter_name,
                'by_id' => $this->deleted_by,
            ];
        }

        return $audit;
    }
}
