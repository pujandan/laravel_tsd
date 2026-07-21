<?php

namespace Daniardev\LaravelTsd\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to normalize boolean and null values in request.
 *
 * Converts:
 * - true (boolean) → "1" (string) for Laravel boolean validation
 * - false (boolean) → "0" (string) for Laravel boolean validation
 * - "true" (string) → "1" (string) for Laravel boolean validation
 * - "false" (string) → "0" (string) for Laravel boolean validation
 * - "null" (string) → null (actual null value)
 * - "" (empty string) → null (actual null value)
 *
 * This ensures frontend can send true/false and it will be converted
 * to "1"/"0" which Laravel expects for boolean validation rules.
 *
 * Works for both:
 * - Request body (JSON, form data)
 * - Query parameters (filter[is_active]=true)
 */
class AppParseBoolAndNull
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Normalize both request body and query parameters
        $allData = array_merge($request->query(), $request->request->all());
        $normalizedData = $this->normalizeData($allData);

        // Merge normalized data back to request
        $request->merge($normalizedData);

        return $next($request);
    }

    /**
     * Normalize boolean and null values in data.
     *
     * @param mixed $data
     * @return mixed
     */
    protected function normalizeData(mixed $data): mixed
    {
        if (!is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Recursively normalize nested arrays (like filter[is_active])
                $data[$key] = $this->normalizeData($value);
            } elseif (is_bool($value)) {
                // Convert boolean true/false to "1"/"0" for Laravel validation
                $data[$key] = $value ? '1' : '0';
            } elseif (is_string($value)) {
                // Convert string representations
                $lowerValue = strtolower(trim($value));

                // Empty string → null
                if ($value === '') {
                    $data[$key] = null;
                } elseif ($lowerValue === 'true') {
                    $data[$key] = '1';
                } elseif ($lowerValue === 'false') {
                    $data[$key] = '0';
                } elseif ($lowerValue === 'null') {
                    $data[$key] = null;
                }
            }
        }

        return $data;
    }
}