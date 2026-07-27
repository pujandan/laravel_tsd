<?php

namespace Daniardev\LaravelTsd\Helpers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Format Request.
 */
class AppResource
{
    public static function pagination(ResourceCollection $data): array
    {
        return [
            // request
            'page' => $data->currentPage(),
            'size' => $data->perPage(),
            // items
            'from' => $data->firstItem(), // first item in page and size
            'to' => $data->lastItem(), // last item in page and size
            'count' => $data->count(), // count in page and size
            'total' => $data->total(), // total items
            // pages
            'page_last' => $data->lastPage(),
            'page_more' => $data->hasMorePages(),
        ];
    }

    public static function whenLoaded(mixed $resource, JsonResource $data, string $relationship) : JsonResource
    {
        return $resource::make($data->whenLoaded($relationship));
    }

}
