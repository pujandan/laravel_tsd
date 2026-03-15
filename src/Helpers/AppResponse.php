<?php

namespace Daniardev\LaravelTsd\Helpers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * Standardized JSON response formatter for API endpoints.
 *
 * Provides consistent response format across all API routes with support for:
 * - Success responses with data
 * - Error responses with messages
 * - Selection/dropdown data for UI components
 *
 * @package Daniardev\LaravelTsd\Helpers
 */
class AppResponse
{
    /**
     * Return success response with data and optional message.
     *
     * Automatically handles pagination data from Laravel resources.
     * Returns format: {"code": 200, "message": "...", "data": {...}}
     * or with pagination: {"code": 200, "message": "...", "data": {...}, "meta": {...}, "links": {...}}
     *
     * @param JsonResource|null $data The resource data (can be null for operations with no return data)
     * @param string|null $message Optional success message
     * @return JsonResponse JSON response with 200 status code
     */
    public static function success(?JsonResource $data, ?string $message = null): JsonResponse
    {
        $response = [
            'code' => 200,
            'message' => $message,
        ];

        // Convert resource to array if provided
        $array = $data? $data->toArray(new Request()) : null;

        // Handle paginated responses (includes 'data', 'meta', 'links' keys)
        if ($array !== null && isset($array['data'])) {
            // Merge with existing pagination structure
            $response = array_merge($response, $array);
        } elseif ($array !== null) {
            // Single item response
            $response['data'] = $array;
        }

        return response()->json($response, $response['code']);
    }

    /**
     * Return error response with message and HTTP status code.
     *
     * Used for exception handling and validation errors.
     * Returns format: {"code": 404, "message": "Error description"}
     *
     * @param string|null $message Error message description
     * @param int $code HTTP status code (default: 404)
     * @param JsonResource|null $error Optional additional error details
     * @return JsonResponse JSON response with specified status code
     */
    public static function error(?string $message = null, int $code = 404, ?JsonResource $error = null): JsonResponse
    {
        $response = [
            'code' => $code,
            'message' => $message,
        ];

        // Add optional error details
        if ($error !== null) {
            $response['error'] = $error->toArray(new Request());
        }

        return response()->json($response, $code);
    }

    /**
     * Return simple response with message and data (no code field).
     *
     * Used for non-standard responses where status code is not needed.
     * Returns format: {"message": "...", "data": {...}}
     *
     * @param string|null $message Response message
     * @param array $data Additional data to include
     * @return JsonResponse JSON response with 200 status code
     */
    public static function print(?string $message, array $data = []): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Convert enum cases to selection dropdown format.
     *
     * Automatically gets labels from enum's label() method if available.
     * Returns format: [{"key": "...", "type": "dropdown", "values": [{"id": enum, "label": "..."}, ...]}]
     *
     * @param string $enumClass Full enum class name (e.g., App\Enums\Status::class)
     * @param string $key Field name for the selection
     * @param string|null $type Optional type identifier (default: "dropdown")
     * @return Collection Formatted selection data with "All" option prepended
     */
    public static function selectionEnums(string $enumClass, string $key, ?string $type = null): Collection
    {
        $cases = $enumClass::cases();

        // Transform enum cases to selection items
        $items = collect($cases)->map(function ($case) {
            return [
                'id' => $case,
                'label' => method_exists($case, 'label') ? $case->label() : null,
            ];
        });

        return self::selection($items, $key, 'label', $type);
    }

    /**
     * Format collection as selection dropdown with "All" option.
     *
     * Prepends "All" option at the beginning for filter dropdowns.
     * Returns format: [{"key": "...", "type": "dropdown", "values": [{"id": null, "label": "All"}, {...}]}]
     *
     * @param Collection $items Collection of items to format
     * @param string $key Field name for the selection
     * @param string $value Field name to use as label (default: 'label')
     * @param string|null $type Optional type identifier (default: "dropdown")
     * @return Collection Formatted selection data with "All" option prepended
     */
    public static function selection(Collection $items, string $key, string $value = 'label', ?string $type = null): Collection
    {
        // Build values array starting with "All" option
        $values = collect([
            ['id' => null, 'label' => __('tsd_label.all')],
        ]);

        // Add items from collection
        foreach ($items as $item) {
            $values->add([
                'id' => $item['id'] ?? null,
                'label' => $item[$value] ?? null,
            ]);
        }

        return collect([
            [
                'key' => $key,
                'type' => $type ?? 'dropdown',
                'values' => $values,
            ],
        ]);
    }
}