<?php

namespace Daniardev\LaravelTsd\Helpers;

use Daniardev\LaravelTsd\Data\PaginationData;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Query helper for pagination and sorting.
 */
class AppQuery
{
    /**
     * Default allowed columns for sorting (whitelist for security).
     * Override in your service if needed.
     */
    private static array $defaultAllowedColumns = [
        'id', 'created_at', 'updated_at', 'deleted_at',
        'name', 'email', 'status', 'code'
    ];

    /**
     * Paginate query with sorting.
     *
     * @param Builder $query Query builder instance
     * @param PaginationData $pagination Pagination data
     * @param array|null $allowedColumns Optional whitelist of allowed sort columns
     * @return LengthAwarePaginator
     */
    public static function paginate(
        Builder $query,
        PaginationData $pagination,
        ?array $allowedColumns = null
    ): LengthAwarePaginator {
        $sortBy = self::validateSortColumn($pagination->sortBy, $allowedColumns);
        $query->orderBy($sortBy, $pagination->direction);

        return $query->paginate(
            perPage: $pagination->size,
            page: $pagination->page
        );
    }

    /**
     * Paginate query from request with sorting.
     *
     * @param Builder $query Query builder instance
     * @param Request $request HTTP request
     * @param array|null $allowedColumns Optional whitelist of allowed sort columns
     * @return LengthAwarePaginator
     */
    public static function pagination(Builder $query, Request $request, ?array $allowedColumns = null): LengthAwarePaginator
    {
        // Sorting
        $sortBy = $request->input('sort.by', 'created_at');
        $sortBy = AppHelper::isCamel($sortBy) ? Str::snake($sortBy) : $sortBy;
        $sortBy = self::validateSortColumn($sortBy, $allowedColumns);

        $sortOrder = $request->input('sort.direction', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $page = $request->input('page', 1);
        $size = $request->input('size', 10);

        return $query->paginate(perPage: $size, page: $page);
    }

    /**
     * Apply sorting to query from request.
     *
     * @param Builder $query Query builder instance
     * @param Request $request HTTP request
     * @param array|null $allowedColumns Optional whitelist of allowed sort columns
     * @return Builder
     */
    public static function sort(Builder $query, Request $request, ?array $allowedColumns = null): Builder
    {
        $sortBy = $request->input('sort.by', 'created_at');
        $sortBy = AppHelper::isCamel($sortBy) ? Str::snake($sortBy) : $sortBy;
        $sortBy = self::validateSortColumn($sortBy, $allowedColumns);

        $sortOrder = $request->input('sort.direction', 'desc');
        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Validate sort column against whitelist.
     *
     * @param string $column Column name to validate
     * @param array|null $allowedColumns Custom whitelist (uses default if null)
     * @return string Validated column name (defaults to 'created_at')
     */
    private static function validateSortColumn(string $column, ?array $allowedColumns = null): string
    {
        $whitelist = $allowedColumns ?? self::$defaultAllowedColumns;

        if (in_array($column, $whitelist)) {
            return $column;
        }

        // Return default if column not in whitelist
        return 'created_at';
    }

    /**
     * Paginate a collection.
     *
     * @param Collection $collection Collection to paginate
     * @param PaginationData $pagination Pagination data
     * @return LengthAwarePaginator
     */
    public static function paginateCollection(
        Collection $collection,
        PaginationData $pagination
    ): LengthAwarePaginator {
        $page = $pagination->page;
        $perPage = $pagination->size;
        $sliced = $collection->slice(($page - 1) * $perPage, $perPage);

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced->values(),
            $collection->count(),
            $perPage,
            $page
        );
    }
}